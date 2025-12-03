<?php
// Folder: pages/
// File: dashboard_user.php
// Purpose: Standard user dashboard with personal statistics

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

require_login();

define('PAGE_TITLE', 'Dashboard');

// Get user statistics
$user_id = get_current_user_id();

// My tasks
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM task_assignments ta 
                       JOIN tasks t ON ta.task_id = t.id 
                       WHERE ta.user_id = ? AND t.status != 'Completed' AND t.status != 'Cancelled'");
$stmt->execute([$user_id]);
$my_tasks = $stmt->fetch()['total'];

// My completed tasks
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM task_assignments ta 
                       JOIN tasks t ON ta.task_id = t.id 
                       WHERE ta.user_id = ? AND t.status = 'Completed'");
$stmt->execute([$user_id]);
$completed_tasks = $stmt->fetch()['total'];

// Tasks created by me
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks WHERE created_by = ?");
$stmt->execute([$user_id]);
$created_tasks = $stmt->fetch()['total'];

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">My Active Tasks</h6>
                        <h2 class="mb-0"><?php echo $my_tasks; ?></h2>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-check2-square fs-1"></i>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/pages/tasks/list_tasks.php" class="btn btn-sm btn-outline-primary mt-2 w-100">View Tasks</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Completed Tasks</h6>
                        <h2 class="mb-0"><?php echo $completed_tasks; ?></h2>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-check-circle fs-1"></i>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/pages/tasks/list_tasks.php?filter=completed" class="btn btn-sm btn-outline-success mt-2 w-100">View All</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Tasks Created</h6>
                        <h2 class="mb-0"><?php echo $created_tasks; ?></h2>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-plus-circle fs-1"></i>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/pages/tasks/list_tasks.php?filter=created" class="btn btn-sm btn-outline-info mt-2 w-100">View All</a>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Quick Actions</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-2">
                <a href="<?php echo BASE_URL; ?>/pages/tasks/add_task.php" class="btn btn-outline-primary w-100">
                    <i class="bi bi-plus-circle me-2"></i>Create Task
                </a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="<?php echo BASE_URL; ?>/pages/equipment/add_equipment.php" class="btn btn-outline-success w-100">
                    <i class="bi bi-plus-circle me-2"></i>Add Equipment
                </a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="<?php echo BASE_URL; ?>/pages/network/add_network_info.php" class="btn btn-outline-info w-100">
                    <i class="bi bi-plus-circle me-2"></i>Add Network Info
                </a>
            </div>
            <div class="col-md-3 mb-2">
                <a href="<?php echo BASE_URL; ?>/pages/users/profile.php" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-person me-2"></i>My Profile
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>