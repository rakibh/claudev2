<?php
// Folder: pages/
// File: dashboard_user.php
// Purpose: Enhanced user dashboard with personal statistics and charts

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

require_login();

define('PAGE_TITLE', 'My Dashboard');

$user_id = get_current_user_id();

// Get user statistics
$stats = [];

// My tasks by status
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN t.status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN t.status = 'Started' THEN 1 ELSE 0 END) as started,
    SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN t.due_date < NOW() AND t.status NOT IN ('Completed', 'Cancelled') THEN 1 ELSE 0 END) as overdue
    FROM task_assignments ta 
    JOIN tasks t ON ta.task_id = t.id 
    WHERE ta.user_id = ?");
$stmt->execute([$user_id]);
$my_task_stats = $stmt->fetch();
$stats['my_total_tasks'] = $my_task_stats['total'];
$stats['my_pending'] = $my_task_stats['pending'];
$stats['my_started'] = $my_task_stats['started'];
$stats['my_completed'] = $my_task_stats['completed'];
$stats['my_overdue'] = $my_task_stats['overdue'];
$stats['my_active'] = $my_task_stats['pending'] + $my_task_stats['started'];

// Tasks I created
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status != 'Completed' AND status != 'Cancelled' THEN 1 ELSE 0 END) as active
    FROM tasks WHERE created_by = ?");
$stmt->execute([$user_id]);
$created_stats = $stmt->fetch();
$stats['created_total'] = $created_stats['total'];
$stats['created_active'] = $created_stats['active'];

