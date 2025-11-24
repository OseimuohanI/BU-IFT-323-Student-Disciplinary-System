<?php
// student_update.php
// JSON API to update a student record. Returns detailed error information when $EXPOSE_ERRORS = true.

ini_set('display_startup_errors', '0');
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Set to true while debugging to include exception/DB details in the JSON response.
// Set to false in production.
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
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    }

    // DB fallback if config.php didn't provide $mysqli
    if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
        $db_host = defined('DB_HOST') ? DB_HOST : '127.0.0.1:3305';
        $db_user = defined('DB_USER') ? DB_USER : 'root';
        $db_pass = defined('DB_PASS') ? DB_PASS : '';
        $db_name = defined('DB_NAME') ? DB_NAME : 'student_disciplinary_system';
        $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_errno) throw new RuntimeException('Database connection failed: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');

    // read JSON request
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) throw new InvalidArgumentException('Invalid JSON payload');

    // session + role check
    session_start();
    $currentUser = $_SESSION['user'] ?? null;
    $role = $currentUser['role'] ?? $currentUser['Role'] ?? $currentUser['user_type'] ?? null;
    if (!$currentUser || !$role || !in_array(strtolower($role), ['admin','lecturer'], true)) {
        throw new RuntimeException('Unauthorized');
    }

    // fields
    $studentId = isset($input['student_id']) ? (int)$input['student_id'] : 0;
    $enrollment = trim((string)($input['enrollment_no'] ?? ''));
    $first = trim((string)($input['first_name'] ?? ''));
    $last  = trim((string)($input['last_name'] ?? ''));
    $dob   = trim((string)($input['dob'] ?? ''));
    $gender= trim((string)($input['gender'] ?? ''));
    $phone = trim((string)($input['phone'] ?? ''));
    $email = trim((string)($input['email'] ?? ''));

    if ($studentId <= 0) throw new InvalidArgumentException('Invalid student_id');
    if ($first === '' || $last === '') throw new InvalidArgumentException('First name and last name are required');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid email address');

    // Optional: ensure enrollment uniqueness if changed
    if ($enrollment !== '') {
        $chk = $mysqli->prepare("SELECT StudentID FROM student WHERE EnrollmentNo = ? LIMIT 1");
        if (! $chk) throw new RuntimeException('Prepare failed: ' . $mysqli->error);
        $chk->bind_param('s', $enrollment);
        $chk->execute();
        $chk->bind_result($existingId);
        if ($chk->fetch() && (int)$existingId !== $studentId) {
            $chk->close();
            throw new RuntimeException('Enrollment number already in use by another student');
        }
        $chk->close();
    }

    // perform update
    $stmt = $mysqli->prepare("UPDATE student SET EnrollmentNo = ?, FirstName = ?, LastName = ?, DOB = ?, Gender = ?, Phone = ?, Email = ? WHERE StudentID = ?");
    if (! $stmt) throw new RuntimeException('Prepare failed: ' . $mysqli->error);
    $stmt->bind_param('sssssssi', $enrollment, $first, $last, $dob, $gender, $phone, $email, $studentId);
    if (! $stmt->execute()) {
        $err = $stmt->error ?: 'Execute failed';
        $stmt->close();
        throw new RuntimeException('Update failed: ' . $err);
    }
    $affected = $stmt->affected_rows;
    $stmt->close();

    $buf = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    $resp = ['success' => true, 'student_id' => $studentId, 'affected_rows' => $affected];
    if ($EXPOSE_ERRORS && $buf !== '') $resp['debug_output'] = $buf;
    echo json_encode($resp);
    exit;

} catch (Throwable $e) {
    $buf = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    $payload = [
        'success' => false,
        'message' => $e->getMessage()
    ];
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