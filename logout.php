<?php
// Logout endpoint — accept POST only to avoid accidental GET navigations.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

session_start();
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    // setcookie signature: name, value, expire, path, domain, secure, httponly
    setcookie(session_name(), '', time() - 42000,
        $params['path'] ?? '/', $params['domain'] ?? '',
        $params['secure'] ?? false, $params['httponly'] ?? false
    );
}

session_destroy();
header('Location: index.php');
exit;