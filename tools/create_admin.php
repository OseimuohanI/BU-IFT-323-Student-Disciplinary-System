<?php
// CLI helper to create an admin user with a hashed password.
// Usage: php tools/create_admin.php username password [Full Name]

if (php_sapi_name() !== 'cli') {
    echo "Run this from CLI\n";
    exit(1);
}

if ($argc < 3) {
    echo "Usage: php create_admin.php username password [Full Name]\n";
    echo "Example: php create_admin.php admin secure123 'Admin User'\n";
    exit(1);
}

$username = $argv[1];
$password = $argv[2];
$fullname = $argv[3] ?? 'Admin User';

// Validate input
if (empty($username) || empty($password)) {
    echo "Error: Username and password are required\n";
    exit(1);
}

if (strlen($password) < 6) {
    echo "Error: Password must be at least 6 characters\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// Load configuration
require_once __DIR__ . '/../config/config.php';
$db_host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
$db_user = defined('DB_USER') ? DB_USER : 'root';
$db_pass = defined('DB_PASS') ? DB_PASS : '';
$db_name = defined('DB_NAME') ? DB_NAME : 'student_disciplinary_system';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    echo "DB connect error: " . $mysqli->connect_error . "\n";
    exit(1);
}

$mysqli->set_charset('utf8mb4');

// Check if user already exists
$stmt = $mysqli->prepare('SELECT UserID FROM users WHERE Username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    echo "Error: User '$username' already exists\n";
    $stmt->close();
    $mysqli->close();
    exit(1);
}
$stmt->close();

// Create admin user
$role = 'admin';
$stmt = $mysqli->prepare('INSERT INTO users (Username, PasswordHash, FullName, Role) VALUES (?, ?, ?, ?)');
if (!$stmt) {
    echo "Prepare failed: " . $mysqli->error . "\n";
    exit(1);
}
$stmt->bind_param('ssss', $username, $hash, $fullname, $role);
if ($stmt->execute()) {
    echo "✓ Admin user created successfully\n";
    echo "  Username: $username\n";
    echo "  Full Name: $fullname\n";
    echo "  Role: admin\n";
} else {
    echo "Error: Insert failed: " . $stmt->error . "\n";
    exit(1);
}
$stmt->close();
$mysqli->close();
exit(0);
