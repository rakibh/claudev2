<?php
// Folder: pages/
// File: dashboard_admin.php
// Purpose: Enhanced admin dashboard with charts and improved UI

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

require_admin();

define('PAGE_TITLE', 'Admin Dashboard');

// Get statistics
$stats = [];

// Total and active users
$stmt = $pdo->query("SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active 
    FROM users");
$user_stats = $stmt->fetch();
$stats['total_users'] = $user_stats['total'];
$stats['active_users'] = $user_stats['active'];

// Equipment statistics
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'In Use' THEN 1 ELSE 0 END) as in_use,
    SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN status = 'Under Repair' THEN 1 ELSE 0 END) as under_repair
    FROM equipments");
$equip_stats = $stmt->fetch();
$stats['total_equipment'] = $equip_stats['total'];
$stats['equipment_in_use'] = $equip_stats['in_use'];
$stats['equipment_available'] = $equip_stats['available'];
$stats['equipment_under_repair'] = $equip_stats['under_repair'];

// Task statistics
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Started' THEN 1 ELSE 0 END) as started,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN due_date < NOW() AND status NOT IN ('Completed', 'Cancelled') THEN 1 ELSE 0 END) as overdue
    FROM tasks");
$task_stats = $stmt->fetch();
$stats['total_tasks'] = $task_stats['total'];
$stats['pending_tasks'] = $task_stats['pending'];
$stats['started_tasks'] = $task_stats['started'];
$stats['completed_tasks'] = $task_stats['completed'];
$stats['overdue_tasks'] = $task_stats['overdue'];

// Network statistics
$stmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN equipment_id IS NOT NULL THEN 1 ELSE 0 END) as assigned
    FROM network_info");
$network_stats = $stmt->fetch();
$stats['total_network'] = $network_stats['total'];
$stats['network_assigned'] = $network_stats['assigned'];
$stats['network_unassigned'] = $network_stats['total'] - $network_stats['assigned'];

