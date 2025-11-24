<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors','0');
error_reporting(E_ALL);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        exit;
    }

    session_start();
    if (empty($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['success'=>false,'message'=>'Authentication required']);
        exit;
    }

    if (file_exists(__DIR__ . '/config.php')) require_once __DIR__ . '/config.php';
    $db_host = defined('DB_HOST') ? DB_HOST : '127.0.0.1:3305';
    $db_user = defined('DB_USER') ? DB_USER : 'root';
    $db_pass = defined('DB_PASS') ? DB_PASS : '';
    $db_name = defined('DB_NAME') ? DB_NAME : 'student_disciplinary_system';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_errno) {
        error_log('DB connect: '.$mysqli->connect_error);
        echo json_encode(['success'=>false,'message'=>'DB connection failed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { echo json_encode(['success'=>false,'message'=>'Invalid JSON']); exit; }

    $incident_id = (int)($input['incident_id'] ?? 0);
    $report_date = trim($input['report_date'] ?? '');
    $location = trim($input['location'] ?? '');
    $reporter_id = (int)($input['reporter_id'] ?? 0);
    $student_id = (int)($input['student_id'] ?? 0);
    $description = trim($input['description'] ?? '');
    $status = trim($input['status'] ?? '');

    if ($incident_id <= 0 || $report_date === '' || $location === '' || $reporter_id <= 0 || $student_id <= 0 || $description === '') {
        echo json_encode(['success'=>false,'message'=>'Missing required fields']);
        exit;
    }

    $report_date = str_replace('T',' ',$report_date);
    $ts = strtotime($report_date);
    if ($ts === false) { echo json_encode(['success'=>false,'message'=>'Invalid date']); exit; }
    $report_date = date('Y-m-d H:i:s',$ts);

    $stmt = $mysqli->prepare("UPDATE incidentreport SET ReportDate=?, Location=?, ReporterStaffID=?, StudentID=?, Description=?, Status=? WHERE IncidentID=?");
    if (!$stmt) { error_log('Prepare failed: '.$mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }

    $stmt->bind_param('ssisssi', $report_date, $location, $reporter_id, $student_id, $description, $status, $incident_id);
    if (!$stmt->execute()) {
        error_log('Update failed: '.$stmt->error);
        echo json_encode(['success'=>false,'message'=>'Update failed']);
        $stmt->close();
        $mysqli->close();
        exit;
    }

    $affected = $stmt->affected_rows;
    $stmt->close();
    $mysqli->close();

    echo json_encode(['success'=>true,'affected'=>$affected]);
    exit;

} catch (Throwable $e) {
    error_log('incident_update exception: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
    exit;
}