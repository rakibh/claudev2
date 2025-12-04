<?php
// ==================== pages/network/view_network_info.php ====================
// Folder: pages/network/
// File: view_network_info.php
// Purpose: View network information details

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

$network_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($network_id === 0) {
    $_SESSION['error_message'] = 'Invalid network ID';
    header('Location: list_network_info.php');
    exit;
}

$stmt = $pdo->prepare("SELECT n.*, e.label as equipment_label, u.first_name, u.last_name 
    FROM network_info n 
    LEFT JOIN equipments e ON n.equipment_id = e.id 
    LEFT JOIN users u ON n.created_by = u.id 
    WHERE n.id = ?");
$stmt->execute([$network_id]);
$network = $stmt->fetch();

if (!$network) {
    $_SESSION['error_message'] = 'Network information not found';
    header('Location: list_network_info.php');
    exit;
}

define('PAGE_TITLE', 'View Network Info');
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-eye me-2"></i>Network Information</h1>
    <div class="btn-group">
        <a href="edit_network_info.php?id=<?php echo $network_id; ?>" class="btn btn-sm btn-warning">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <a href="list_network_info.php" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6"><strong>IP Address:</strong> <?php echo htmlspecialchars($network['ip_address']); ?></div>
            <div class="col-md-6"><strong>MAC Address:</strong> <?php echo htmlspecialchars($network['mac_address'] ?? 'N/A'); ?></div>
            <div class="col-md-6"><strong>Switch No:</strong> <?php echo htmlspecialchars($network['switch_no'] ?? 'N/A'); ?></div>
            <div class="col-md-6"><strong>Attached Equipment:</strong>
                <?php if ($network['equipment_id']): ?>
                <a href="../equipment/view_equipment.php?id=<?php echo $network['equipment_id']; ?>">
                    <?php echo htmlspecialchars($network['equipment_label']); ?>
                </a>
                <?php else: ?>
                <span class="text-muted">Unassigned</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
