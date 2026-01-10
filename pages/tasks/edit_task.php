<?php
// Folder: pages/tasks/
// File: edit_task.php
// Purpose: Edit task with assignment management

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
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error_message'] = 'Task not found';
    header('Location: list_tasks.php');
    exit;
}

// Check permissions
$user_id = get_current_user_id();
$is_creator = $task['created_by'] == $user_id;

// Check if user is assigned
$stmt = $pdo->prepare("SELECT COUNT(*) FROM task_assignments WHERE task_id = ? AND user_id = ?");
$stmt->execute([$task_id, $user_id]);
$is_assigned = $stmt->fetchColumn() > 0;

$can_edit = is_admin() || $is_creator || $is_assigned;

if (!$can_edit) {
    $_SESSION['error_message'] = 'You do not have permission to edit this task';
    header('Location: view_task.php?id=' . $task_id);
    exit;
}

// Get all active users
$stmt = $pdo->query("SELECT id, employee_id, first_name, last_name FROM users WHERE status = 'Active' ORDER BY first_name");
$users = $stmt->fetchAll();

// Get currently assigned users
$stmt = $pdo->prepare("SELECT user_id FROM task_assignments WHERE task_id = ?");
$stmt->execute([$task_id]);
$assigned_user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $priority = sanitize_input($_POST['priority'] ?? 'Medium');
    $status = sanitize_input($_POST['status'] ?? 'Pending');
    $due_date = sanitize_input($_POST['due_date'] ?? '');
    $assigned_users = $_POST['assigned_users'] ?? [];
    
    $errors = [];
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Task title is required';
    }
    
    if (empty($assigned_users)) {
        $errors[] = 'At least one user must be assigned';
    }
    
    // Validate status transition
    if ($status === 'Completed' && $task['status'] !== 'Started') {
        $errors[] = 'Task must be Started before marking as Completed';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update task
            $sql = "UPDATE tasks SET title = ?, description = ?, priority = ?, status = ?, due_date = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $title,
                $description ?: null,
                $priority,
                $status,
                $due_date ?: null,
                $task_id
            ]);
            
            // Log status change if changed
            if ($status !== $task['status']) {
                create_notification(
                    $pdo,
                    'Task',
                    'Status Changed',
                    'Task Status Updated',
                    "Task '{$title}' status changed from '{$task['status']}' to '{$status}'",
                    $task_id,
                    get_current_user_id()
                );
            }
            
            // Update assignments
            // Delete current assignments
            $stmt = $pdo->prepare("DELETE FROM task_assignments WHERE task_id = ?");
            $stmt->execute([$task_id]);
            
            // Insert new assignments
            $sql = "INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            foreach ($assigned_users as $assigned_user_id) {
                $stmt->execute([$task_id, $assigned_user_id]);
            }
            
            // Get assignee names for notification
            $assignee_names = [];
            foreach ($users as $user) {
                if (in_array($user['id'], $assigned_users)) {
                    $assignee_names[] = $user['first_name'] . ' ' . $user['last_name'];
                }
            }
            
            // Create notification
            create_notification(
                $pdo,
                'Task',
                'Updated',
                'Task Updated',
                "Task '{$title}' has been updated. Assigned to: " . implode(', ', $assignee_names),
                $task_id,
                get_current_user_id()
            );
            
            // Log activity
            log_system($pdo, 'INFO', "Task updated: {$title}", get_current_user_id());
            
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Task updated successfully';
            header('Location: view_task.php?id=' . $task_id);
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            log_system($pdo, 'ERROR', "Failed to update task: " . $e->getMessage(), get_current_user_id());
            $errors[] = 'Database error occurred';
        }
    }
}

define('PAGE_TITLE', 'Edit Task');

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-pencil-square me-2"></i>Edit Task</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="view_task.php?id=<?php echo $task_id; ?>" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to View
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Task Details</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label for="title" class="form-label">Task Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?php echo htmlspecialchars($task['title']); ?>" required>
                </div>
                
                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-md-4">
                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                    <select class="form-select" id="priority" name="priority" required>
                        <option value="Low" <?php echo $task['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo $task['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo $task['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
                        <option value="Critical" <?php echo $task['priority'] === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="Pending" <?php echo $task['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Started" <?php echo $task['status'] === 'Started' ? 'selected' : ''; ?>>Started</option>
                        <option value="Completed" <?php echo $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo $task['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <?php if ($task['status'] !== 'Started'): ?>
                    <small class="text-muted">Note: Task must be Started before marking as Completed</small>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <label for="due_date" class="form-label">Due Date</label>
                    <input type="datetime-local" class="form-control" id="due_date" name="due_date" 
                           value="<?php echo $task['due_date'] ? date('Y-m-d\TH:i', strtotime($task['due_date'])) : ''; ?>">
                </div>
                
                <div class="col-12">
                    <label class="form-label">Assign To <span class="text-danger">*</span></label>
                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                        <div class="row g-2">
                            <?php foreach ($users as $user): ?>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="assigned_users[]" 
                                           value="<?php echo $user['id']; ?>" id="user_<?php echo $user['id']; ?>"
                                           <?php echo in_array($user['id'], $assigned_user_ids) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="user_<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['employee_id'] . ')'); ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <small class="text-muted">Select at least one user</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="view_task.php?id=<?php echo $task_id; ?>" class="btn btn-secondary">
            <i class="bi bi-x-circle me-1"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-2"></i>Save Changes
        </button>
    </div>
</form>

<script>
// Validate status transition
document.getElementById('status').addEventListener('change', function() {
    const originalStatus = '<?php echo $task['status']; ?>';
    const newStatus = this.value;
    
    if (newStatus === 'Completed' && originalStatus !== 'Started') {
        alert('Warning: Task must be "Started" before marking as "Completed".');
        this.value = originalStatus;
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('input[name="assigned_users[]"]:checked');
    
    if (checkboxes.length === 0) {
        e.preventDefault();
        alert('Please assign at least one user to this task.');
        return false;
    }
    
    const status = document.getElementById('status').value;
    const originalStatus = '<?php echo $task['status']; ?>';
    
    if (status === 'Completed' && originalStatus !== 'Started') {
        e.preventDefault();
        alert('Task must be Started before marking as Completed.');
        return false;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>