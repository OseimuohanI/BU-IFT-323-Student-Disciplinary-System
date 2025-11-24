<?php
// student_create.php
// JSON API: create student and return detailed errors when something goes wrong.
// Set $EXPOSE_ERRORS = true to include detailed error/debug output in the JSON response.

ini_set('display_startup_errors', '0');
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Expose full errors in JSON (set to false to hide stack/DB details)
$EXPOSE_ERRORS = true;

// buffer all output so accidental HTML/warnings can be captured and returned
ob_start();

// convert PHP warnings/notices into exceptions so we can catch them
set_error_handler(function($severity, $message, $file, $line) {
    // Respect @ operator
    if (error_reporting() === 0) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// catch fatal errors on shutdown
register_shutdown_function(function() use ($EXPOSE_ERRORS) {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $buf = ob_get_clean();
        header('Content-Type: application/json; charset=utf-8');
        $payload = [
            'success' => false,
            'message' => 'Fatal error',
        ];
        if ($EXPOSE_ERRORS) {
            $payload['error'] = $err;
            if ($buf !== '') $payload['debug_output'] = $buf;
        }
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
        if ($mysqli->connect_errno) {
            throw new RuntimeException('Database connection failed: ' . $mysqli->connect_error);
        }
    }
    $mysqli->set_charset('utf8mb4');

    // read JSON request
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        throw new InvalidArgumentException('Invalid JSON payload');
    }

    // session + role check
    session_start();
    $currentUser = $_SESSION['user'] ?? null;
    $role = $currentUser['role'] ?? $currentUser['Role'] ?? $currentUser['user_type'] ?? null;
    if (!$currentUser || !$role || !in_array(strtolower($role), ['admin','lecturer'], true)) {
        throw new RuntimeException('Unauthorized');
    }

    // gather inputs
    $enrollment = trim((string)($input['enrollment_no'] ?? ''));
    $first = trim((string)($input['first_name'] ?? ''));
    $last  = trim((string)($input['last_name'] ?? ''));
    $dob   = trim((string)($input['dob'] ?? ''));
    $gender= trim((string)($input['gender'] ?? ''));
    $phone = trim((string)($input['phone'] ?? ''));
    $email = trim((string)($input['email'] ?? ''));

    if ($first === '' || $last === '') {
        throw new InvalidArgumentException('First name and last name are required');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email address');
    }

    // compute enrollment if empty
    if ($enrollment === '') {
        $enRes = $mysqli->query("SELECT MAX(CAST(SUBSTRING(EnrollmentNo,3) AS UNSIGNED)) AS maxnum FROM student WHERE EnrollmentNo REGEXP '^EN[0-9]+$'");
        if ($enRes) {
            $r = $enRes->fetch_assoc();
            $maxnum = (int)($r['maxnum'] ?? 0);
            $num = $maxnum + 1;
            $enrollment = 'EN' . str_pad($num, 5, '0', STR_PAD_LEFT);
            $enRes->free();
        } else {
            throw new RuntimeException('Failed to compute next enrollment: ' . $mysqli->error);
        }
    }

    // ensure unique enrollment
    $stmt = $mysqli->prepare("SELECT 1 FROM student WHERE EnrollmentNo = ? LIMIT 1");
    if (! $stmt) {
        throw new RuntimeException('Prepare failed: ' . $mysqli->error);
    }
    $stmt->bind_param('s', $enrollment);
    if (! $stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Failed to check enrollment uniqueness: ' . $stmt->error);
    }
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        throw new RuntimeException('Enrollment number already exists');
    }
    $stmt->close();

    // --- compute next StudentID and insert safely inside a transaction ---
$nextId = null;
$insert = null;
$mysqli->begin_transaction();

try {
    // lock table rows to avoid race conditions (requires InnoDB)
    $res = $mysqli->query("SELECT MAX(StudentID) AS maxid FROM student FOR UPDATE");
    if ($res === false) {
        throw new RuntimeException('Failed to get max StudentID: ' . $mysqli->error);
    }
    $r = $res->fetch_assoc();
    $maxid = (int)($r['maxid'] ?? 0);
    $nextId = $maxid + 1;
    $res->free();

    $insert = $mysqli->prepare(
        "INSERT INTO student (StudentID, EnrollmentNo, FirstName, LastName, DOB, Gender, Phone, Email, CreatedAt)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    if (! $insert) {
        throw new RuntimeException('Prepare failed: ' . $mysqli->error);
    }

    $insert->bind_param('isssssss', $nextId, $enrollment, $first, $last, $dob, $gender, $phone, $email);
    if (! $insert->execute()) {
        $err = $insert->error ?: 'Insert failed';
        $insert->close();
        throw new RuntimeException($err);
    }
    $insert->close();

    $mysqli->commit();

    // success response (existing code expects $studentId)
    $studentId = $nextId;

} catch (Throwable $e) {
    $mysqli->rollback();
    // rethrow so outer catch will return JSON error as before
    throw $e;
}

    // capture any buffered output for debugging
    $buf = ob_get_clean();

    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => true, 'student_id' => (int)$studentId, 'enrollment_no' => $enrollment];
    if ($EXPOSE_ERRORS && $buf !== '') $response['debug_output'] = $buf;
    echo json_encode($response);
    exit;

} catch (Throwable $e) {
    // get and clear buffer (captured output, warnings, etc.)
    $buf = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');

    $payload = [
        'success' => false,
        'message' => $e->getMessage(),
    ];

    if ($EXPOSE_ERRORS) {
        $payload['type'] = get_class($e);
        $payload['file'] = $e->getFile();
        $payload['line'] = $e->getLine();
        $payload['trace'] = $e->getTraceAsString();
        if ($buf !== '') $payload['debug_output'] = $buf;
        // include mysqli error if available
        if (isset($mysqli) && ($mysqli instanceof mysqli) && $mysqli->error) {
            $payload['mysqli_error'] = $mysqli->error;
        }
    }

    echo json_encode($payload);
    exit;
}
?>