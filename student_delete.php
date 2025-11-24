<?php
// student_delete.php
// JSON API to delete a student. Returns detailed error info when $EXPOSE_ERRORS = true.

ini_set('display_startup_errors', '0');
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Toggle to true while debugging, set false in production
$EXPOSE_ERRORS = true;

// Buffer output so accidental HTML/warnings can be captured
ob_start();

// Convert PHP warnings/notices into exceptions
set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() === 0) return false; // respect @
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Catch fatal errors
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

    // DB fallback
    if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
        $db_host = defined('DB_HOST') ? DB_HOST : '127.0.0.1:3305';
        $db_user = defined('DB_USER') ? DB_USER : 'root';
        $db_pass = defined('DB_PASS') ? DB_PASS : '';
        $db_name = defined('DB_NAME') ? DB_NAME : 'student_disciplinary_system';
        $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_errno) throw new RuntimeException('Database connection failed: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');

    // read JSON
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) throw new InvalidArgumentException('Invalid JSON payload');

    session_start();
    $currentUser = $_SESSION['user'] ?? null;
    $role = $currentUser['role'] ?? $currentUser['Role'] ?? $currentUser['user_type'] ?? null;
    if (!$currentUser || !$role || !in_array(strtolower($role), ['admin','lecturer'], true)) {
        throw new RuntimeException('Unauthorized');
    }

    $studentId = isset($input['student_id']) ? (int)$input['student_id'] : 0;
    $password  = isset($input['password']) ? (string)$input['password'] : '';
    if ($studentId <= 0) throw new InvalidArgumentException('Invalid student_id');
    if ($password === '') throw new InvalidArgumentException('Password is required to confirm deletion');

    // verify password against users table using username/email from session if available
    $username = $currentUser['Username'] ?? $currentUser['username'] ?? $currentUser['Email'] ?? $currentUser['email'] ?? null;
    if (!$username) throw new RuntimeException('Current user identifier not available in session');

    // --- detect password column dynamically (common candidates) ---
    $candidateCols = ['Password','password','passwd','pass','PasswordHash','password_hash','pwd','user_pass'];
    $escaped = array_map(function($c) use ($mysqli){ return $mysqli->real_escape_string($c); }, $candidateCols);
    $inList = "'" . implode("','", $escaped) . "'";

    $colRes = $mysqli->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME IN ($inList) LIMIT 1");
    if (! $colRes) {
        throw new RuntimeException('Failed to inspect users table: ' . $mysqli->error);
    }
    $pwdCol = null;
    if ($row = $colRes->fetch_assoc()) {
        $pwdCol = $row['COLUMN_NAME'];
    }
    $colRes->free();

    if (! $pwdCol) {
        // Helpful error for debugging: show expected column names
        throw new RuntimeException("Password column not found in 'users' table. Expected one of: " . implode(', ', $candidateCols) . ". Run SHOW COLUMNS FROM users; to inspect schema.");
    }

    // perform lookup using the detected column
    $colEsc = $mysqli->real_escape_string($pwdCol);
    $sql = "SELECT `$colEsc` FROM users WHERE Username = ? OR Email = ? LIMIT 1";
    $uStmt = $mysqli->prepare($sql);
    if (! $uStmt) throw new RuntimeException('Prepare failed (user lookup): ' . $mysqli->error);
    $uStmt->bind_param('ss', $username, $username);
    $uStmt->execute();
    $uStmt->bind_result($dbPass);
    if (! $uStmt->fetch()) {
        $uStmt->close();
        throw new RuntimeException('Unable to verify password: user record not found');
    }
    $uStmt->close();

    // verify password: use password_verify for hashed strings that look like bcrypt/argon
    $ok = false;
    if (strpos($dbPass, '$2y$') === 0 || strpos($dbPass, '$2a$') === 0 || strpos($dbPass, '$argon') === 0) {
        $ok = password_verify($password, $dbPass);
    } else {
        // fallback: direct compare (legacy plain text)
        $ok = hash_equals($dbPass, $password);
    }
    if (! $ok) throw new RuntimeException('Password verification failed');

    // ensure student exists
    $chk = $mysqli->prepare("SELECT StudentID FROM student WHERE StudentID = ? LIMIT 1");
    if (! $chk) throw new RuntimeException('Prepare failed (student check): ' . $mysqli->error);
    $chk->bind_param('i', $studentId);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        $chk->close();
        throw new RuntimeException('Student not found');
    }
    $chk->close();

    // delete inside transaction
    $mysqli->begin_transaction();
    try {
        // optional: delete related incidents first if foreign key prevents deletion
        // $dInc = $mysqli->prepare("DELETE FROM incidents WHERE StudentID = ?");
        // if ($dInc) { $dInc->bind_param('i',$studentId); $dInc->execute(); $dInc->close(); }

        $del = $mysqli->prepare("DELETE FROM student WHERE StudentID = ?");
        if (! $del) throw new RuntimeException('Prepare failed (delete): ' . $mysqli->error);
        $del->bind_param('i', $studentId);
        if (! $del->execute()) {
            $err = $del->error ?: 'Delete failed';
            $del->close();
            throw new RuntimeException($err);
        }
        $affected = $del->affected_rows;
        $del->close();

        $mysqli->commit();

        $buf = ob_get_clean();
        header('Content-Type: application/json; charset=utf-8');
        $resp = ['success' => true, 'student_id' => $studentId, 'deleted_rows' => $affected];
        if ($EXPOSE_ERRORS && $buf !== '') $resp['debug_output'] = $buf;
        echo json_encode($resp);
        exit;

    } catch (Throwable $e) {
        $mysqli->rollback();
        throw $e;
    }

} catch (Throwable $e) {
    $buf = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
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