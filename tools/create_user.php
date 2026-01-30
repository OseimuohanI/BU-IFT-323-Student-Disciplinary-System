<?php
// CLI helper to create a user with a hashed password.
// Usage (Windows): php tools\create_user.php username "plain-password" "Full Name" "role"
if (php_sapi_name() !== 'cli') {
    echo "Run this from CLI\n";
    exit;
}
if ($argc < 3) {
    echo "Usage: php create_user.php username password [Full Name] [role]\n";
    exit;
}
$username = $argv[1];
$password = $argv[2];
$fullname = $argv[3] ?? '';
$role = $argv[4] ?? '';

$hash = password_hash($password, PASSWORD_DEFAULT);

require_once __DIR__ . '/../config/config.php'; // adjust path if needed
$db_host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
$db_user = defined('DB_USER') ? DB_USER : 'root';
$db_pass = defined('DB_PASS') ? DB_PASS : 'Aresthe1st';
$db_name = defined('DB_NAME') ? DB_NAME : 'student_disciplinary_system';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    echo "DB connect error: " . $mysqli->connect_error . "\n";
    exit;
}
$stmt = $mysqli->prepare('INSERT INTO users (Username, PasswordHash, FullName, Role) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $username, $hash, $fullname, $role);
if ($stmt->execute()) {
    echo "User created: $username\n";
} else {
    echo "Insert failed: " . $stmt->error . "\n";
}
$stmt->close();
$mysqli->close();