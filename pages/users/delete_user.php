<?php
// ==================== pages/users/delete_user.php ====================
// Purpose: Delete user handler (Admin Only)

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_admin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($user_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Prevent self-deletion
if ($user_id === get_current_user_id()) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    exit;
}

try {
    // Get user info before deletion
    $stmt = $pdo->prepare("SELECT employee_id, first_name, last_name, profile_photo FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Delete profile photo if exists
    if ($user['profile_photo']) {
        $photo_path = PROFILE_UPLOAD_PATH . '/' . $user['profile_photo'];
        delete_file($photo_path);
    }
    
    // Delete user (cascading will handle related records)
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Log activity
    log_system($pdo, 'WARNING', "User deleted: {$user['employee_id']} ({$user['first_name']} {$user['last_name']})", get_current_user_id());
    
    // Create notification
    create_notification(
        $pdo,
        'User',
        'Deleted',
        'User Deleted',
        "User {$user['first_name']} {$user['last_name']} (ID: {$user['employee_id']}) was deleted from the system.",
        null,
        get_current_user_id()
    );
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    log_system($pdo, 'ERROR', "Failed to delete user: " . $e->getMessage(), get_current_user_id());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

?>


<?php
// ==================== pages/users/reset_password.php ====================
// Purpose: Reset user password handler (Admin Only)

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_admin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$new_password = $_POST['password'] ?? '';

if ($user_id === 0 || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if (!validate_password($new_password)) {
    echo json_encode(['success' => false, 'message' => 'Password must be 6+ chars incl. a letter, a number, and a special character']);
    exit;
}

try {
    // Get user info
    $stmt = $pdo->prepare("SELECT employee_id, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Hash new password
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password and set force_password_change flag
    $stmt = $pdo->prepare("UPDATE users SET password = ?, force_password_change = 1 WHERE id = ?");
    $stmt->execute([$password_hash, $user_id]);
    
    // Log activity
    log_system($pdo, 'INFO', "Password reset for user: {$user['employee_id']} ({$user['first_name']} {$user['last_name']})", get_current_user_id());
    
    // Log revision
    log_user_revision($pdo, $user_id, get_current_user_id(), 'password', '***', '*** (reset by admin)');
    
    echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
    
} catch (PDOException $e) {
    log_system($pdo, 'ERROR', "Failed to reset password: " . $e->getMessage(), get_current_user_id());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

?>