// Recent task completions (last 5)
$stmt = $pdo->prepare("SELECT t.*, ta.user_id 
    FROM task_assignments ta 
    JOIN tasks t ON ta.task_id = t.id 
    WHERE ta.user_id = ? AND t.status = 'Completed'
    ORDER BY t.updated_at DESC 
    LIMIT 5");
$stmt->execute([$user_id]);
$recent_completions = $stmt->fetchAll();

// Upcoming tasks with deadlines
$stmt = $pdo->prepare("SELECT t.* 
    FROM task_assignments ta 
    JOIN tasks t ON ta.task_id = t.id 
    WHERE ta.user_id = ? 
    AND t.status NOT IN ('Completed', 'Cancelled')
    AND t.due_date IS NOT NULL
    ORDER BY t.due_date ASC 
    LIMIT 5");
$stmt->execute([$user_id]);
$upcoming_tasks = $stmt->fetchAll();

// Task completion rate by month (last 6 months)
$stmt = $pdo->prepare("SELECT 
    DATE_FORMAT(t.updated_at, '%b %Y') as month,
    COUNT(*) as completed
    FROM task_assignments ta
    JOIN tasks t ON ta.task_id = t.id
    WHERE ta.user_id = ?
    AND t.status = 'Completed'
    AND t.updated_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(t.updated_at, '%Y-%m')
    ORDER BY t.updated_at ASC");
$stmt->execute([$user_id]);
$monthly_completions = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-speedometer2 me-2"></i>My Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- Alert for overdue tasks -->
<?php if ($stats['my_overdue'] > 0): ?>
<div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
    <div>
        <strong>Attention!</strong> You have <?php echo $stats['my_overdue']; ?> overdue task<?php echo $stats['my_overdue'] > 1 ? 's' : ''; ?>.
        <a href="<?php echo BASE_URL; ?>/pages/tasks/list_tasks.php?filter=my_overdue" class="alert-link ms-2">View now <i class="bi bi-arrow-right"></i></a>
    </div>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 text-uppercase small fw-semibold">My Active Tasks</p>
                        <h2 class="mb-0 fw-bold"><?php echo $stats['my_active']; ?></h2>
                        <small class="text-<?php echo $stats['my_overdue'] > 0 ? 'danger' : 'success'; ?>">
                            <?php echo $stats['my_overdue']; ?> overdue
                        </small>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-check2-square fs-1 text-primary"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>/pages/tasks/list_tasks.php?filter=my_active" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-arrow-right me-1"></i>View Tasks
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 text-uppercase small fw-semibold">Completed Tasks</p>
                        <h2 class="mb-0 fw-bold"><?php echo $stats['my_completed']; ?></h2>
                        <small class="text-muted">Total: <?php echo $stats['my_total_tasks']; ?> tasks</small>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-check-circle fs-1 text-success"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>/pages/tasks/list_tasks.php?filter=my_completed" class="btn btn-sm btn-outline-success w-100">
                        <i class="bi bi-arrow-right me-1"></i>View Completed
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 text-uppercase small fw-semibold">Tasks I Created</p>
                        <h2 class="mb-0 fw-bold"><?php echo $stats['created_total']; ?></h2>
                        <small class="text-info"><?php echo $stats['created_active']; ?> active</small>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="bi bi-plus-circle fs-1 text-info"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>/pages/tasks/add_task.php" class="btn btn-sm btn-outline-info w-100">
                        <i class="bi bi-plus-circle me-1"></i>Create New Task
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <!-- Task Distribution -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-pie-chart me-2"></i>My Task Status
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="myTaskChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Completion Trend -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-graph-up me-2"></i>Completion Trend (Last 6 Months)
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

<!-- Upcoming Tasks -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bi bi-calendar-event me-2"></i>Upcoming Deadlines
                    </h5>
                    <span class="badge bg-warning"><?php echo count($upcoming_tasks); ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcoming_tasks)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-calendar-check fs-1"></i>
                        <p class="mt-2 mb-0">No upcoming deadlines</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcoming_tasks as $task): ?>
                            <?php 
                            $is_urgent = strtotime($task['due_date']) < strtotime('+3 days');
                            $due_class = $is_urgent ? 'text-danger' : 'text-muted';
                            ?>
                            <a href="<?php echo BASE_URL; ?>/pages/tasks/view_task.php?id=<?php echo $task['id']; ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                    <small class="<?php echo $due_class; ?>">
                                        <i class="bi bi-clock"></i> <?php echo format_date($task['due_date']); ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?php 
                                    echo $task['priority'] === 'Critical' ? 'danger' : 
                                        ($task['priority'] === 'High' ? 'warning' : 
                                        ($task['priority'] === 'Medium' ? 'info' : 'secondary')); 
                                ?>"><?php echo $task['priority']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Completions -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bi bi-check2-circle me-2"></i>Recent Completions
                    </h5>
                    <span class="badge bg-success"><?php echo count($recent_completions); ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_completions)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2 mb-0">No completed tasks yet</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_completions as $task): ?>
                            <a href="<?php echo BASE_URL; ?>/pages/tasks/view_task.php?id=<?php echo $task['id']; ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                    <small class="text-muted">
                                        <i class="bi bi-check-circle-fill text-success"></i> <?php echo format_date($task['updated_at']); ?>
                                    </small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0 fw-semibold">
            <i class="bi bi-lightning me-2"></i>Quick Actions
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3 col-sm-6">
                <a href="<?php echo BASE_URL; ?>/pages/tasks/add_task.php" class="btn btn-outline-primary w-100">
                    <i class="bi bi-plus-circle me-2"></i>Create Task
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="<?php echo BASE_URL; ?>/pages/equipment/add_equipment.php" class="btn btn-outline-success w-100">
                    <i class="bi bi-plus-circle me-2"></i>Add Equipment
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="<?php echo BASE_URL; ?>/pages/network/add_network_info.php" class="btn btn-outline-info w-100">
                    <i class="bi bi-plus-circle me-2"></i>Add Network Info
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="<?php echo BASE_URL; ?>/pages/users/profile.php" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-person me-2"></i>My Profile
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// My Task Status Chart
const myTaskCtx = document.getElementById('myTaskChart').getContext('2d');
new Chart(myTaskCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Started', 'Completed'],
        datasets: [{
            data: [
                <?php echo $stats['my_pending']; ?>,
                <?php echo $stats['my_started']; ?>,
                <?php echo $stats['my_completed']; ?>
            ],
            backgroundColor: ['#f59e0b', '#3b82f6', '#10b981'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 12 }
                }
            }
        }
    }
});

// Completion Trend Chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($monthly_completions, 'month')); ?>,
        datasets: [{
            label: 'Completed Tasks',
            data: <?php echo json_encode(array_column($monthly_completions, 'completed')); ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
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