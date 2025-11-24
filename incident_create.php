<?php
// Robust JSON endpoint for creating incidents.
// Always returns valid JSON and never emits stray HTML/PHP warnings.

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    session_start();
    if (empty($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }

    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    }
    $db_host = defined('DB_HOST') ? DB_HOST : '127.0.0.1:3305';
    $db_user = defined('DB_USER') ? DB_USER : 'root';
    $db_pass = defined('DB_PASS') ? DB_PASS : '';
    $db_name = defined('DB_NAME') ? DB_NAME : 'student_disciplinary_system';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_errno) {
        http_response_code(500);
        error_log('DB connect error: ' . $mysqli->connect_error);
        echo json_encode(['success' => false, 'message' => 'DB connection failed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    $report_date = trim($input['report_date'] ?? '');
    $location = trim($input['location'] ?? '');
    $reporter_id = (int)($input['reporter_id'] ?? 0);
    $student_id = (int)($input['student_id'] ?? 0);
    $description = trim($input['description'] ?? '');
    $status = trim($input['status'] ?? 'Pending');

    if ($report_date === '' || $location === '' || $reporter_id <= 0 || $student_id <= 0 || $description === '') {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Accept datetime-local (YYYY-MM-DDTHH:MM) or full datetime
    $report_date = str_replace('T', ' ', $report_date);
    $ts = strtotime($report_date);
    if ($ts === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }
    $report_date = date('Y-m-d H:i:s', $ts);

    // start transaction and allocate an IncidentID in a safe loop
    $maxAttempts = 5;
    $attempt = 0;
    $insertId = null;

    while ($attempt < $maxAttempts) {
        $attempt++;
        if (! $mysqli->begin_transaction()) {
            error_log('Failed to start transaction: ' . $mysqli->error);
            echo json_encode(['success' => false, 'message' => 'Server error']);
            exit;
        }

        // lock and get current max
        $res = $mysqli->query("SELECT MAX(IncidentID) AS maxid FROM incidentreport FOR UPDATE");
        if ($res === false) {
            $mysqli->rollback();
            error_log('Select max failed: ' . $mysqli->error);
            echo json_encode(['success' => false, 'message' => 'Server error']);
            exit;
        }
        $row = $res->fetch_assoc();
        $res->free();
        $nextId = ((int)($row['maxid'] ?? 0)) + 1;

        $stmt = $mysqli->prepare("INSERT INTO incidentreport (IncidentID, ReportDate, Location, ReporterStaffID, StudentID, Description, Status, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if (! $stmt) {
            $mysqli->rollback();
            error_log('Prepare failed: ' . $mysqli->error);
            echo json_encode(['success' => false, 'message' => 'Server error']);
            exit;
        }

        // types: i = IncidentID, s = ReportDate, s = Location, i = ReporterStaffID, i = StudentID, s = Description, s = Status
        $stmt->bind_param('issiiss', $nextId, $report_date, $location, $reporter_id, $student_id, $description, $status);

        if ($stmt->execute()) {
            $insertId = $nextId;
            $stmt->close();
            $mysqli->commit();
            break;
        }

        // handle duplicate key by retrying (possible concurrent insert)
        $errno = $stmt->errno;
        $stmt->close();
        $mysqli->rollback();

        if ($errno === 1062 && $attempt < $maxAttempts) {
            // small sleep to reduce tight loop on busy systems
            usleep(50000);
            continue;
        }

        error_log('Insert failed: ' . $mysqli->error . ' (errno ' . $errno . ')');
        echo json_encode(['success' => false, 'message' => 'Insert failed']);
        exit;
    } // end loop

    if ($insertId === null) {
        echo json_encode(['success' => false, 'message' => 'Could not allocate IncidentID']);
        exit;
    }

    echo json_encode(['success' => true, 'incident_id' => (int)$insertId]);
    exit;

} catch (Throwable $e) {
    // TEMP DEBUG: return error details (remove in production)
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'debug' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    error_log('incident_create exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    exit;
}