<?php
// Folder: pages/network/
// File: edit_network_info.php
// Purpose: Edit network information with all fields

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

// Get network data
$stmt = $pdo->prepare("SELECT * FROM network_info WHERE id = ?");
$stmt->execute([$network_id]);
$network = $stmt->fetch();

if (!$network) {
    $_SESSION['error_message'] = 'Network information not found';
    header('Location: list_network_info.php');
    exit;
}

// Get available equipment (excluding currently assigned if any)
$sql = "SELECT e.id, e.label, e.serial_number FROM equipments e 
        LEFT JOIN network_info n ON e.id = n.equipment_id 
        WHERE n.equipment_id IS NULL OR e.id = ?
        ORDER BY e.label";
$stmt = $pdo->prepare($sql);
$stmt->execute([$network['equipment_id']]);
$available_equipment = $stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip_address = sanitize_input($_POST['ip_address'] ?? '');
    $mac_address = sanitize_input($_POST['mac_address'] ?? '');
    $cable_no = sanitize_input($_POST['cable_no'] ?? '');
    $patch_panel_no = sanitize_input($_POST['patch_panel_no'] ?? '');
    $patch_panel_port = sanitize_input($_POST['patch_panel_port'] ?? '');
    $patch_panel_location = sanitize_input($_POST['patch_panel_location'] ?? '');
    $switch_no = sanitize_input($_POST['switch_no'] ?? '');
    $switch_port = sanitize_input($_POST['switch_port'] ?? '');
    $switch_location = sanitize_input($_POST['switch_location'] ?? '');
    $equipment_id = isset($_POST['equipment_id']) && $_POST['equipment_id'] !== '' ? (int)$_POST['equipment_id'] : null;
    $remarks = sanitize_input($_POST['remarks'] ?? '');
    
    // Validation
    if (empty($ip_address)) {
        $errors[] = 'IP Address is required';
    } elseif (!validate_ip($ip_address)) {
        $errors[] = 'Invalid IP address format';
    } elseif ($ip_address !== $network['ip_address']) {
        $stmt = $pdo->prepare("SELECT id FROM network_info WHERE ip_address = ? AND id != ?");
        $stmt->execute([$ip_address, $network_id]);
        if ($stmt->fetch()) {
            $errors[] = 'IP address already exists';
        }
    }
    
    // Check MAC address uniqueness
    if ($mac_address && $mac_address !== 'N/A' && $mac_address !== $network['mac_address']) {
        $stmt = $pdo->prepare("SELECT id FROM network_info WHERE mac_address = ? AND id != ?");
        $stmt->execute([$mac_address, $network_id]);
        if ($stmt->fetch()) {
            $errors[] = 'MAC address already exists';
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $sql = "UPDATE network_info SET ip_address = ?, mac_address = ?, cable_no = ?, 
                    patch_panel_no = ?, patch_panel_port = ?, patch_panel_location = ?, 
                    switch_no = ?, switch_port = ?, switch_location = ?, equipment_id = ?, remarks = ? 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $ip_address,
                $mac_address ?: null,
                $cable_no ?: null,
                $patch_panel_no ?: null,
                $patch_panel_port ?: null,
                $patch_panel_location ?: null,
                $switch_no ?: null,
                $switch_port ?: null,
                $switch_location ?: null,
                $equipment_id,
                $remarks ?: null,
                $network_id
            ]);
            
            // Log revisions for changed fields
            $changed_fields = [
                'ip_address' => $ip_address,
                'mac_address' => $mac_address,
                'cable_no' => $cable_no,
                'patch_panel_no' => $patch_panel_no,
                'patch_panel_port' => $patch_panel_port,
                'patch_panel_location' => $patch_panel_location,
                'switch_no' => $switch_no,
                'switch_port' => $switch_port,
                'switch_location' => $switch_location,
                'equipment_id' => $equipment_id,
                'remarks' => $remarks
            ];
            
            foreach ($changed_fields as $field => $new_value) {
                if ($new_value != $network[$field]) {
                    log_network_revision($pdo, $network_id, get_current_user_id(), $field, $network[$field], $new_value);
                }
            }
            
            create_notification($pdo, 'Network', 'Updated', 'Network Info Updated', 
                "IP Address {$ip_address} information was updated.", $network_id, get_current_user_id());
            
            log_system($pdo, 'INFO', "Network info updated: {$ip_address}", get_current_user_id());
            
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Network information updated successfully';
            header('Location: view_network_info.php?id=' . $network_id);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            log_system($pdo, 'ERROR', "Failed to update network info: " . $e->getMessage(), get_current_user_id());
            $errors[] = 'Database error occurred';
        }
    }
}

