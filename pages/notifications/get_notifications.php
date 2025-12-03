<?php
// Folder: pages/notifications/
// File: get_notifications.php
// Purpose: Get recent notifications for dropdown display

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

$user_id = get_current_user_id();
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;

$sql = "SELECT n.*, uns.is_read, uns.is_acknowledged, u.first_name, u.last_name
        FROM notifications n
        JOIN user_notification_status uns ON n.id = uns.notification_id
        LEFT JOIN users u ON n.created_by = u.id
        WHERE uns.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $limit]);
$notifications = $stmt->fetchAll();

$result = [];
foreach ($notifications as $notif) {
    $result[] = [
        'id' => $notif['id'],
        'title' => $notif['title'],
        'message' => $notif['message'],
        'type' => $notif['type'],
        'event' => $notif['event'],
        'is_read' => (bool)$notif['is_read'],
        'is_acknowledged' => (bool)$notif['is_acknowledged'],
        'time_ago' => time_ago($notif['created_at']),
        'created_by' => $notif['first_name'] ? $notif['first_name'] . ' ' . $notif['last_name'] : 'System'
    ];
}

header('Content-Type: application/json');
echo json_encode(['notifications' => $result]);
?>
