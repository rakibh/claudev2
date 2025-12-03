<?php
// Folder: pages/
// File: dashboard_admin.php
// Purpose: Admin dashboard with system statistics and quick links

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';

require_admin();

define('PAGE_TITLE', 'Admin Dashboard');

// Get statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'Active'");
$stats['total_users'] = $stmt->fetch()['total'];

// Total equipment
$stmt = $pdo->query("SELECT COUNT(*) as total FROM equipments");
$stats['total_equipment'] = $stmt->fetch()['total'];

// Total tasks
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tasks WHERE status != 'Completed' AND status != 'Cancelled'");
$stats['pending_tasks'] = $stmt->fetch()['total'];

// Total network entries
$stmt = $pdo->query("SELECT COUNT(*) as total FROM network_info");
$stats['total_network'] = $stmt->fetch()['total'];

// Overdue tasks
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tasks WHERE due_date < NOW() AND status NOT IN ('Completed', 'Cancelled')");
$stats['overdue_tasks'] = $stmt->fetch()['total'];

// Warranty expiring soon (30 days)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM equipments WHERE warranty_expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)");
$stats['expiring_warranties'] = $stmt->fetch()['total'];

// Recent activities
$stmt = $pdo->prepare("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$recent_logs = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Admin Dashboard</h1>
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
    <div class="col-md-3 mb-3">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Active Users</h6>
                        <h2 class="mb-0"><?php echo $stats['total_users']; ?></h2>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-people fs-1"></i>
                    </div>
                </div>
                <a href="/pages/users/list_users.php" class="btn btn-sm btn-outline-primary mt-2 w-100">View All</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Equipment</h6>
                        <h2 class="mb-0"><?php echo $stats['total_equipment']; ?></h2>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-pc-display fs-1"></i>
                    </div>
                </div>
                <a href="/pages/equipment/list_equipment.php" class="btn btn-sm btn-outline-success mt-2 w-100">View All</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Pending Tasks</h6>
                        <h2 class="mb-0"><?php echo $stats['pending_tasks']; ?></h2>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-check2-square fs-1"></i>
                    </div>
                </div>
                <a href="/pages/tasks/list_tasks.php" class="btn btn-sm btn-outline-warning mt-2 w-100">View All</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Network Entries</h6>
                        <h2 class="mb-0"><?php echo $stats['total_network']; ?></h2>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-hdd-network fs-1"></i>
                    </div>
                </div>
                <a href="/pages/network/list_network_info.php" class="btn btn-sm btn-outline-info mt-2 w-100">View All</a>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if ($stats['overdue_tasks'] > 0): ?>
<div class="alert alert-danger" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Warning:</strong> You have <?php echo $stats['overdue_tasks']; ?> overdue tasks.
    <a href="/pages/tasks/list_tasks.php?filter=overdue" class="alert-link">View overdue tasks</a>
</div>
<?php endif; ?>

<?php if ($stats['expiring_warranties'] > 0): ?>
<div class="alert alert-warning" role="alert">
    <i class="bi bi-exclamation-circle-fill me-2"></i>
    <strong>Notice:</strong> <?php echo $stats['expiring_warranties']; ?> equipment warranties expiring in 30 days.
    <a href="/pages/equipment/list_equipment.php?filter=warranty_expiring" class="alert-link">View equipment</a>
</div>
<?php endif; ?>

<!-- Recent Activity -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Recent System Activity</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Level</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_logs as $log): ?>
                    <tr>
                        <td class="text-nowrap"><?php echo format_datetime($log['created_at']); ?></td>
                        <td>
                            <?php
                            $badge_class = 'secondary';
                            if ($log['log_level'] === 'ERROR') $badge_class = 'danger';
                            elseif ($log['log_level'] === 'WARNING') $badge_class = 'warning';
                            elseif ($log['log_level'] === 'INFO') $badge_class = 'info';
                            ?>
                            <span class="badge bg-<?php echo $badge_class; ?>"><?php echo $log['log_level']; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($log['message']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-center">
        <a href="/pages/tools/system_logs.php" class="btn btn-sm btn-outline-secondary">View All Logs</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>