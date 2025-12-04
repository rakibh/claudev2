<?php
// Folder: pages/tasks/
// File: add_task.php
// Purpose: Create new task with multiple assignees

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

// Get all active users for assignment
$stmt = $pdo->query("SELECT id, employee_id, first_name, last_name FROM users WHERE status = 'Active' ORDER BY first_name");
$users = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $priority = sanitize_input($_POST['priority'] ?? 'Medium');
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
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert task
            $sql = "INSERT INTO tasks (title, description, priority, due_date, created_by) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $title,
                $description ?: null,
                $priority,
                $due_date ?: null,
                get_current_user_id()
            ]);
            
            $task_id = $pdo->lastInsertId();
            
            // Assign users
            $sql = "INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            foreach ($assigned_users as $user_id) {
                $stmt->execute([$task_id, $user_id]);
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
                'Assigned',
                'New Task Assigned',
                "Task '{$title}' has been assigned to: " . implode(', ', $assignee_names),
                $task_id,
                get_current_user_id()
            );
            
            // Log activity
            log_system($pdo, 'INFO', "New task created: {$title}", get_current_user_id());
            
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Task created successfully';
            header('Location: list_tasks.php');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            log_system($pdo, 'ERROR', "Failed to create task: " . $e->getMessage(), get_current_user_id());
            $errors[] = 'Database error occurred';
        }
    }
}

define('PAGE_TITLE', 'Create Task');

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-plus-circle me-2"></i>Create New Task</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="list_tasks.php" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Tasks
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
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                </div>
                
                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-md-6">
                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                    <select class="form-select" id="priority" name="priority" required>
                        <option value="Low" <?php echo ($_POST['priority'] ?? 'Medium') === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo ($_POST['priority'] ?? 'Medium') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo ($_POST['priority'] ?? 'Medium') === 'High' ? 'selected' : ''; ?>>High</option>
                        <option value="Critical" <?php echo ($_POST['priority'] ?? 'Medium') === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="due_date" class="form-label">Due Date</label>
                    <input type="datetime-local" class="form-control" id="due_date" name="due_date" 
                           value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>">
                </div>
                
                <div class="col-12">
                    <label class="form-label">Assign To <span class="text-danger">*</span></label>
                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($users as $user): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="assigned_users[]" 
                                   value="<?php echo $user['id']; ?>" id="user_<?php echo $user['id']; ?>"
                                   <?php echo in_array($user['id'], $_POST['assigned_users'] ?? []) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="user_<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['employee_id'] . ')'); ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted">Select at least one user</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="list_tasks.php" class="btn btn-secondary">
            <i class="bi bi-x-circle me-1"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-2"></i>Create Task
        </button>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>