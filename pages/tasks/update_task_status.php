<?php
// ==================== pages/tasks/update_task_status.php ====================
// Folder: pages/tasks/
// File: update_task_status.php
// Purpose: AJAX endpoint to update task status

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$task_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$new_status = sanitize_input($_POST['status'] ?? '');

if ($task_id === 0 || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate status
$valid_statuses = ['Pending', 'Started', 'Completed', 'Cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Get current task
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
        exit;
    }
    
    // Validate status transition
    if ($new_status === 'Completed' && $task['status'] !== 'Started') {
        echo json_encode(['success' => false, 'message' => 'Task must be Started before marking as Completed']);
        exit;
    }
    
    // Update status
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $task_id]);
    
    // Create notification
    create_notification(
        $pdo,
        'Task',
        'Status Changed',
        'Task Status Updated',
        "Task '{$task['title']}' status changed to '{$new_status}'",
        $task_id,
        get_current_user_id()
    );
    
    // Log activity
    log_system($pdo, 'INFO', "Task status updated: {$task['title']} -> {$new_status}", get_current_user_id());
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    log_system($pdo, 'ERROR', "Failed to update task status: " . $e->getMessage(), get_current_user_id());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>