<?php
// Folder: includes/
// File: header.php
// Purpose: Common header for all authenticated pages with notification bell

if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Dashboard');
}

$current_user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$current_user_photo = $_SESSION['profile_photo'] ? '/uploads/profiles/' . $_SESSION['profile_photo'] : '/assets/images/default-avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?> - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="navbar navbar-dark bg-dark sticky-top flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="/">
            <?php echo SYSTEM_NAME; ?>
        </a>
        
        <div class="navbar-nav flex-row ms-auto">
            <!-- Notification Bell -->
            <div class="nav-item dropdown me-3">
                <a class="nav-link position-relative" href="#" id="notificationDropdown" 
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell-fill fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                          id="notificationCount" style="display: none;">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown p-0" 
                     aria-labelledby="notificationDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
                    <div class="dropdown-header bg-primary text-white">
                        <strong>Notifications</strong>
                    </div>
                    <div id="notificationList" class="list-group list-group-flush">
                        <div class="text-center p-3 text-muted">Loading...</div>
                    </div>
                    <div class="dropdown-footer text-center border-top p-2">
                        <a href="/pages/notifications/list_notifications.php" class="small">View All</a>
                    </div>
                </div>
            </div>
            
            <!-- Theme Toggle -->
            <div class="nav-item me-3">
                <button class="btn btn-link nav-link" id="themeToggle" title="Toggle theme">
                    <i class="bi bi-moon-stars-fill fs-5" id="themeIcon"></i>
                </button>
            </div>
            
            <!-- Profile Dropdown -->
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle px-3" href="#" id="profileDropdown" 
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?php echo $current_user_photo; ?>" alt="Profile" 
                         class="rounded-circle" width="32" height="32">
                    <span class="ms-2 d-none d-md-inline"><?php echo htmlspecialchars($current_user_name); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="/pages/users/profile.php">
                        <i class="bi bi-person me-2"></i>My Profile
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </header>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo htmlspecialchars($_SESSION['success_message']); 
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo htmlspecialchars($_SESSION['error_message']); 
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>