define('PAGE_TITLE', 'Edit Network Info');
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-pencil-square me-2"></i>Edit Network Information</h1>
    <div class="btn-group">
        <a href="view_network_info.php?id=<?php echo $network_id; ?>" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">
    <!-- IP and MAC Address -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>Network Identification</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="ip_address" class="form-label">IP Address <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ip_address" name="ip_address" 
                           value="<?php echo htmlspecialchars($network['ip_address']); ?>" 
                           placeholder="e.g., 192.168.1.100" required>
                </div>
                
                <div class="col-md-6">
                    <label for="mac_address" class="form-label">MAC Address</label>
                    <input type="text" class="form-control" id="mac_address" name="mac_address" 
                           value="<?php echo htmlspecialchars($network['mac_address'] ?? ''); ?>" 
                           placeholder="e.g., 00:1A:2B:3C:4D:5E">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cable Information -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-ethernet me-2"></i>Cable Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="cable_no" class="form-label">Cable Number</label>
                    <input type="text" class="form-control" id="cable_no" name="cable_no" 
                           value="<?php echo htmlspecialchars($network['cable_no'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Patch Panel Information -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-collection me-2"></i>Patch Panel Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="patch_panel_no" class="form-label">Patch Panel No</label>
                    <input type="text" class="form-control" id="patch_panel_no" name="patch_panel_no" 
                           value="<?php echo htmlspecialchars($network['patch_panel_no'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="patch_panel_port" class="form-label">Patch Panel Port</label>
                    <input type="text" class="form-control" id="patch_panel_port" name="patch_panel_port" 
                           value="<?php echo htmlspecialchars($network['patch_panel_port'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="patch_panel_location" class="form-label">Patch Panel Location</label>
                    <input type="text" class="form-control" id="patch_panel_location" name="patch_panel_location" 
                           value="<?php echo htmlspecialchars($network['patch_panel_location'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Switch Information -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-router me-2"></i>Switch Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="switch_no" class="form-label">Switch Number</label>
                    <input type="text" class="form-control" id="switch_no" name="switch_no" 
                           value="<?php echo htmlspecialchars($network['switch_no'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="switch_port" class="form-label">Switch Port</label>
                    <input type="text" class="form-control" id="switch_port" name="switch_port" 
                           value="<?php echo htmlspecialchars($network['switch_port'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="switch_location" class="form-label">Switch Location</label>
                    <input type="text" class="form-control" id="switch_location" name="switch_location" 
                           value="<?php echo htmlspecialchars($network['switch_location'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Equipment Assignment -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-pc-display me-2"></i>Equipment Assignment</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="equipment_id" class="form-label">Attached Equipment</label>
                    <select class="form-select" id="equipment_id" name="equipment_id">
                        <option value="">Unassigned</option>
                        <?php foreach ($available_equipment as $equip): ?>
                        <option value="<?php echo $equip['id']; ?>" <?php echo $network['equipment_id'] == $equip['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($equip['label'] . ' (' . ($equip['serial_number'] ?? 'N/A') . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Remarks -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Additional Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea class="form-control" id="remarks" name="remarks" rows="3"><?php echo htmlspecialchars($network['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="view_network_info.php?id=<?php echo $network_id; ?>" class="btn btn-secondary">
            <i class="bi bi-x-circle me-1"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-2"></i>Save Changes
        </button>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>