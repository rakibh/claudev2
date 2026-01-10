<?php
// Folder: pages/equipment/
// File: edit_equipment.php
// Purpose: Edit equipment with AJAX dynamic type-specific fields

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

// Get equipment data
$stmt = $pdo->prepare("SELECT e.*, et.type_name, et.has_network 
    FROM equipments e 
    LEFT JOIN equipment_types et ON e.type_id = et.id 
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

// Get equipment types
$types = $pdo->query("SELECT * FROM equipment_types ORDER BY type_name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label = sanitize_input($_POST['label'] ?? '');
    $type_id = (int)($_POST['type_id'] ?? 0);
    $brand = sanitize_input($_POST['brand'] ?? '');
    $model_number = sanitize_input($_POST['model_number'] ?? '');
    $serial_number = sanitize_input($_POST['serial_number'] ?? '');
    $location = sanitize_input($_POST['location'] ?? '');
    $floor_no = sanitize_input($_POST['floor_no'] ?? '');
    $department = sanitize_input($_POST['department'] ?? '');
    $assigned_to_name = sanitize_input($_POST['assigned_to_name'] ?? '');
    $assigned_to_designation = sanitize_input($_POST['assigned_to_designation'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'Available');
    $condition_status = sanitize_input($_POST['condition_status'] ?? '');
    $remarks = sanitize_input($_POST['remarks'] ?? '');
    $seller_company = sanitize_input($_POST['seller_company'] ?? '');
    $purchase_date = sanitize_input($_POST['purchase_date'] ?? '');
    $warranty_expiry_date = sanitize_input($_POST['warranty_expiry_date'] ?? '');
    $custom_label_1 = sanitize_input($_POST['custom_label_1'] ?? '');
    $custom_value_1 = sanitize_input($_POST['custom_value_1'] ?? '');
    $custom_label_2 = sanitize_input($_POST['custom_label_2'] ?? '');
    $custom_value_2 = sanitize_input($_POST['custom_value_2'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($label)) {
        $errors[] = 'Label/Name is required';
    }
    
    if ($type_id === 0) {
        $errors[] = 'Equipment type is required';
    }
    
    // Check serial number uniqueness
    if ($serial_number && $serial_number !== 'N/A' && $serial_number !== $equipment['serial_number']) {
        $stmt = $pdo->prepare("SELECT id FROM equipments WHERE serial_number = ? AND id != ?");
        $stmt->execute([$serial_number, $equipment_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Serial number already exists';
        }
    }
    
    // Handle warranty documents
    $warranty_documents = $equipment['warranty_documents'] ? json_decode($equipment['warranty_documents'], true) : [];
    
    if (isset($_FILES['warranty_documents']) && !empty($_FILES['warranty_documents']['name'][0])) {
        foreach ($_FILES['warranty_documents']['name'] as $key => $name) {
            if ($_FILES['warranty_documents']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['warranty_documents']['name'][$key],
                    'type' => $_FILES['warranty_documents']['type'][$key],
                    'tmp_name' => $_FILES['warranty_documents']['tmp_name'][$key],
                    'error' => $_FILES['warranty_documents']['error'][$key],
                    'size' => $_FILES['warranty_documents']['size'][$key]
                ];
                
                $upload_result = upload_file($file, WARRANTY_UPLOAD_PATH, MAX_WARRANTY_SIZE, ALLOWED_DOCUMENT_TYPES);
                
                if ($upload_result['success']) {
                    $warranty_documents[] = $upload_result['filename'];
                } else {
                    $errors[] = $upload_result['message'];
                }
            }
        }
    }
    
    // Handle document deletion
    if (isset($_POST['delete_documents']) && is_array($_POST['delete_documents'])) {
        foreach ($_POST['delete_documents'] as $doc) {
            delete_file(WARRANTY_UPLOAD_PATH . '/' . $doc);
            $warranty_documents = array_diff($warranty_documents, [$doc]);
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update equipment
            $sql = "UPDATE equipments SET label = ?, type_id = ?, brand = ?, model_number = ?, 
                    serial_number = ?, location = ?, floor_no = ?, department = ?, 
                    assigned_to_name = ?, assigned_to_designation = ?, status = ?, condition_status = ?, 
                    remarks = ?, seller_company = ?, purchase_date = ?, warranty_expiry_date = ?, 
                    warranty_documents = ?, custom_label_1 = ?, custom_value_1 = ?, 
                    custom_label_2 = ?, custom_value_2 = ? 
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $label,
                $type_id,
                $brand ?: null,
                $model_number ?: null,
                $serial_number ?: null,
                $location ?: null,
                $floor_no ?: null,
                $department ?: null,
                $assigned_to_name ?: null,
                $assigned_to_designation ?: null,
                $status,
                $condition_status ?: null,
                $remarks ?: null,
                $seller_company ?: null,
                $purchase_date ?: null,
                $warranty_expiry_date ?: null,
                !empty($warranty_documents) ? json_encode(array_values($warranty_documents)) : null,
                $custom_label_1 ?: null,
                $custom_value_1 ?: null,
                $custom_label_2 ?: null,
                $custom_value_2 ?: null,
                $equipment_id
            ]);
            
            // Log revisions for changed fields
            $changed_fields = [
                'label' => $label,
                'type_id' => $type_id,
                'brand' => $brand,
                'model_number' => $model_number,
                'serial_number' => $serial_number,
                'location' => $location,
                'floor_no' => $floor_no,
                'department' => $department,
                'status' => $status,
                'condition_status' => $condition_status
            ];
            
            foreach ($changed_fields as $field => $new_value) {
                if ($new_value != $equipment[$field]) {
                    log_equipment_revision($pdo, $equipment_id, get_current_user_id(), $field, $equipment[$field], $new_value);
                }
            }
            
            // Delete existing custom values
            $stmt = $pdo->prepare("DELETE FROM equipment_custom_values WHERE equipment_id = ?");
            $stmt->execute([$equipment_id]);
            
            // Insert updated type-specific custom fields
            if (isset($_POST['custom_fields'])) {
                $sql = "INSERT INTO equipment_custom_values (equipment_id, field_name, field_value) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                foreach ($_POST['custom_fields'] as $field_name => $field_value) {
                    if (!empty($field_value)) {
                        if (is_array($field_value)) {
                            $field_value = implode(', ', $field_value);
                        }
                        $stmt->execute([$equipment_id, $field_name, sanitize_input($field_value)]);
                    }
                }
            }
            
            // Create notification
            create_notification(
                $pdo,
                'Equipment',
                'Updated',
                'Equipment Updated',
                "Equipment '{$label}' has been updated.",
                $equipment_id,
                get_current_user_id()
            );
            
            log_system($pdo, 'INFO', "Equipment updated: {$label}", get_current_user_id());
            
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Equipment updated successfully';
            header('Location: view_equipment.php?id=' . $equipment_id);
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            log_system($pdo, 'ERROR', "Failed to update equipment: " . $e->getMessage(), get_current_user_id());
            $errors[] = 'Database error occurred';
        }
    }
}

