<?php
// ==================== pages/network/unassign_network_info.php ====================
// Folder: pages/network/
// File: unassign_network_info.php
// Purpose: Unassign network from equipment

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

$network_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

try {
    $stmt = $pdo->prepare("UPDATE network_info SET equipment_id = NULL WHERE id = ?");
    $stmt->execute([$network_id]);
    
    create_notification($pdo, 'Network', 'Unassigned', 'Network Unassigned', 
        "Network information was unassigned from equipment.", $network_id, get_current_user_id());
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>