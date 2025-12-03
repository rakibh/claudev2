<?php
// Folder: pages/tasks/
// File: list_tasks.php
// Purpose: Task management with tabs: To Do, Doing, Past Due, Done, Dropped

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

define('PAGE_TITLE', 'Tasks');

$user_id = get_current_user_id();
$is_admin = is_admin();

// Get active tab
$active_tab = $_GET['tab'] ?? 'todo';

// Build SQL based on tab
$sql = "SELECT DISTINCT t.*, 
        GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') as assignees,
        creator.first_name as creator_first, creator.last_name as creator_last
        FROM tasks t
        LEFT JOIN task_assignments ta ON t.id = ta.task_id
        LEFT JOIN users u ON ta.user_id = u.id
        LEFT JOIN users creator ON t.created_by = creator.id
        WHERE 1=1";

$params = [];

// Filter by tab
switch ($active_tab) {
    case 'doing':
        $sql .= " AND t.status = 'Started'";
        break;
    case 'pastdue':
        $sql .= " AND t.due_date < NOW() AND t.status NOT IN ('Completed', 'Cancelled')";
        break;
    case 'done':
        $sql .= " AND t.status = 'Completed'";
        break;
    case 'dropped':
        $sql .= " AND t.status = 'Cancelled'";
        break;
    default: // todo
        $sql .= " AND t.status NOT IN ('Completed', 'Cancelled')";
}

// Filter by user assignments (non-admin sees only their tasks)
if (!$is_admin) {
    $sql .= " AND (t.created_by = ? OR ta.user_id = ?)";
    $params[] = $user_id;
    $params[] = $user_id;
}

$sql .= " GROUP BY t.id ORDER BY t.due_date ASC, t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Get counts for tabs
$count_sql = "SELECT 
    SUM(CASE WHEN t.status NOT IN ('Completed', 'Cancelled') THEN 1 ELSE 0 END) as todo_count,
    SUM(CASE WHEN t.status = 'Started' THEN 1 ELSE 0 END) as doing_count,
    SUM(CASE WHEN t.due_date < NOW() AND t.status NOT IN ('Completed', 'Cancelled') THEN 1 ELSE 0 END) as pastdue_count,
    SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as done_count,
    SUM(CASE WHEN t.status = 'Cancelled' THEN 1 ELSE 0 END) as dropped_count
    FROM tasks t";

if (!$is_admin) {
    $count_sql .= " LEFT JOIN task_assignments ta ON t.id = ta.task_id 
                    WHERE (t.created_by = ? OR ta.user_id = ?)";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute([$user_id, $user_id]);
} else {
    $count_stmt = $pdo->query($count_sql);
}

$counts = $count_stmt->fetch();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-check2-square me-2"></i>Task Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_task.php" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Create Task
        </a>
    </div>
</div>

<!-- Task Tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?php echo $active_tab === 'todo' ? 'active' : ''; ?>" href="?tab=todo">
            To Do <span class="badge bg-warning"><?php echo $counts['todo_count']; ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $active_tab === 'doing' ? 'active' : ''; ?>" href="?tab=doing">
            Doing <span class="badge bg-info"><?php echo $counts['doing_count']; ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $active_tab === 'pastdue' ? 'active' : ''; ?>" href="?tab=pastdue">
            Past Due <span class="badge bg-danger"><?php echo $counts['pastdue_count']; ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $active_tab === 'done' ? 'active' : ''; ?>" href="?tab=done">
            Done <span class="badge bg-success"><?php echo $counts['done_count']; ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $active_tab === 'dropped' ? 'active' : ''; ?>" href="?tab=dropped">
            Dropped <span class="badge bg-secondary"><?php echo $counts['dropped_count']; ?></span>
        </a>
    </li>
</ul>

<!-- Task List -->
<div class="row g-3">
    <?php if (empty($tasks)): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2 mb-0">No tasks found in this category</p>
            </div>
        </div>
    </div>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-<?php 
                            echo $task['priority'] === 'Critical' ? 'danger' : 
                                ($task['priority'] === 'High' ? 'warning' : 
                                ($task['priority'] === 'Medium' ? 'info' : 'secondary')); 
                        ?>"><?php echo $task['priority']; ?></span>
                        
                        <span class="badge bg-<?php 
                            echo $task['status'] === 'Completed' ? 'success' : 
                                ($task['status'] === 'Started' ? 'info' : 
                                ($task['status'] === 'Cancelled' ? 'secondary' : 'warning')); 
                        ?>"><?php echo $task['status']; ?></span>
                    </div>
                    
                    <h5 class="card-title">
                        <a href="view_task.php?id=<?php echo $task['id']; ?>" class="text-decoration-none text-dark">
                            <?php echo htmlspecialchars($task['title']); ?>
                        </a>
                    </h5>
                    
                    <p class="card-text text-muted small">
                        <?php echo htmlspecialchars(substr($task['description'] ?? '', 0, 100)); ?>
                        <?php echo strlen($task['description'] ?? '') > 100 ? '...' : ''; ?>
                    </p>
                    
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($task['assignees'] ?? 'Unassigned'); ?>
                        </small>
                    </div>
                    
                    <?php if ($task['due_date']): ?>
                    <div class="mb-2">
                        <small class="<?php echo is_task_overdue($task['due_date']) && $task['status'] !== 'Completed' ? 'text-danger' : 'text-muted'; ?>">
                            <i class="bi bi-calendar"></i> Due: <?php echo format_date($task['due_date']); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Created by: <?php echo htmlspecialchars($task['creator_first'] . ' ' . $task['creator_last']); ?>
                        </small>
                        
                        <div class="btn-group btn-group-sm">
                            <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Quick Status Update -->
                    <?php if ($task['status'] !== 'Completed' && $task['status'] !== 'Cancelled'): ?>
                    <div class="mt-3">
                        <div class="btn-group w-100" role="group">
                            <?php if ($task['status'] === 'Pending'): ?>
                            <button type="button" class="btn btn-sm btn-outline-info" 
                                    onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'Started')">
                                <i class="bi bi-play"></i> Start
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($task['status'] === 'Started'): ?>
                            <button type="button" class="btn btn-sm btn-outline-success" 
                                    onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'Completed')">
                                <i class="bi bi-check"></i> Complete
                            </button>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                    onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'Cancelled')">
                                <i class="bi bi-x"></i> Drop
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function updateTaskStatus(taskId, newStatus) {
    if (newStatus === 'Completed' && !confirm('Mark this task as completed?')) {
        return;
    }
    if (newStatus === 'Cancelled' && !confirm('Cancel this task?')) {
        return;
    }
    
    fetch('update_task_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${taskId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update task status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}
</script>

<?php include '../../includes/footer.php'; ?>