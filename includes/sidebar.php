<?php
// Folder: includes/
// File: sidebar.php
// Purpose: Navigation sidebar with role-based menu items
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3 sidebar-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'dashboard') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/pages/<?php echo is_admin() ? 'dashboard_admin' : 'dashboard_user'; ?>.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <?php if (is_admin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'users') !== false && strpos($_SERVER['PHP_SELF'], 'profile') === false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/pages/users/list_users.php">
                    <i class="bi bi-people me-2"></i>
                    Users
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'tasks') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/pages/tasks/list_tasks.php">
                    <i class="bi bi-check2-square me-2"></i>
                    Tasks
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'equipment') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/pages/equipment/list_equipment.php">
                    <i class="bi bi-pc-display me-2"></i>
                    Equipment
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'network') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/pages/network/list_network_info.php">
                    <i class="bi bi-hdd-network me-2"></i>
                    Network
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'notifications') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/pages/notifications/list_notifications.php">
                    <i class="bi bi-bell me-2"></i>
                    Notifications
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>System</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <?php if (is_admin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'tools') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>/pages/tools/system_settings.php">
                    <i class="bi bi-gear me-2"></i>
                    Settings & Tools
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>/logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>