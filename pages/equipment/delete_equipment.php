<?php
// ==================== pages/equipment/delete_equipment.php ====================
// Folder: pages/equipment/
// File: delete_equipment.php
// Purpose: Delete equipment handler

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

$equipment_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($equipment_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid equipment ID']);
    exit;
}

try {
    // Get equipment info
    $stmt = $pdo->prepare("SELECT * FROM equipments WHERE id = ?");
    $stmt->execute([$equipment_id]);
    $equipment = $stmt->fetch();
    
    if (!$equipment) {
        echo json_encode(['success' => false, 'message' => 'Equipment not found']);
        exit;
    }
    
    // Delete warranty documents
    if ($equipment['warranty_documents']) {
        $docs = json_decode($equipment['warranty_documents'], true);
        foreach ($docs as $doc) {
            delete_file(WARRANTY_UPLOAD_PATH . '/' . $doc);
        }
    }
    
    // Delete equipment (cascading will delete custom values and revisions)
    $stmt = $pdo->prepare("DELETE FROM equipments WHERE id = ?");
    $stmt->execute([$equipment_id]);
    
    // Create notification
    create_notification(
        $pdo,
        'Equipment',
        'Deleted',
        'Equipment Deleted',
        "Equipment '{$equipment['label']}' was deleted from the inventory.",
        null,
        get_current_user_id()
    );
    
    log_system($pdo, 'WARNING', "Equipment deleted: {$equipment['label']}", get_current_user_id());
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    log_system($pdo, 'ERROR', "Failed to delete equipment: " . $e->getMessage(), get_current_user_id());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>