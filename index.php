<?php
// File: index.php
// Purpose: Root redirect to login or dashboard based on authentication status

require_once 'config/session.php';

if (is_logged_in()) {
    if (is_admin()) {
        header('Location: pages/dashboard_admin.php');
    } else {
        header('Location: pages/dashboard_user.php');
    }
} else {
    header('Location: login.php');
}
exit;
?>