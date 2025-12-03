<?php
// Folder: includes/
// File: header.php
// Purpose: Enhanced common header with fixed UI bugs and improved notification bell

if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Dashboard');
}

$current_user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$current_user_photo = $_SESSION['profile_photo'] ? BASE_URL . '/uploads/profiles/' . $_SESSION['profile_photo'] : BASE_URL . '/assets/images/default-avatar.png';
$current_user_role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?> - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        .navbar-dark {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
            height: 56px;
        }
        
        .notification-bell-wrapper {
            position: relative;
        }
        
        .notification-count-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            font-size: 10px;
            font-weight: 600;
            line-height: 18px;
            text-align: center;
            border-radius: 9px;
        }
        
        /* Fix dropdown positioning */
        .navbar .dropdown-menu {
            position: absolute !important;
            top: 100% !important;
            left: auto !important;
            right: 0 !important;
            transform: none !important;
            z-index: 1050 !important;
            margin-top: 0.5rem !important;
        }
        
        .notification-dropdown {
            min-width: 380px;
            max-width: 380px;
            max-height: 500px;
            overflow-y: auto;
            position: absolute !important;
        }
        
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: #f9fafb;
        }
        
        .notification-item.unread {
            background-color: #eff6ff;
        }
        
        body.dark-theme .notification-item.unread {
            background-color: #1e3a5f;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .notification-icon.equipment {
            background-color: #d1fae5;
            color: #10b981;
        }
        
        .notification-icon.network {
            background-color: #dbeafe;
            color: #3b82f6;
        }
        
        .notification-icon.task {
            background-color: #fef3c7;
            color: #f59e0b;
        }
        
        .notification-icon.user {
            background-color: #e0e7ff;
            color: #667eea;
        }
        
        .notification-icon.warranty {
            background-color: #fee2e2;
            color: #ef4444;
        }
        
        body.dark-theme .notification-icon.equipment {
            background-color: #064e3b;
        }
        
        body.dark-theme .notification-icon.network {
            background-color: #1e3a8a;
        }
        
        body.dark-theme .notification-icon.task {
            background-color: #78350f;
        }
        
        body.dark-theme .notification-icon.user {
            background-color: #312e81;
        }
        
        body.dark-theme .notification-icon.warranty {
            background-color: #7f1d1d;
        }
        
        .profile-dropdown-img {
            width: 32px;
            height: 32px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .theme-toggle-btn {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            padding: 0.5rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .theme-toggle-btn:hover {
            color: #fff;
        }
    </style>
</head>
<body>
    <header class="navbar navbar-dark sticky-top flex-md-nowrap p-0 shadow" style="z-index: 1030;">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 py-2" href="<?php echo BASE_URL; ?>">
            <i class="bi bi-lightning-charge-fill me-2"></i><?php echo SYSTEM_NAME; ?>
        </a>
        
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" 
                data-bs-toggle="collapse" data-bs-target="#sidebarMenu" 
                aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation"
                style="top: 10px; right: 10px;">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="navbar-nav flex-row align-items-center ms-auto px-3">
            <!-- Notification Bell -->
            <div class="nav-item dropdown me-3 position-static">
                <a class="nav-link notification-bell-wrapper p-2 position-relative" href="#" 
                   id="notificationDropdown" data-bs-toggle="dropdown" 
                   aria-expanded="false" title="Notifications">
                    <i class="bi bi-bell-fill fs-5"></i>
                    <span class="notification-count-badge badge bg-danger" id="notificationCount" style="display: none;">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown shadow-lg border-0" 
                     aria-labelledby="notificationDropdown">
                    <div class="dropdown-header bg-primary text-white py-3">
                        <strong><i class="bi bi-bell me-2"></i>Notifications</strong>
                    </div>
                    <div id="notificationList">
                        <div class="text-center p-4 text-muted">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 mb-0">Loading notifications...</p>
                        </div>
                    </div>
                    <div class="dropdown-divider m-0"></div>
                    <div class="text-center p-2">
                        <a href="<?php echo BASE_URL; ?>/pages/notifications/list_notifications.php" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-list me-1"></i>View All Notifications
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Theme Toggle -->
            <div class="nav-item me-3">
                <button class="theme-toggle-btn" id="themeToggle" title="Toggle dark mode">
                    <i class="bi bi-moon-stars-fill fs-5" id="themeIcon"></i>
                </button>
            </div>
            
            <!-- Profile Dropdown -->
            <div class="nav-item dropdown position-static">
                <a class="nav-link dropdown-toggle d-flex align-items-center p-2" href="#" 
                   id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?php echo $current_user_photo; ?>" alt="Profile" 
                         class="rounded-circle profile-dropdown-img me-2">
                    <span class="d-none d-lg-inline text-white">
                        <?php echo htmlspecialchars($current_user_name); ?>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" 
                    aria-labelledby="profileDropdown" style="min-width: 250px;">
                    <li class="px-3 py-2 border-bottom">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo $current_user_photo; ?>" alt="Profile" 
                                 class="rounded-circle" width="48" height="48">
                            <div class="ms-2">
                                <div class="fw-bold"><?php echo htmlspecialchars($current_user_name); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($current_user_role); ?></div>
                            </div>
                        </div>
                    </li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/users/profile.php">
                        <i class="bi bi-person me-2"></i>My Profile
                    </a></li>
                    <?php if (is_admin()): ?>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/tools/system_settings.php">
                        <i class="bi bi-gear me-2"></i>Settings
                    </a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </header>
    
    <div class="container-fluid">
        <div class="row">
            <?php include BASE_PATH . '/includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php 
                        echo htmlspecialchars($_SESSION['success_message']); 
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php 
                        echo htmlspecialchars($_SESSION['error_message']); 
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>