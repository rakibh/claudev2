<?php
// Folder: ajax/
// File: notification_count.php
// Purpose: Get unacknowledged notification count for current user

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../config/session.php';

require_login();

$user_id = get_current_user_id();

$sql = "SELECT COUNT(*) as count FROM user_notification_status 
        WHERE user_id = ? AND is_acknowledged = 0";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$result = $stmt->fetch();

header('Content-Type: application/json');
echo json_encode(['count' => (int)$result['count']]);
?>