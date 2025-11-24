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
    if (empty($_SESSION['user']['UserID'])) {
        http_response_code(401);
        echo json_encode(['success'=>false,'message'=>'Authentication required']);
        exit;
    }
    $currentUserId = (int)$_SESSION['user']['UserID'];

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
    $password = (string)($input['password'] ?? '');

    if ($incident_id <= 0 || $password === '') {
        echo json_encode(['success'=>false,'message'=>'Missing required fields']);
        exit;
    }

    // verify password for current session user
    $stmt = $mysqli->prepare("SELECT PasswordHash FROM users WHERE UserID = ? LIMIT 1");
    if (!$stmt) { error_log('Prepare failed: '.$mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }
    $stmt->bind_param('i', $currentUserId);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['PasswordHash'])) {
        echo json_encode(['success'=>false,'message'=>'Invalid password']);
        $mysqli->close();
        exit;
    }

    // perform delete
    $stmt = $mysqli->prepare("DELETE FROM incidentreport WHERE IncidentID = ? LIMIT 1");
    if (!$stmt) { error_log('Prepare delete failed: '.$mysqli->error); echo json_encode(['success'=>false,'message'=>'Server error']); exit; }
    $stmt->bind_param('i', $incident_id);
    if (!$stmt->execute()) {
        error_log('Delete failed: '.$stmt->error);
        echo json_encode(['success'=>false,'message'=>'Delete failed']);
        $stmt->close();
        $mysqli->close();
        exit;
    }
    $deleted = $stmt->affected_rows;
    $stmt->close();
    $mysqli->close();

    if ($deleted <= 0) {
        echo json_encode(['success'=>false,'message'=>'Incident not found']);
        exit;
    }

    echo json_encode(['success'=>true,'deleted'=>$deleted]);
    exit;

} catch (Throwable $e) {
    error_log('incident_delete exception: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
    exit;
}