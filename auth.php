<?php
// Simple JSON-based login endpoint.
// Expects JSON body: { "username": "...", "password": "..." }
// On success sets $_SESSION['user'] and returns {"success":true}

session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if ($username === '' || $password === '') {
    echo json_encode(['success'=>false,'message'=>'Username and password required']);
    exit;
}

// reuse config.php or fallback connection
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    $mysqli = new mysqli('127.0.0.1:3305', 'root', '', 'student_disciplinary_system');
    if ($mysqli->connect_errno) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
}

$stmt = $mysqli->prepare('SELECT UserID, Username, FullName, PasswordHash, Role FROM users WHERE Username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (! $user || ! password_verify($password, $user['PasswordHash'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

// set session user (avoid storing sensitive info)
$_SESSION['user'] = [
    'UserID' => (int)$user['UserID'],
    'Username' => $user['Username'],
    'FullName' => $user['FullName'],
    'role' => $user['Role']
];

// return role so client can redirect appropriately
echo json_encode([
    'success' => true,
    'message' => 'Authenticated',
    'role' => $user['Role']
]);
exit;