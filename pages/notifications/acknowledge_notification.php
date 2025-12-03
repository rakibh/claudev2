<?php
// Folder: pages/notifications/
// File: acknowledge_notification.php
// Purpose: Mark notification as acknowledged

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_id = (int)($_POST['id'] ?? 0);
    $user_id = get_current_user_id();
    
    if ($notification_id > 0) {
        $sql = "UPDATE user_notification_status 
                SET is_acknowledged = 1, acknowledged_at = NOW() 
                WHERE notification_id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$notification_id, $user_id]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>