define('PAGE_TITLE', 'Edit Equipment');

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-pencil-square me-2"></i>Edit Equipment</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="view_equipment.php?id=<?php echo $equipment_id; ?>" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to View
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

<form method="POST" enctype="multipart/form-data" id="equipmentForm">
    <!-- Identification Block -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Identification</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="label" class="form-label">Label/Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="label" name="label" 
                           value="<?php echo htmlspecialchars($equipment['label']); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="type_id" class="form-label">Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="type_id" name="type_id" required>
                        <option value="">Select Type</option>
                        <?php foreach ($types as $type): ?>
                        <option value="<?php echo $type['id']; ?>" <?php echo $equipment['type_id'] == $type['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['type_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="brand" class="form-label">Brand/Manufacturer</label>
                    <input type="text" class="form-control" id="brand" name="brand" 
                           value="<?php echo htmlspecialchars($equipment['brand'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="model_number" class="form-label">Model Number</label>
                    <input type="text" class="form-control" id="model_number" name="model_number" 
                           value="<?php echo htmlspecialchars($equipment['model_number'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="serial_number" class="form-label">Serial Number</label>
                    <input type="text" class="form-control" id="serial_number" name="serial_number" 
                           value="<?php echo htmlspecialchars($equipment['serial_number'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Type-Specific Fields Container -->
    <div id="typeSpecificFieldsContainer"></div>
    
    <!-- Location Block -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Location</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" 
                           value="<?php echo htmlspecialchars($equipment['location'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="floor_no" class="form-label">Floor No</label>
                    <input type="text" class="form-control" id="floor_no" name="floor_no" 
                           value="<?php echo htmlspecialchars($equipment['floor_no'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" class="form-control" id="department" name="department" 
                           value="<?php echo htmlspecialchars($equipment['department'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="assigned_to_name" class="form-label">Assigned To - Name</label>
                    <input type="text" class="form-control" id="assigned_to_name" name="assigned_to_name" 
                           value="<?php echo htmlspecialchars($equipment['assigned_to_name'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="assigned_to_designation" class="form-label">Assigned To - Designation</label>
                    <input type="text" class="form-control" id="assigned_to_designation" name="assigned_to_designation" 
                           value="<?php echo htmlspecialchars($equipment['assigned_to_designation'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Status & Condition Block -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Status & Condition</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="In Use" <?php echo $equipment['status'] === 'In Use' ? 'selected' : ''; ?>>In Use</option>
                        <option value="Available" <?php echo $equipment['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Under Repair" <?php echo $equipment['status'] === 'Under Repair' ? 'selected' : ''; ?>>Under Repair</option>
                        <option value="Retired" <?php echo $equipment['status'] === 'Retired' ? 'selected' : ''; ?>>Retired</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="condition_status" class="form-label">Condition</label>
                    <select class="form-select" id="condition_status" name="condition_status">
                        <option value="">Select Condition</option>
                        <option value="New" <?php echo $equipment['condition_status'] === 'New' ? 'selected' : ''; ?>>New</option>
                        <option value="Good" <?php echo $equipment['condition_status'] === 'Good' ? 'selected' : ''; ?>>Good</option>
                        <option value="Needs Service" <?php echo $equipment['condition_status'] === 'Needs Service' ? 'selected' : ''; ?>>Needs Service</option>
                        <option value="Damaged" <?php echo $equipment['condition_status'] === 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <label for="remarks" class="form-label">Remarks/Notes</label>
                    <textarea class="form-control" id="remarks" name="remarks" rows="3"><?php echo htmlspecialchars($equipment['remarks'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Warranty Block -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Warranty Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="seller_company" class="form-label">Seller Company</label>
                    <input type="text" class="form-control" id="seller_company" name="seller_company" 
                           value="<?php echo htmlspecialchars($equipment['seller_company'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="purchase_date" class="form-label">Purchase Date</label>
                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                           value="<?php echo htmlspecialchars($equipment['purchase_date'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="warranty_expiry_date" class="form-label">Warranty Expiry Date</label>
                    <input type="date" class="form-control" id="warranty_expiry_date" name="warranty_expiry_date" 
                           value="<?php echo htmlspecialchars($equipment['warranty_expiry_date'] ?? ''); ?>">
                </div>
                
                <?php if ($equipment['warranty_documents']): ?>
                <div class="col-12">
                    <label class="form-label">Existing Documents</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                        $docs = json_decode($equipment['warranty_documents'], true);
                        if ($docs && is_array($docs)):
                            foreach ($docs as $doc):
                        ?>
                        <div class="border rounded p-2 d-flex align-items-center">
                            <a href="<?php echo BASE_URL . '/uploads/warranty/' . $doc; ?>" target="_blank" class="me-2">
                                <i class="bi bi-file-earmark-pdf"></i> <?php echo htmlspecialchars($doc); ?>
                            </a>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="delete_documents[]" value="<?php echo htmlspecialchars($doc); ?>" id="del_<?php echo md5($doc); ?>">
                                <label class="form-check-label text-danger" for="del_<?php echo md5($doc); ?>">
                                    <small>Delete</small>
                                </label>
                            </div>
                        </div>
                        <?php 
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="col-12">
                    <label for="warranty_documents" class="form-label">Add New Documents</label>
                    <input type="file" class="form-control" id="warranty_documents" name="warranty_documents[]" 
                           accept=".pdf,.jpg,.jpeg,.png" multiple>
                    <small class="text-muted">Max 15MB per file. Formats: PDF, JPG, PNG</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Custom Fields -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-tag me-2"></i>Custom Fields</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="custom_label_1" class="form-label">Custom Label 1</label>
                    <input type="text" class="form-control" id="custom_label_1" name="custom_label_1" 
                           value="<?php echo htmlspecialchars($equipment['custom_label_1'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="custom_value_1" class="form-label">Custom Value 1</label>
                    <input type="text" class="form-control" id="custom_value_1" name="custom_value_1" 
                           value="<?php echo htmlspecialchars($equipment['custom_value_1'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="custom_label_2" class="form-label">Custom Label 2</label>
                    <input type="text" class="form-control" id="custom_label_2" name="custom_label_2" 
                           value="<?php echo htmlspecialchars($equipment['custom_label_2'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="custom_value_2" class="form-label">Custom Value 2</label>
                    <input type="text" class="form-control" id="custom_value_2" name="custom_value_2" 
                           value="<?php echo htmlspecialchars($equipment['custom_value_2'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="view_equipment.php?id=<?php echo $equipment_id; ?>" class="btn btn-secondary">
            <i class="bi bi-x-circle me-1"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-2"></i>Save Changes
        </button>
    </div>
</form>

<script>
// Store existing custom values
const existingCustomValues = <?php echo json_encode($custom_values); ?>;

// Load type-specific fields on page load
document.addEventListener('DOMContentLoaded', function() {
    loadTypeSpecificFields(<?php echo $equipment['type_id']; ?>);
});

// AJAX Dynamic Field Loading
document.getElementById('type_id').addEventListener('change', function() {
    loadTypeSpecificFields(this.value);
});

function loadTypeSpecificFields(typeId) {
    const container = document.getElementById('typeSpecificFieldsContainer');
    
    if (!typeId) {
        container.innerHTML = '';
        return;
    }
    
    container.innerHTML = '<div class="card mb-4"><div class="card-body text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div></div>';
    
    fetch(`get_equipment_fields.php?type_id=${typeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.fields && data.fields.length > 0) {
                let html = '<div class="card mb-4">';
                html += '<div class="card-header bg-white">';
                html += '<h5 class="mb-0"><i class="bi bi-cpu me-2"></i>' + escapeHtml(data.type_name) + ' Specifications</h5>';
                html += '</div>';
                html += '<div class="card-body"><div class="row g-3">';
                
                data.fields.forEach(field => {
                    const existingValue = existingCustomValues[field.name] || '';
                    
                    html += '<div class="col-md-6">';
                    html += '<label for="cf_' + field.name + '" class="form-label">' + escapeHtml(field.label) + '</label>';
                    
                    if (field.type === 'textarea') {
                        html += '<textarea class="form-control" id="cf_' + field.name + '" name="custom_fields[' + field.name + ']" rows="3"';
                        if (field.placeholder) html += ' placeholder="' + escapeHtml(field.placeholder) + '"';
                        html += '>' + escapeHtml(existingValue) + '</textarea>';
                    } else if (field.type === 'select') {
                        html += '<select class="form-select" id="cf_' + field.name + '" name="custom_fields[' + field.name + ']">';
                        html += '<option value="">Select</option>';
                        field.options.forEach(opt => {
                            const selected = existingValue === opt ? ' selected' : '';
                            html += '<option value="' + escapeHtml(opt) + '"' + selected + '>' + escapeHtml(opt) + '</option>';
                        });
                        html += '</select>';
                    } else if (field.type === 'select-multiple') {
                        const existingArray = existingValue ? existingValue.split(', ') : [];
                        html += '<select class="form-select" id="cf_' + field.name + '" name="custom_fields[' + field.name + '][]" multiple size="4">';
                        field.options.forEach(opt => {
                            const selected = existingArray.includes(opt) ? ' selected' : '';
                            html += '<option value="' + escapeHtml(opt) + '"' + selected + '>' + escapeHtml(opt) + '</option>';
                        });
                        html += '</select>';
                    } else {
                        html += '<input type="text" class="form-control" id="cf_' + field.name + '" name="custom_fields[' + field.name + ']"';
                        if (field.placeholder) html += ' placeholder="' + escapeHtml(field.placeholder) + '"';
                        html += ' value="' + escapeHtml(existingValue) + '">';
                    }
                    
                    html += '</div>';
                });
                
                html += '</div></div></div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div class="alert alert-danger">Error loading fields</div>';
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include '../../includes/footer.php'; ?>