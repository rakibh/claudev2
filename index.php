<?php
// File: index.php
// Purpose: Root redirect to login or dashboard based on authentication status

require_once 'config/constants.php';
require_once 'config/session.php';

if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . BASE_URL . '/pages/dashboard_admin.php');
    } else {
        header('Location: ' . BASE_URL . '/pages/dashboard_user.php');
    }
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
?>