<?php
// Apply a sanction to an existing case row (best-effort detection).
// Returns JSON with detailed errors when $EXPOSE_ERRORS = true.

ini_set('display_startup_errors', '0');
ini_set('display_errors', '0');
error_reporting(E_ALL);

$EXPOSE_ERRORS = true;
ob_start();

set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() === 0) return false; // respect @
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function() use ($EXPOSE_ERRORS) {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $buf = ob_get_clean();
        header('Content-Type: application/json; charset=utf-8');
        $payload = ['success' => false, 'message' => 'Fatal error'];
        if ($EXPOSE_ERRORS) { $payload['error'] = $err; if ($buf !== '') $payload['debug_output'] = $buf; }
        echo json_encode($payload);
    }
});

try {
    if (file_exists(__DIR__ . '/config.php')) require_once __DIR__ . '/config.php';

    if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
        $db_host = defined('DB_HOST') ? DB_HOST : '127.0.0.1:3305';
        $db_user = defined('DB_USER') ? DB_USER : 'root';
        $db_pass = defined('DB_PASS') ? DB_PASS : '';
        $db_name = defined('DB_NAME') ? DB_NAME : 'student_disciplinary_system';
        $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_errno) throw new RuntimeException('Database connection failed: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');

    header('Content-Type: application/json; charset=utf-8');

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) throw new InvalidArgumentException('Invalid JSON payload');

    session_start();
    $currentUser = $_SESSION['user'] ?? null;
    $role = $currentUser['role'] ?? $currentUser['Role'] ?? $currentUser['user_type'] ?? null;
    if (!$currentUser || !$role || !in_array(strtolower($role), ['lecturer','admin'], true)) {
        throw new RuntimeException('Unauthorized');
    }

    $case_id = trim((string)($input['case_id'] ?? ''));
    $sanction_type = trim((string)($input['sanction_type'] ?? ''));
    $duration_days = isset($input['duration_days']) ? (int)$input['duration_days'] : 0;
    $effective_date = trim((string)($input['effective_date'] ?? ''));
    $notes = trim((string)($input['notes'] ?? ''));

    if ($case_id === '' || $sanction_type === '') throw new InvalidArgumentException('Missing case_id or sanction_type');

    // detect case table
    $candidateTables = ['incidentreport','incidents','cases','reports','student_cases','disciplinary_cases','complaints'];
    $found = null;
    foreach ($candidateTables as $t) {
        $r = $mysqli->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".$mysqli->real_escape_string($t)."' LIMIT 1");
        if ($r && $r->num_rows) { $found = $t; if ($r) $r->free(); break; }
        if ($r) $r->free();
    }
    if (!$found) {
        $r = $mysqli->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $name = $row['TABLE_NAME'];
                if (stripos($name, 'incident') !== false || stripos($name, 'case') !== false || stripos($name, 'disciplin') !== false) { $found = $name; break; }
            }
            $r->free();
        }
    }
    if (!$found) throw new RuntimeException('No case/incident table found. Add your table to $candidateTables or rename it.');

    // inspect columns
    $cols = [];
    $r = $mysqli->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".$mysqli->real_escape_string($found)."'");
    if ($r) {
        while ($ro = $r->fetch_assoc()) $cols[] = $ro['COLUMN_NAME'];
        $r->free();
    }

    // pick helper
    $pick = function(array $cols, array $cands) {
        foreach ($cands as $c) {
            foreach ($cols as $col) if (strcasecmp($col, $c) === 0) return $col;
        }
        foreach ($cands as $c) {
            foreach ($cols as $col) if (stripos($col, $c) !== false) return $col;
        }
        return null;
    };

    $col_id = $pick($cols, ['IncidentID','ReportID','id','CaseID','ReportId']);
    $col_sanctions = $pick($cols, ['Sanctions','sanctions','Penalty','penalties']);
    $col_status = $pick($cols, ['Status','status','CaseStatus']);
    $col_decided = $pick($cols, ['ResolvedAt','resolved_at','DecisionDate','DecidedAt']);
    $col_notes = $pick($cols, ['Notes','notes','Remarks','remarks','Description','description','ReportText']);

    if (!$col_id) throw new RuntimeException('Case id column not found in table ' . $found);

    // build sanction payload
    $issuer = $currentUser['Username'] ?? $currentUser['username'] ?? $currentUser['Email'] ?? 'unknown';
    $sanction_payload = "Type: {$sanction_type}";
    if ($duration_days > 0) $sanction_payload .= "; Duration(days): {$duration_days}";
    if ($effective_date !== '') $sanction_payload .= "; Effective: {$effective_date}";
    if ($notes !== '') $sanction_payload .= "; Notes: {$notes}";
    $sanction_payload .= "; IssuedBy: {$issuer} @ " . date('Y-m-d H:i:s');

    // helper to bind params (mysqli requires references)
    $bind_params = function($stmt, $types, $params) {
        $a = [];
        $a[] = $types;
        foreach ($params as $k => $v) $a[] = &$params[$k];
        return call_user_func_array([$stmt, 'bind_param'], $a);
    };

    // perform update inside transaction
    $mysqli->begin_transaction();
    try {
        if ($col_sanctions) {
            // append to existing sanctions column
            $sel = $mysqli->prepare("SELECT `{$col_sanctions}` FROM `{$found}` WHERE `{$col_id}` = ? LIMIT 1");
            if (! $sel) throw new RuntimeException('Prepare failed (select existing sanctions): ' . $mysqli->error);
            $sel->bind_param('s', $case_id);
            $sel->execute();
            $sel->bind_result($existingSanctions);
            $sel->fetch();
            $sel->close();

            $newSanctions = trim(($existingSanctions ? $existingSanctions . "\n" : '') . $sanction_payload);

            $setParts = ["`{$col_sanctions}` = ?"];
            $params = [$newSanctions];
            $types = 's';

            if ($col_status) {
                $setParts[] = "`{$col_status}` = ?";
                $params[] = 'Sanctioned';
                $types .= 's';
            }
            if ($col_decided) {
                $setParts[] = "`{$col_decided}` = ?";
                $decidedAt = date('Y-m-d H:i:s');
                $params[] = $decidedAt;
                $types .= 's';
            }

            $params[] = $case_id;
            $types .= 's';

            $sql = "UPDATE `{$found}` SET " . implode(', ', $setParts) . " WHERE `{$col_id}` = ?";
            $upd = $mysqli->prepare($sql);
            if (! $upd) throw new RuntimeException('Prepare failed (update sanctions): ' . $mysqli->error);

            $bind_params($upd, $types, $params);
            if (! $upd->execute()) {
                $err = $upd->error ?: 'Execute failed';
                $upd->close();
                throw new RuntimeException('Update failed: ' . $err);
            }
            $affected = $upd->affected_rows;
            $upd->close();
        } else {
            // no sanctions column: try to write to notes/description and status
            if (!$col_status && !$col_notes) throw new RuntimeException('No suitable column (Sanctions/Notes/Status) found to store sanction');

            $setParts = [];
            $params = [];
            $types = '';

            if ($col_status) {
                $setParts[] = "`{$col_status}` = ?";
                $params[] = 'Sanctioned';
                $types .= 's';
            }
            if ($col_notes) {
                // fetch existing note
                $sel = $mysqli->prepare("SELECT `{$col_notes}` FROM `{$found}` WHERE `{$col_id}` = ? LIMIT 1");
                if (! $sel) throw new RuntimeException('Prepare failed (select existing note): ' . $mysqli->error);
                $sel->bind_param('s', $case_id);
                $sel->execute();
                $sel->bind_result($existingNote);
                $sel->fetch();
                $sel->close();

                $newNote = trim(($existingNote ? $existingNote . "\n" : '') . $sanction_payload);
                $setParts[] = "`{$col_notes}` = ?";
                $params[] = $newNote;
                $types .= 's';
            }

            $params[] = $case_id;
            $types .= 's';

            $sql = "UPDATE `{$found}` SET " . implode(', ', $setParts) . " WHERE `{$col_id}` = ?";
            $upd = $mysqli->prepare($sql);
            if (! $upd) throw new RuntimeException('Prepare failed (update): ' . $mysqli->error);

            $bind_params($upd, $types, $params);
            if (! $upd->execute()) {
                $err = $upd->error ?: 'Execute failed';
                $upd->close();
                throw new RuntimeException('Update failed: ' . $err);
            }
            $affected = $upd->affected_rows;
            $upd->close();
        }

        $mysqli->commit();
        echo json_encode(['success' => true, 'message' => 'Sanction applied', 'case_id' => $case_id, 'affected_rows' => (int)$affected]);
        exit;

    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }

} catch (Throwable $e) {
    $buf = ob_get_clean();
    $payload = ['success' => false, 'message' => $e->getMessage()];
    if ($EXPOSE_ERRORS) {
        $payload['type'] = get_class($e);
        $payload['file'] = $e->getFile();
        $payload['line'] = $e->getLine();
        $payload['trace'] = $e->getTraceAsString();
        if ($buf !== '') $payload['debug_output'] = $buf;
        if (isset($mysqli) && ($mysqli instanceof mysqli) && $mysqli->error) $payload['mysqli_error'] = $mysqli->error;
    }
    echo json_encode($payload);
    exit;
}
?>