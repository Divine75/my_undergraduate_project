<?php
// includes/auth.php - Session and Access Control

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dynamically determine the base URL path
$script_name = $_SERVER['SCRIPT_NAME'];
$base_path = str_replace(basename($script_name), '', $script_name);
// Ensure we get the root folder path rather than subdirectories
if (strpos($base_path, '/admin/') !== false) {
    $base_path = str_replace('/admin/', '/', $base_path);
}
if (!defined('BASE_URL')) {
    define('BASE_URL', $base_path);
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login($allowed_roles = []) {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
    
    if (!empty($allowed_roles)) {
        $user_role = $_SESSION['user_role'] ?? '';
        if (!in_array($user_role, $allowed_roles)) {
            // Log this unauthorized attempt
            if (function_exists('log_audit')) {
                log_audit('unauthorized_access', "Attempted to access restricted page as " . $user_role);
            }
            // Display clean royal warning
            echo "<!DOCTYPE html><html><head><title>Access Denied</title>";
            echo "<link href='https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap' rel='stylesheet'>";
            echo "<style>body{font-family:'Outfit',sans-serif;background:#081525;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}";
            echo ".box{background:#0F3057;padding:40px;border-radius:12px;border:1px solid #D4AF37;text-align:center;max-width:500px;box-shadow:0 10px 30px rgba(0,0,0,0.5);}";
            echo "h1{color:#D4AF37;margin-top:0;} a{color:#D4AF37;text-decoration:none;font-weight:600;} a:hover{text-decoration:underline;}</style></head>";
            echo "<body><div class='box'><h1>Access Restricted</h1><p>You do not have the required permissions to access this module.</p>";
            echo "<p><a href='" . BASE_URL . "admin/index.php'>Back to Dashboard</a></p></div></body></html>";
            exit;
        }
    }
}
?>
