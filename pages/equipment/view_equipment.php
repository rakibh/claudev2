<?php
// Folder: pages/equipment/
// File: view_equipment.php
// Purpose: View complete equipment details with type-specific fields and revision history

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($equipment_id === 0) {
    $_SESSION['error_message'] = 'Invalid equipment ID';
    header('Location: list_equipment.php');
    exit;
}

// Get equipment data with type info
$stmt = $pdo->prepare("SELECT e.*, et.type_name, et.has_network, u.first_name, u.last_name, u.employee_id as creator_id 
    FROM equipments e 
    LEFT JOIN equipment_types et ON e.type_id = et.id 
    LEFT JOIN users u ON e.created_by = u.id 
    WHERE e.id = ?");
$stmt->execute([$equipment_id]);
$equipment = $stmt->fetch();

if (!$equipment) {
    $_SESSION['error_message'] = 'Equipment not found';
    header('Location: list_equipment.php');
    exit;
}

// Get custom field values
$stmt = $pdo->prepare("SELECT field_name, field_value FROM equipment_custom_values WHERE equipment_id = ?");
$stmt->execute([$equipment_id]);
$custom_values = [];
while ($row = $stmt->fetch()) {
    $custom_values[$row['field_name']] = $row['field_value'];
}

// Get network info if assigned
$stmt = $pdo->prepare("SELECT * FROM network_info WHERE equipment_id = ?");
$stmt->execute([$equipment_id]);
$network_info = $stmt->fetch();

// Get revision history (Admin only, last 100)
$revisions = [];
if (is_admin()) {
    $stmt = $pdo->prepare("SELECT er.*, u.first_name, u.last_name, u.employee_id 
        FROM equipment_revisions er 
        LEFT JOIN users u ON er.changed_by = u.id 
        WHERE er.equipment_id = ? 
        ORDER BY er.changed_at DESC 
        LIMIT 100");
    $stmt->execute([$equipment_id]);
    $revisions = $stmt->fetchAll();
}

define('PAGE_TITLE', 'View Equipment');

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-eye me-2"></i>Equipment Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="edit_equipment.php?id=<?php echo $equipment_id; ?>" class="btn btn-sm btn-warning">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteEquipment(<?php echo $equipment_id; ?>)">
                <i class="bi bi-trash me-1"></i>Delete
            </button>
        </div>
        <a href="list_equipment.php" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<!-- Basic Information -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <strong>Label/Name:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($equipment['label']); ?></p>
            </div>
            
            <div class="col-md-6">
                <strong>Type:</strong>
                <p class="form-control-plaintext">
                    <span class="badge bg-primary"><?php echo htmlspecialchars($equipment['type_name']); ?></span>
                </p>
            </div>
            
            <div class="col-md-6">
                <strong>Brand:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($equipment['brand'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="col-md-6">
                <strong>Model Number:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($equipment['model_number'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="col-md-6">
                <strong>Serial Number:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($equipment['serial_number'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="col-md-6">
                <strong>Status:</strong>
                <p class="form-control-plaintext">
                    <span class="badge bg-<?php 
                        echo $equipment['status'] === 'In Use' ? 'success' : 
                            ($equipment['status'] === 'Available' ? 'primary' : 
                            ($equipment['status'] === 'Under Repair' ? 'warning' : 'secondary')); 
                    ?>">
                        <?php echo $equipment['status']; ?>
                    </span>
                </p>
            </div>
            
            <?php if ($equipment['condition_status']): ?>
            <div class="col-md-6">
                <strong>Condition:</strong>
                <p class="form-control-plaintext">
                    <span class="badge bg-<?php 
                        echo $equipment['condition_status'] === 'New' ? 'success' : 
                            ($equipment['condition_status'] === 'Good' ? 'info' : 
                            ($equipment['condition_status'] === 'Needs Service' ? 'warning' : 'danger')); 
                    ?>">
                        <?php echo $equipment['condition_status']; ?>
                    </span>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Type-Specific Fields -->
<?php if (!empty($custom_values)): ?>
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-cpu me-2"></i><?php echo htmlspecialchars($equipment['type_name']); ?> Specifications</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($custom_values as $field_name => $field_value): ?>
            <div class="col-md-6">
                <strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $field_name))); ?>:</strong>
                <p class="form-control-plaintext"><?php echo nl2br(htmlspecialchars($field_value)); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Network Information -->
<?php if ($network_info): ?>
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>Network Information</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <strong>IP Address:</strong>
                <p class="form-control-plaintext">
                    <a href="../network/view_network_info.php?id=<?php echo $network_info['id']; ?>" target="_blank">
                        <?php echo htmlspecialchars($network_info['ip_address']); ?>
                        <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </a>
                </p>
            </div>
            
            <div class="col-md-6">
                <strong>MAC Address:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network_info['mac_address'] ?? 'N/A'); ?></p>
            </div>
            
            <?php if ($network_info['cable_no']): ?>
            <div class="col-md-6">
                <strong>Cable No:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network_info['cable_no']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($network_info['patch_panel_no']): ?>
            <div class="col-md-6">
                <strong>Patch Panel No:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network_info['patch_panel_no']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($network_info['patch_panel_port']): ?>
            <div class="col-md-6">
                <strong>Patch Panel Port:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network_info['patch_panel_port']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($network_info['patch_panel_location']): ?>
            <div class="col-md-6">
                <strong>Patch Panel Location:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network_info['patch_panel_location']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($network_info['switch_no']): ?>
            <div class="col-md-6">
                <strong>Switch No:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network_info['switch_no']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($network_info['switch_port']): ?>
            <div class="col-md-6">
                <strong>Switch Port:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network_info['switch_port']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($network_info['switch_location']): ?>
            <div class="col-md-6">
                <strong>Switch Location:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network_info['switch_location']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($network_info['remarks']): ?>
            <div class="col-12">
                <strong>Network Remarks:</strong>
                <p class="form-control-plaintext"><?php echo nl2br(htmlspecialchars($network_info['remarks'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php elseif ($equipment['has_network']): ?>
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>Network Information</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-0">
            <i class="bi bi-info-circle me-2"></i>No network information assigned to this equipment.
            <a href="../network/assign_network_info.php" class="alert-link ms-2">Assign Network Info</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Location Information -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <strong>Location:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($equipment['location'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="col-md-6">
                <strong>Floor No:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($equipment['floor_no'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="col-md-6">
                <strong>Department:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($equipment['department'] ?? 'N/A'); ?></p>
            </div>
            
            <?php if ($equipment['assigned_to_name']): ?>
            <div class="col-md-6">
                <strong>Assigned To:</strong>
                <p class="form-control-plaintext">
                    <?php echo htmlspecialchars($equipment['assigned_to_name']); ?>
                    <?php if ($equipment['assigned_to_designation']): ?>
                    <br><small class="text-muted"><?php echo htmlspecialchars($equipment['assigned_to_designation']); ?></small>
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Warranty Information -->
<?php if ($equipment['warranty_expiry_date'] || $equipment['warranty_documents']): ?>
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Warranty Information</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php if ($equipment['seller_company']): ?>
            <div class="col-md-6">
                <strong>Seller Company:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($equipment['seller_company']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($equipment['purchase_date']): ?>
            <div class="col-md-6">
                <strong>Purchase Date:</strong>
                <p class="form-control-plaintext"><?php echo format_date($equipment['purchase_date']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($equipment['warranty_expiry_date']): ?>
            <div class="col-md-6">
                <strong>Warranty Expiry:</strong>
                <p class="form-control-plaintext">
                    <?php echo format_date($equipment['warranty_expiry_date']); ?>
                    <br>
                    <?php
                    $warranty_status = get_warranty_status($equipment['warranty_expiry_date']);
                    $badge_class = strpos($warranty_status, 'Expired') !== false ? 'danger' : 
                                  (strpos($warranty_status, 'Expiring') !== false ? 'warning' : 'success');
                    ?>
                    <span class="badge bg-<?php echo $badge_class; ?>">
                        <?php echo $warranty_status; ?>
                    </span>
                </p>
            </div>
            <?php endif; ?>
            
            <?php if ($equipment['warranty_documents']): ?>
            <div class="col-12">
                <strong>Warranty Documents:</strong>
                <div class="mt-2">
                    <?php
                    $docs = json_decode($equipment['warranty_documents'], true);
                    if ($docs && is_array($docs)):
                        foreach ($docs as $doc):
                    ?>
                    <a href="<?php echo BASE_URL . '/uploads/warranty/' . $doc; ?>" 
                       class="btn btn-sm btn-outline-primary me-2 mb-2" download>
                        <i class="bi bi-download me-1"></i><?php echo htmlspecialchars($doc); ?>
                    </a>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Custom Fields -->
<?php if ($equipment['custom_label_1'] || $equipment['custom_label_2']): ?>
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-tag me-2"></i>Custom Fields</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php if ($equipment['custom_label_1']): ?>
            <div class="col-md-6">
                <strong><?php echo htmlspecialchars($equipment['custom_label_1']); ?>:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($equipment['custom_value_1'] ?? 'N/A'); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($equipment['custom_label_2']): ?>
            <div class="col-md-6">
                <strong><?php echo htmlspecialchars($equipment['custom_label_2']); ?>:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($equipment['custom_value_2'] ?? 'N/A'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Remarks -->
<?php if ($equipment['remarks']): ?>
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Remarks</h5>
    </div>
    <div class="card-body">
        <p class="mb-0"><?php echo nl2br(htmlspecialchars($equipment['remarks'])); ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Metadata -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Metadata</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <strong>Created By:</strong>
                <p class="form-control-plaintext">
                    <?php echo htmlspecialchars($equipment['first_name'] . ' ' . $equipment['last_name'] . ' (' . $equipment['creator_id'] . ')'); ?>
                </p>
            </div>
            
            <div class="col-md-6">
                <strong>Created At:</strong>
                <p class="form-control-plaintext"><?php echo format_datetime($equipment['created_at']); ?></p>
            </div>
            
            <div class="col-md-6">
                <strong>Last Updated:</strong>
                <p class="form-control-plaintext"><?php echo format_datetime($equipment['updated_at']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Revision History (Admin Only) -->
<?php if (is_admin() && !empty($revisions)): ?>
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Revision History (Last 100 Changes)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Changed By</th>
                        <th>Field Changed</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($revisions as $rev): ?>
                    <tr>
                        <td class="text-nowrap"><?php echo format_datetime($rev['changed_at']); ?></td>
                        <td>
                            <?php 
                            if ($rev['first_name']) {
                                echo htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name'] . ' (' . $rev['employee_id'] . ')');
                            } else {
                                echo 'System';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($rev['field_name']); ?></td>
                        <td><?php echo htmlspecialchars(substr($rev['old_value'] ?? 'N/A', 0, 50)) . (strlen($rev['old_value'] ?? '') > 50 ? '...' : ''); ?></td>
                        <td><?php echo htmlspecialchars(substr($rev['new_value'] ?? 'N/A', 0, 50)) . (strlen($rev['new_value'] ?? '') > 50 ? '...' : ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function deleteEquipment(equipId) {
    if (confirm('Are you sure you want to delete this equipment?\n\nThis action cannot be undone and will also delete:\n- All type-specific field data\n- Warranty documents\n- Revision history\n\nNetwork information (if any) will be unassigned but not deleted.')) {
        const originalBtn = event.target.closest('button');
        const originalText = originalBtn.innerHTML;
        originalBtn.disabled = true;
        originalBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';
        
        fetch('delete_equipment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${equipId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'list_equipment.php';
            } else {
                alert(data.message || 'Failed to delete equipment');
                originalBtn.disabled = false;
                originalBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting equipment');
            originalBtn.disabled = false;
            originalBtn.innerHTML = originalText;
        });
    }
}
</script>

<?php include '../../includes/footer.php'; ?>