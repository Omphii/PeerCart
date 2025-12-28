<?php
// controllers/logout.php
session_start();

// Clear all session data
$_SESSION = [];

// If it's desired to kill the session, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a new session for flash message
session_start();
$_SESSION['flash_message'] = [
    'type' => 'success',
    'text' => 'You have been logged out successfully.'
];

// Use absolute path to home
header('Location: /Peer-Cart/');
exit;
?>