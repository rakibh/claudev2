<?php
// ==================== pages/network/edit_network_info.php ====================
// Folder: pages/network/
// File: edit_network_info.php
// Purpose: Edit network information

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

$stmt = $pdo->prepare("SELECT * FROM network_info WHERE id = ?");
$stmt->execute([$network_id]);
$network = $stmt->fetch();

if (!$network) {
    $_SESSION['error_message'] = 'Network information not found';
    header('Location: list_network_info.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip_address = sanitize_input($_POST['ip_address'] ?? '');
    
    $errors = [];
    
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
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE network_info SET ip_address = ?, mac_address = ?, switch_no = ? WHERE id = ?");
            $stmt->execute([$ip_address, $_POST['mac_address'] ?: null, $_POST['switch_no'] ?: null, $network_id]);
            
            create_notification($pdo, 'Network', 'Updated', 'Network Info Updated', 
                "IP Address {$ip_address} information was updated.", $network_id, get_current_user_id());
            
            $_SESSION['success_message'] = 'Network information updated successfully';
            header('Location: view_network_info.php?id=' . $network_id);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error occurred';
        }
    }
}

define('PAGE_TITLE', 'Edit Network Info');
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-pencil-square me-2"></i>Edit Network Information</h1>
    <a href="view_network_info.php?id=<?php echo $network_id; ?>" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<form method="POST">
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="ip_address" class="form-label">IP Address <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ip_address" name="ip_address" 
                           value="<?php echo htmlspecialchars($network['ip_address']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="mac_address" class="form-label">MAC Address</label>
                    <input type="text" class="form-control" id="mac_address" name="mac_address" 
                           value="<?php echo htmlspecialchars($network['mac_address'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Update</button>
</form>

<?php include '../../includes/footer.php'; ?>

