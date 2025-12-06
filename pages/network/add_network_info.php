<?php
// Folder: pages/network/
// File: add_network_info.php
// Purpose: Add new network information with all fields visible

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
    } else {
        $stmt = $pdo->prepare("SELECT id FROM network_info WHERE ip_address = ?");
        $stmt->execute([$ip_address]);
        if ($stmt->fetch()) {
            $errors[] = 'IP address already exists';
        }
    }
    
    // Check MAC address uniqueness (if not N/A)
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
            
            $network_id = $pdo->lastInsertId();
            
            create_notification($pdo, 'Network', 'Added', 'New Network Info Added', 
                "IP Address {$ip_address} has been added to the system.", $network_id, get_current_user_id());
            
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
    <a href="list_network_info.php" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to List
    </a>
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
                           value="<?php echo htmlspecialchars($_POST['ip_address'] ?? ''); ?>" 
                           placeholder="e.g., 192.168.1.100" required>
                    <small class="text-muted">IPv4 format (e.g., 192.168.1.100)</small>
                </div>
                
                <div class="col-md-6">
                    <label for="mac_address" class="form-label">MAC Address</label>
                    <input type="text" class="form-control" id="mac_address" name="mac_address" 
                           value="<?php echo htmlspecialchars($_POST['mac_address'] ?? ''); ?>" 
                           placeholder="e.g., 00:1A:2B:3C:4D:5E">
                    <small class="text-muted">Format: XX:XX:XX:XX:XX:XX or N/A</small>
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
                           value="<?php echo htmlspecialchars($_POST['cable_no'] ?? ''); ?>" 
                           placeholder="e.g., CAB-001">
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
                           value="<?php echo htmlspecialchars($_POST['patch_panel_no'] ?? ''); ?>" 
                           placeholder="e.g., PP-01">
                </div>
                
                <div class="col-md-4">
                    <label for="patch_panel_port" class="form-label">Patch Panel Port</label>
                    <input type="text" class="form-control" id="patch_panel_port" name="patch_panel_port" 
                           value="<?php echo htmlspecialchars($_POST['patch_panel_port'] ?? ''); ?>" 
                           placeholder="e.g., Port 12">
                </div>
                
                <div class="col-md-4">
                    <label for="patch_panel_location" class="form-label">Patch Panel Location</label>
                    <input type="text" class="form-control" id="patch_panel_location" name="patch_panel_location" 
                           value="<?php echo htmlspecialchars($_POST['patch_panel_location'] ?? ''); ?>" 
                           placeholder="e.g., Server Room A">
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
                           value="<?php echo htmlspecialchars($_POST['switch_no'] ?? ''); ?>" 
                           placeholder="e.g., SW-01">
                </div>
                
                <div class="col-md-4">
                    <label for="switch_port" class="form-label">Switch Port</label>
                    <input type="text" class="form-control" id="switch_port" name="switch_port" 
                           value="<?php echo htmlspecialchars($_POST['switch_port'] ?? ''); ?>" 
                           placeholder="e.g., Port 24">
                </div>
                
                <div class="col-md-4">
                    <label for="switch_location" class="form-label">Switch Location</label>
                    <input type="text" class="form-control" id="switch_location" name="switch_location" 
                           value="<?php echo htmlspecialchars($_POST['switch_location'] ?? ''); ?>" 
                           placeholder="e.g., 3rd Floor Network Room">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Equipment Assignment -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-pc-display me-2"></i>Equipment Assignment (Optional)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="equipment_id" class="form-label">Attach to Equipment</label>
                    <select class="form-select" id="equipment_id" name="equipment_id">
                        <option value="">Select Equipment (Optional)</option>
                        <?php foreach ($available_equipment as $equip): ?>
                        <option value="<?php echo $equip['id']; ?>" <?php echo ($_POST['equipment_id'] ?? '') == $equip['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($equip['label'] . ' (' . ($equip['serial_number'] ?? 'N/A') . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">You can assign network info to equipment later</small>
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
                    <textarea class="form-control" id="remarks" name="remarks" rows="3" 
                              placeholder="Any additional notes about this network configuration..."><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="list_network_info.php" class="btn btn-secondary">
            <i class="bi bi-x-circle me-1"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-2"></i>Save Network Information
        </button>
    </div>
</form>

<script>
// Real-time IP validation
document.getElementById('ip_address').addEventListener('blur', function() {
    const ip = this.value.trim();
    const ipPattern = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
    
    if (ip && !ipPattern.test(ip)) {
        this.classList.add('is-invalid');
        if (!document.getElementById('ip_error')) {
            const error = document.createElement('div');
            error.id = 'ip_error';
            error.className = 'invalid-feedback';
            error.textContent = 'Please enter a valid IPv4 address (e.g., 192.168.1.100)';
            this.parentNode.appendChild(error);
        }
    } else {
        this.classList.remove('is-invalid');
        const error = document.getElementById('ip_error');
        if (error) error.remove();
    }
});

// Form change tracking
let formChanged = false;
const formInputs = document.querySelectorAll('input, textarea, select');
formInputs.forEach(input => {
    input.addEventListener('change', () => {
        formChanged = true;
    });
});

document.querySelector('form').addEventListener('submit', () => {
    formChanged = false;
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});
</script>

<?php include '../../includes/footer.php'; ?>