// Warranty statistics
$stmt = $pdo->query("SELECT 
    SUM(CASE WHEN warranty_expiry_date < NOW() THEN 1 ELSE 0 END) as expired,
    SUM(CASE WHEN warranty_expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon
    FROM equipments WHERE warranty_expiry_date IS NOT NULL");
$warranty_stats = $stmt->fetch();
$stats['warranty_expired'] = $warranty_stats['expired'];
$stats['warranty_expiring'] = $warranty_stats['expiring_soon'];

// Equipment by type (for chart)
$stmt = $pdo->query("SELECT et.type_name, COUNT(e.id) as count 
    FROM equipment_types et 
    LEFT JOIN equipments e ON et.id = e.type_id 
    GROUP BY et.id, et.type_name 
    ORDER BY count DESC 
    LIMIT 10");
$equipment_by_type = $stmt->fetchAll();

// Tasks by status (for chart)
$task_chart_data = [
    'Pending' => $stats['pending_tasks'],
    'Started' => $stats['started_tasks'],
    'Completed' => $stats['completed_tasks'],
    'Cancelled' => $task_stats['cancelled']
];

// Recent activities
$stmt = $pdo->prepare("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 15");
$stmt->execute();
$recent_logs = $stmt->fetchAll();

// Monthly task completion trend (last 6 months)
$stmt = $pdo->query("SELECT 
    DATE_FORMAT(updated_at, '%b %Y') as month,
    COUNT(*) as completed
    FROM tasks 
    WHERE status = 'Completed' 
    AND updated_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
    ORDER BY updated_at ASC");
$monthly_completions = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>/pages/tools/backup_database.php" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-download"></i> Backup
            </a>
        </div>
    </div>
</div>

<!-- Alert Cards -->
<?php if ($stats['overdue_tasks'] > 0): ?>
<div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
    <div>
        <strong>Attention!</strong> You have <?php echo $stats['overdue_tasks']; ?> overdue task<?php echo $stats['overdue_tasks'] > 1 ? 's' : ''; ?>.
        <a href="<?php echo BASE_URL; ?>/pages/tasks/list_tasks.php?filter=overdue" class="alert-link ms-2">View now <i class="bi bi-arrow-right"></i></a>
    </div>
</div>
<?php endif; ?>

<?php if ($stats['warranty_expiring'] > 0): ?>
<div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
    <i class="bi bi-exclamation-circle-fill me-2 fs-4"></i>
    <div>
        <strong>Notice:</strong> <?php echo $stats['warranty_expiring']; ?> equipment warranties expiring in 30 days.
        <a href="<?php echo BASE_URL; ?>/pages/equipment/list_equipment.php?filter=warranty_expiring" class="alert-link ms-2">View equipment <i class="bi bi-arrow-right"></i></a>
    </div>
</div>
<?php endif; ?>

<!-- Primary Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 text-uppercase small fw-semibold">Active Users</p>
                        <h2 class="mb-0 fw-bold"><?php echo $stats['active_users']; ?></h2>
                        <small class="text-muted">of <?php echo $stats['total_users']; ?> total</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-people fs-1 text-primary"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>/pages/users/list_users.php" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-arrow-right me-1"></i>Manage Users
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 text-uppercase small fw-semibold">Total Equipment</p>
                        <h2 class="mb-0 fw-bold"><?php echo $stats['total_equipment']; ?></h2>
                        <small class="text-success"><?php echo $stats['equipment_in_use']; ?> in use</small>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-pc-display fs-1 text-success"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>/pages/equipment/list_equipment.php" class="btn btn-sm btn-outline-success w-100">
                        <i class="bi bi-arrow-right me-1"></i>View Equipment
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 text-uppercase small fw-semibold">Active Tasks</p>
                        <h2 class="mb-0 fw-bold"><?php echo $stats['pending_tasks'] + $stats['started_tasks']; ?></h2>
                        <small class="text-<?php echo $stats['overdue_tasks'] > 0 ? 'danger' : 'warning'; ?>">
                            <?php echo $stats['overdue_tasks']; ?> overdue
                        </small>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="bi bi-check2-square fs-1 text-warning"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>/pages/tasks/list_tasks.php" class="btn btn-sm btn-outline-warning w-100">
                        <i class="bi bi-arrow-right me-1"></i>Manage Tasks
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 text-uppercase small fw-semibold">Network IPs</p>
                        <h2 class="mb-0 fw-bold"><?php echo $stats['total_network']; ?></h2>
                        <small class="text-info"><?php echo $stats['network_assigned']; ?> assigned</small>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="bi bi-hdd-network fs-1 text-info"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>/pages/network/list_network_info.php" class="btn btn-sm btn-outline-info w-100">
                        <i class="bi bi-arrow-right me-1"></i>View Network
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <!-- Equipment Distribution Chart -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-pie-chart me-2"></i>Equipment Distribution
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="equipmentChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Task Status Chart -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-bar-chart me-2"></i>Task Status Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="taskChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Completion Trend -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-graph-up me-2"></i>Task Completion Trend (Last 6 Months)
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">
                <i class="bi bi-clock-history me-2"></i>Recent System Activity
            </h5>
            <a href="<?php echo BASE_URL; ?>/pages/tools/system_logs.php" class="btn btn-sm btn-outline-secondary">
                View All <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Time</th>
                        <th>Level</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_logs)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">No recent activity</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recent_logs as $log): ?>
                        <tr>
                            <td class="text-nowrap ps-3"><?php echo format_datetime($log['created_at']); ?></td>
                            <td>
                                <?php
                                $badge_class = 'secondary';
                                $icon = 'info-circle';
                                if ($log['log_level'] === 'ERROR') {
                                    $badge_class = 'danger';
                                    $icon = 'x-circle';
                                } elseif ($log['log_level'] === 'WARNING') {
                                    $badge_class = 'warning';
                                    $icon = 'exclamation-triangle';
                                } elseif ($log['log_level'] === 'INFO') {
                                    $badge_class = 'info';
                                    $icon = 'info-circle';
                                }
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>">
                                    <i class="bi bi-<?php echo $icon; ?> me-1"></i><?php echo $log['log_level']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['message']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Equipment Distribution Chart
const equipmentCtx = document.getElementById('equipmentChart').getContext('2d');
new Chart(equipmentCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($equipment_by_type, 'type_name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($equipment_by_type, 'count')); ?>,
            backgroundColor: [
                '#667eea', '#764ba2', '#10b981', '#f59e0b', '#ef4444',
                '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 12,
                    padding: 10,
                    font: { size: 11 }
                }
            }
        }
    }
});

// Task Status Chart
const taskCtx = document.getElementById('taskChart').getContext('2d');
new Chart(taskCtx, {
    type: 'bar',
    data: {
        labels: ['Pending', 'Started', 'Completed', 'Cancelled'],
        datasets: [{
            label: 'Tasks',
            data: [
                <?php echo $task_chart_data['Pending']; ?>,
                <?php echo $task_chart_data['Started']; ?>,
                <?php echo $task_chart_data['Completed']; ?>,
                <?php echo $task_chart_data['Cancelled']; ?>
            ],
            backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
            borderRadius: 8,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});

// Monthly Trend Chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($monthly_completions, 'month')); ?>,
        datasets: [{
            label: 'Completed Tasks',
            data: <?php echo json_encode(array_column($monthly_completions, 'completed')); ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>