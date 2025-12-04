<?php
// ==================== pages/network/add_network_info.php ====================
// Folder: pages/network/
// File: add_network_info.php
// Purpose: Add new network information

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

// Get unassigned equipment
$stmt = $pdo->query("SELECT e.id, e.label, e.serial_number FROM equipments e 
    LEFT JOIN network_info n ON e.id = n.equipment_id 
    WHERE n.equipment_id IS NULL 
    ORDER BY e.label");
$available_equipment = $stmt->fetchAll();

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
    $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : null;
    $remarks = sanitize_input($_POST['remarks'] ?? '');
    
    $errors = [];
    
    if (empty($ip_address)) {
        $errors[] = 'IP Address is required';
    } elseif (!validate_ip($ip_address)) {
        $errors[] = 'Invalid IP address format';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM network_info WHERE ip_address = ?");
        $stmt->execute([$ip_address]);
        if ($stmt->fetch()) {
            $errors[] = 'IP address already exists';
        }
    }
    
    if ($mac_address && $mac_address !== 'N/A') {
        $stmt = $pdo->prepare("SELECT id FROM network_info WHERE mac_address = ?");
        $stmt->execute([$mac_address]);
        if ($stmt->fetch()) {
            $errors[] = 'MAC address already exists';
        }
    }
    
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO network_info (ip_address, mac_address, cable_no, patch_panel_no, 
                    patch_panel_port, patch_panel_location, switch_no, switch_port, switch_location, 
                    equipment_id, remarks, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
                get_current_user_id()
            ]);
            
            create_notification($pdo, 'Network', 'Added', 'New Network Info Added', 
                "IP Address {$ip_address} has been added.", $pdo->lastInsertId(), get_current_user_id());
            
            log_system($pdo, 'INFO', "Network info added: {$ip_address}", get_current_user_id());
            
            $_SESSION['success_message'] = 'Network information added successfully';
            header('Location: list_network_info.php');
            exit;
        } catch (PDOException $e) {
            log_system($pdo, 'ERROR', "Failed to add network info: " . $e->getMessage(), get_current_user_id());
            $errors[] = 'Database error occurred';
        }
    }
}

define('PAGE_TITLE', 'Add Network Info');
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-plus-circle me-2"></i>Add Network Information</h1>
    <a href="list_network_info.php" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e); ?></div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="ip_address" class="form-label">IP Address <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ip_address" name="ip_address" required>
                </div>
                <div class="col-md-6">
                    <label for="mac_address" class="form-label">MAC Address</label>
                    <input type="text" class="form-control" id="mac_address" name="mac_address">
                </div>
                <div class="col-md-6">
                    <label for="switch_no" class="form-label">Switch No</label>
                    <input type="text" class="form-control" id="switch_no" name="switch_no">
                </div>
                <div class="col-md-6">
                    <label for="equipment_id" class="form-label">Attach Equipment (Optional)</label>
                    <select class="form-select" id="equipment_id" name="equipment_id">
                        <option value="">Select Equipment</option>
                        <?php foreach ($available_equipment as $equip): ?>
                        <option value="<?php echo $equip['id']; ?>">
                            <?php echo htmlspecialchars($equip['label'] . ' (' . ($equip['serial_number'] ?? 'N/A') . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
</form>

<?php include '../../includes/footer.php'; ?>