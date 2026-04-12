<?php
// /php/auth/logout.php
header('Content-Type: application/json; charset=utf-8');
// Allow both GET and POST (JS fetch uses POST, direct links use GET)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $p["path"],
        $p["domain"],
        $p["secure"],
        $p["httponly"]
    );
}

session_destroy();

echo json_encode(['success' => true]);