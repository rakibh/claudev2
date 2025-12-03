<?php
// File: logout.php
// Purpose: Logout functionality with session destruction and cache clearing

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

// Log logout activity
if (is_logged_in()) {
    $user_id = get_current_user_id();
    $username = $_SESSION['username'] ?? 'Unknown';
    log_system($pdo, 'INFO', "User '{$username}' logged out", $user_id);
}

// Destroy session
logout();

// Clear browser cache headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Redirect to login
header('Location: ' . BASE_URL . '/login.php');
exit;
?>