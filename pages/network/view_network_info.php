<?php
// Folder: pages/network/
// File: view_network_info.php
// Purpose: View complete network information details with all fields

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

$stmt = $pdo->prepare("SELECT n.*, e.label as equipment_label, e.id as equipment_id, 
    u.first_name, u.last_name, u.employee_id as creator_id
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

// Get revision history (Admin only, last 100)
$revisions = [];
if (is_admin()) {
    $stmt = $pdo->prepare("SELECT nr.*, u.first_name, u.last_name, u.employee_id 
        FROM network_revisions nr 
        LEFT JOIN users u ON nr.changed_by = u.id 
        WHERE nr.network_id = ? 
        ORDER BY nr.changed_at DESC 
        LIMIT 100");
    $stmt->execute([$network_id]);
    $revisions = $stmt->fetchAll();
}

define('PAGE_TITLE', 'View Network Info');
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-eye me-2"></i>Network Information Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="edit_network_info.php?id=<?php echo $network_id; ?>" class="btn btn-sm btn-warning">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            <?php if (!$network['equipment_id']): ?>
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteNetwork(<?php echo $network_id; ?>)">
                <i class="bi bi-trash me-1"></i>Delete
            </button>
            <?php endif; ?>
        </div>
        <a href="list_network_info.php" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>
</div>

<!-- Network Identification -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>Network Identification</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <strong>IP Address:</strong>
                <p class="form-control-plaintext">
                    <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($network['ip_address']); ?></span>
                </p>
            </div>
            
            <div class="col-md-6">
                <strong>MAC Address:</strong>
                <p class="form-control-plaintext">
                    <?php if ($network['mac_address']): ?>
                        <code class="text-dark"><?php echo htmlspecialchars($network['mac_address']); ?></code>
                    <?php else: ?>
                        <span class="text-muted">N/A</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Cable Information -->
<?php if ($network['cable_no']): ?>
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-ethernet me-2"></i>Cable Information</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-12">
                <strong>Cable Number:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network['cable_no']); ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Patch Panel Information -->
<?php if ($network['patch_panel_no'] || $network['patch_panel_port'] || $network['patch_panel_location']): ?>
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-collection me-2"></i>Patch Panel Information</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php if ($network['patch_panel_no']): ?>
            <div class="col-md-4">
                <strong>Patch Panel No:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network['patch_panel_no']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($network['patch_panel_port']): ?>
            <div class="col-md-4">
                <strong>Patch Panel Port:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network['patch_panel_port']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($network['patch_panel_location']): ?>
            <div class="col-md-4">
                <strong>Patch Panel Location:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network['patch_panel_location']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Switch Information -->
<?php if ($network['switch_no'] || $network['switch_port'] || $network['switch_location']): ?>
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-router me-2"></i>Switch Information</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php if ($network['switch_no']): ?>
            <div class="col-md-4">
                <strong>Switch Number:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network['switch_no']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($network['switch_port']): ?>
            <div class="col-md-4">
                <strong>Switch Port:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network['switch_port']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($network['switch_location']): ?>
            <div class="col-md-4">
                <strong>Switch Location:</strong>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($network['switch_location']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Equipment Assignment -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-pc-display me-2"></i>Equipment Assignment</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-12">
                <strong>Attached Equipment:</strong>
                <p class="form-control-plaintext">
                    <?php if ($network['equipment_id']): ?>
                    <a href="../equipment/view_equipment.php?id=<?php echo $network['equipment_id']; ?>" 
                       class="btn btn-sm btn-outline-primary" target="_blank">
                        <i class="bi bi-pc-display me-1"></i>
                        <?php echo htmlspecialchars($network['equipment_label']); ?>
                        <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" 
                            onclick="unassignNetwork(<?php echo $network_id; ?>)">
                        <i class="bi bi-x-circle me-1"></i>Unassign
                    </button>
                    <?php else: ?>
                    <span class="text-muted me-3">
                        <i class="bi bi-info-circle me-1"></i>This network information is not assigned to any equipment
                    </span>
                    <a href="assign_network_info.php" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-link me-1"></i>Assign to Equipment
                    </a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Remarks -->
<?php if ($network['remarks']): ?>
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Remarks</h5>
    </div>
    <div class="card-body">
        <p class="mb-0"><?php echo nl2br(htmlspecialchars($network['remarks'])); ?></p>
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
            <div class="col-md-4">
                <strong>Created By:</strong>
                <p class="form-control-plaintext">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars($network['first_name'] . ' ' . $network['last_name']); ?>
                    <br>
                    <small class="text-muted">Employee ID: <?php echo htmlspecialchars($network['creator_id']); ?></small>
                </p>
            </div>
            
            <div class="col-md-4">
                <strong>Created At:</strong>
                <p class="form-control-plaintext">
                    <i class="bi bi-calendar-plus me-1"></i>
                    <?php echo format_datetime($network['created_at']); ?>
                </p>
            </div>
            
            <div class="col-md-4">
                <strong>Last Updated:</strong>
                <p class="form-control-plaintext">
                    <i class="bi bi-calendar-check me-1"></i>
                    <?php echo format_datetime($network['updated_at']); ?>
                </p>
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
                <thead class="table-light">
                    <tr>
                        <th style="width: 180px;">Timestamp</th>
                        <th style="width: 200px;">Changed By</th>
                        <th style="width: 150px;">Field Changed</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($revisions as $rev): ?>
                    <tr>
                        <td class="text-nowrap">
                            <small><?php echo format_datetime($rev['changed_at']); ?></small>
                        </td>
                        <td>
                            <?php 
                            if ($rev['first_name']) {
                                echo '<i class="bi bi-person me-1"></i>';
                                echo htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']);
                                echo '<br><small class="text-muted">' . htmlspecialchars($rev['employee_id']) . '</small>';
                            } else {
                                echo '<i class="bi bi-gear me-1"></i>System';
                            }
                            ?>
                        </td>
                        <td>
                            <code class="text-dark"><?php echo htmlspecialchars($rev['field_name']); ?></code>
                        </td>
                        <td>
                            <?php 
                            $old_val = $rev['old_value'] ?? 'N/A';
                            if (strlen($old_val) > 50) {
                                echo '<span title="' . htmlspecialchars($old_val) . '">';
                                echo htmlspecialchars(substr($old_val, 0, 50)) . '...';
                                echo '</span>';
                            } else {
                                echo htmlspecialchars($old_val);
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $new_val = $rev['new_value'] ?? 'N/A';
                            if (strlen($new_val) > 50) {
                                echo '<span title="' . htmlspecialchars($new_val) . '">';
                                echo htmlspecialchars(substr($new_val, 0, 50)) . '...';
                                echo '</span>';
                            } else {
                                echo htmlspecialchars($new_val);
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function deleteNetwork(networkId) {
    if (confirm('Are you sure you want to delete this network information?\n\nThis action cannot be undone.')) {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';
        
        fetch('delete_network_info.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${networkId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'list_network_info.php';
            } else {
                alert(data.message || 'Failed to delete network information');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting network information');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
}

function unassignNetwork(networkId) {
    if (confirm('Unassign this network information from equipment?\n\nThe network information will remain but will no longer be linked to any equipment.')) {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';
        
        fetch('unassign_network_info.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${networkId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to unassign network information');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
}
</script>

<?php include '../../includes/footer.php'; ?>