<?php
// ==================== pages/network/delete_network_info.php ====================
// Folder: pages/network/
// File: delete_network_info.php
// Purpose: Delete network information (only if unassigned)

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

if ($network_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid network ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM network_info WHERE id = ?");
    $stmt->execute([$network_id]);
    $network = $stmt->fetch();
    
    if (!$network) {
        echo json_encode(['success' => false, 'message' => 'Network information not found']);
        exit;
    }
    
    if ($network['equipment_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete assigned network info']);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM network_info WHERE id = ?");
    $stmt->execute([$network_id]);
    
    create_notification($pdo, 'Network', 'Deleted', 'Network Info Deleted', 
        "IP Address {$network['ip_address']} was deleted.", null, get_current_user_id());
    
    log_system($pdo, 'WARNING', "Network info deleted: {$network['ip_address']}", get_current_user_id());
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
