<?php
// logout.php - Session Destroy Handler

require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (is_logged_in()) {
    log_audit('logout', 'User logged out successfully');
}

// Clear session variables
$_SESSION = [];

// Destroy session cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

header("Location: login.php");
exit;
?>
