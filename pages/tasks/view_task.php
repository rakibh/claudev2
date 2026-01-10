<?php
// Folder: pages/tasks/
// File: view_task.php
// Purpose: View complete task details with assigned users

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($task_id === 0) {
    $_SESSION['error_message'] = 'Invalid task ID';
    header('Location: list_tasks.php');
    exit;
}

// Get task data
$stmt = $pdo->prepare("SELECT t.*, u.first_name, u.last_name, u.employee_id as creator_id 
    FROM tasks t 
    LEFT JOIN users u ON t.created_by = u.id 
    WHERE t.id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error_message'] = 'Task not found';
    header('Location: list_tasks.php');
    exit;
}

// Get assigned users
$stmt = $pdo->prepare("SELECT u.id, u.employee_id, u.first_name, u.last_name, u.email, u.profile_photo, ta.assigned_at 
    FROM task_assignments ta 
    LEFT JOIN users u ON ta.user_id = u.id 
    WHERE ta.task_id = ? 
    ORDER BY u.first_name");
$stmt->execute([$task_id]);
$assigned_users = $stmt->fetchAll();

// Check if current user is creator or assigned
$user_id = get_current_user_id();
$is_creator = $task['created_by'] == $user_id;
$is_assigned = false;
foreach ($assigned_users as $assigned_user) {
    if ($assigned_user['id'] == $user_id) {
        $is_assigned = true;
        break;
    }
}

$can_edit = is_admin() || $is_creator || $is_assigned;

define('PAGE_TITLE', 'View Task');

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-eye me-2"></i>Task Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <?php if ($can_edit): ?>
            <a href="edit_task.php?id=<?php echo $task_id; ?>" class="btn btn-sm btn-warning">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            <?php endif; ?>
            
            <?php if (is_admin() || $is_creator): ?>
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteTask(<?php echo $task_id; ?>)">
                <i class="bi bi-trash me-1"></i>Delete
            </button>
            <?php endif; ?>
        </div>
        <a href="list_tasks.php" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Tasks
        </a>
    </div>
</div>

<!-- Task Information -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-start">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Task Information</h5>
            <div>
                <span class="badge bg-<?php 
                    echo $task['priority'] === 'Critical' ? 'danger' : 
                        ($task['priority'] === 'High' ? 'warning' : 
                        ($task['priority'] === 'Medium' ? 'info' : 'secondary')); 
                ?> me-2">
                    <?php echo htmlspecialchars($task['priority']); ?> Priority
                </span>
                <span class="badge bg-<?php 
                    echo $task['status'] === 'Completed' ? 'success' : 
                        ($task['status'] === 'Started' ? 'info' : 
                        ($task['status'] === 'Cancelled' ? 'secondary' : 'warning')); 
                ?>">
                    <?php echo htmlspecialchars($task['status']); ?>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12">
                <h4 class="mb-3"><?php echo htmlspecialchars($task['title']); ?></h4>
            </div>
            
            <?php if ($task['description']): ?>
            <div class="col-12">
                <strong>Description:</strong>
                <p class="form-control-plaintext"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="col-md-6">
                <strong>Status:</strong>
                <p class="form-control-plaintext">
                    <span class="badge bg-<?php 
                        echo $task['status'] === 'Completed' ? 'success' : 
                            ($task['status'] === 'Started' ? 'info' : 
                            ($task['status'] === 'Cancelled' ? 'secondary' : 'warning')); 
                    ?> fs-6">
                        <?php echo htmlspecialchars($task['status']); ?>
                    </span>
                </p>
            </div>
            
            <div class="col-md-6">
                <strong>Priority:</strong>
                <p class="form-control-plaintext">
                    <span class="badge bg-<?php 
                        echo $task['priority'] === 'Critical' ? 'danger' : 
                            ($task['priority'] === 'High' ? 'warning' : 
                            ($task['priority'] === 'Medium' ? 'info' : 'secondary')); 
                    ?> fs-6">
                        <?php echo htmlspecialchars($task['priority']); ?>
                    </span>
                </p>
            </div>
            
            <?php if ($task['due_date']): ?>
            <div class="col-md-6">
                <strong>Due Date:</strong>
                <p class="form-control-plaintext">
                    <i class="bi bi-calendar-event me-1"></i>
                    <?php echo format_datetime($task['due_date']); ?>
                    <?php if (is_task_overdue($task['due_date']) && $task['status'] !== 'Completed'): ?>
                        <span class="badge bg-danger ms-2">Overdue</span>
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
            
            <div class="col-md-6">
                <strong>Created By:</strong>
                <p class="form-control-plaintext">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars($task['first_name'] . ' ' . $task['last_name']); ?>
                    <br>
                    <small class="text-muted">Employee ID: <?php echo htmlspecialchars($task['creator_id']); ?></small>
                </p>
            </div>
            
            <div class="col-md-6">
                <strong>Created At:</strong>
                <p class="form-control-plaintext">
                    <i class="bi bi-calendar-plus me-1"></i>
                    <?php echo format_datetime($task['created_at']); ?>
                </p>
            </div>
            
            <div class="col-md-6">
                <strong>Last Updated:</strong>
                <p class="form-control-plaintext">
                    <i class="bi bi-calendar-check me-1"></i>
                    <?php echo format_datetime($task['updated_at']); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Assigned Users -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="bi bi-people me-2"></i>Assigned Users 
            <span class="badge bg-primary"><?php echo count($assigned_users); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($assigned_users)): ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-person-x fs-1"></i>
                <p class="mt-2 mb-0">No users assigned to this task</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($assigned_users as $user): ?>
                <div class="col-md-6">
                    <div class="d-flex align-items-center p-3 border rounded">
                        <img src="<?php echo $user['profile_photo'] ? BASE_URL . '/uploads/profiles/' . $user['profile_photo'] : BASE_URL . '/assets/images/default-avatar.png'; ?>" 
                             alt="<?php echo htmlspecialchars($user['first_name']); ?>" 
                             class="rounded-circle me-3" width="50" height="50">
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                            <small class="text-muted">ID: <?php echo htmlspecialchars($user['employee_id']); ?></small>
                            <?php if ($user['email']): ?>
                            <br><small class="text-muted">
                                <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                            </small>
                            <?php endif; ?>
                            <br><small class="text-muted">
                                <i class="bi bi-calendar-plus me-1"></i>Assigned: <?php echo format_datetime($user['assigned_at']); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Status Update -->
