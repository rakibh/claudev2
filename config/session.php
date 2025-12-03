<?php
// Folder: config/
// File: session.php
// Purpose: Session management with security settings

// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Session lifetime: 0 means session ends when browser closes
ini_set('session.gc_maxlifetime', 0);
ini_set('session.cookie_lifetime', 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['employee_id']);
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
}

// Redirect to login if not authenticated
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// Redirect to login if not admin
function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: ' . BASE_URL . '/pages/dashboard_user.php');
        exit;
    }
}

// Get current user ID
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user employee ID
function get_current_employee_id() {
    return $_SESSION['employee_id'] ?? null;
}

// Get current user role
function get_current_user_role() {
    return $_SESSION['role'] ?? null;
}

// Logout function
function logout() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}
?>