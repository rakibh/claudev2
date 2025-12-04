<?php
// ==================== pages/tasks/delete_task.php ====================
// Folder: pages/tasks/
// File: delete_task.php
// Purpose: Delete task with confirmation (Admin only or task creator)

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

if ($task_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
    exit;
}

try {
    // Get task info
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
        exit;
    }
    
    // Check permissions (admin or creator can delete)
    if (!is_admin() && $task['created_by'] != get_current_user_id()) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this task']);
        exit;
    }
    
    // Delete task (cascading will delete assignments)
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    
    // Create notification
    create_notification(
        $pdo,
        'Task',
        'Deleted',
        'Task Deleted',
        "Task '{$task['title']}' was deleted from the system.",
        null,
        get_current_user_id()
    );
    
    // Log activity
    log_system($pdo, 'WARNING', "Task deleted: {$task['title']}", get_current_user_id());
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    log_system($pdo, 'ERROR', "Failed to delete task: " . $e->getMessage(), get_current_user_id());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>