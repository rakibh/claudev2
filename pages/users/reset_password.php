<?php
// Folder: pages/users/
// File: reset_password.php
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
    
    // Create notification for the user
    create_notification(
        $pdo,
        'User',
        'Updated',
        'Password Reset',
        "Your password has been reset by an administrator. You will be required to change it on your next login.",
        $user_id,
        get_current_user_id()
    );
    
    echo json_encode(['success' => true, 'message' => 'Password reset successfully. User will be prompted to change password on next login.']);
    
} catch (PDOException $e) {
    log_system($pdo, 'ERROR', "Failed to reset password: " . $e->getMessage(), get_current_user_id());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>