<?php if ($can_edit && $task['status'] !== 'Completed' && $task['status'] !== 'Cancelled'): ?>
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <?php if ($task['status'] === 'Pending'): ?>
            <div class="col-md-4">
                <button type="button" class="btn btn-info w-100" 
                        onclick="updateTaskStatus(<?php echo $task_id; ?>, 'Started')">
                    <i class="bi bi-play-circle me-1"></i>Start Task
                </button>
            </div>
            <?php endif; ?>
            
            <?php if ($task['status'] === 'Started'): ?>
            <div class="col-md-4">
                <button type="button" class="btn btn-success w-100" 
                        onclick="updateTaskStatus(<?php echo $task_id; ?>, 'Completed')">
                    <i class="bi bi-check-circle me-1"></i>Mark as Complete
                </button>
            </div>
            <?php endif; ?>
            
            <div class="col-md-4">
                <button type="button" class="btn btn-secondary w-100" 
                        onclick="updateTaskStatus(<?php echo $task_id; ?>, 'Cancelled')">
                    <i class="bi bi-x-circle me-1"></i>Cancel Task
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function updateTaskStatus(taskId, newStatus) {
    let confirmMsg = 'Update task status to ' + newStatus + '?';
    
    if (newStatus === 'Completed') {
        confirmMsg = 'Mark this task as completed?';
    } else if (newStatus === 'Cancelled') {
        confirmMsg = 'Are you sure you want to cancel this task?';
    }
    
    if (!confirm(confirmMsg)) {
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Updating...';
    
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
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function deleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task?\n\nThis action cannot be undone and will remove:\n- Task details\n- All assignments\n- Related notifications')) {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';
        
        fetch('delete_task.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${taskId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'list_tasks.php';
            } else {
                alert(data.message || 'Failed to delete task');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
}
</script>

<?php include '../../includes/footer.php'; ?>