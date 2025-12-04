<?php
// Folder: pages/equipment/
// File: list_equipment.php
// Purpose: Display paginated list of equipment with search and filters

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

define('PAGE_TITLE', 'Equipment Management');

// Get filter parameters
$search = $_GET['search'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_brand = $_GET['brand'] ?? '';
$filter_location = $_GET['location'] ?? '';
$filter_condition = $_GET['condition'] ?? '';

// Build SQL query
$sql = "SELECT e.*, et.type_name, u.employee_id as creator_id
        FROM equipments e
        LEFT JOIN equipment_types et ON e.type_id = et.id
        LEFT JOIN users u ON e.created_by = u.id
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (e.label LIKE ? OR e.serial_number LIKE ? OR e.brand LIKE ? OR e.model_number LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($filter_type) {
    $sql .= " AND e.type_id = ?";
    $params[] = $filter_type;
}

if ($filter_brand) {
    $sql .= " AND e.brand = ?";
    $params[] = $filter_brand;
}

if ($filter_location) {
    $sql .= " AND e.location = ?";
    $params[] = $filter_location;
}

if ($filter_condition) {
    $sql .= " AND e.condition_status = ?";
    $params[] = $filter_condition;
}

$sql .= " ORDER BY e.created_at DESC";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = RECORDS_PER_PAGE;
$offset = ($page - 1) * $records_per_page;

// Get total count
$count_sql = str_replace("SELECT e.*, et.type_name, u.employee_id as creator_id", "SELECT COUNT(*)", $sql);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();

// Get paginated results
$sql .= " LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipment_list = $stmt->fetchAll();

$total_pages = ceil($total_records / $records_per_page);

// Get equipment types for filter
$types = $pdo->query("SELECT id, type_name FROM equipment_types ORDER BY type_name")->fetchAll();

// Get unique brands and locations for filters
$brands = $pdo->query("SELECT DISTINCT brand FROM equipments WHERE brand IS NOT NULL ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);
$locations = $pdo->query("SELECT DISTINCT location FROM equipments WHERE location IS NOT NULL ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-pc-display me-2"></i>Equipment Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_equipment.php" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Add Equipment
        </a>
    </div>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Label, Serial, Brand..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <?php foreach ($types as $type): ?>
                    <option value="<?php echo $type['id']; ?>" <?php echo $filter_type == $type['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type['type_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="brand" class="form-label">Brand</label>
                <select class="form-select" id="brand" name="brand">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $brand): ?>
                    <option value="<?php echo htmlspecialchars($brand); ?>" <?php echo $filter_brand === $brand ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($brand); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="location" class="form-label">Location</label>
                <select class="form-select" id="location" name="location">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $location): ?>
                    <option value="<?php echo htmlspecialchars($location); ?>" <?php echo $filter_location === $location ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($location); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="condition" class="form-label">Condition</label>
                <select class="form-select" id="condition" name="condition">
                    <option value="">All</option>
                    <option value="New" <?php echo $filter_condition === 'New' ? 'selected' : ''; ?>>New</option>
                    <option value="Good" <?php echo $filter_condition === 'Good' ? 'selected' : ''; ?>>Good</option>
                    <option value="Needs Service" <?php echo $filter_condition === 'Needs Service' ? 'selected' : ''; ?>>Needs Service</option>
                    <option value="Damaged" <?php echo $filter_condition === 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                </select>
            </div>
            
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Equipment Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Label/Name</th>
                        <th>Brand</th>
                        <th>Serial Number</th>
                        <th>Location</th>
                        <th>Floor</th>
                        <th>Department</th>
                        <th>Condition</th>
                        <th>Warranty</th>
                        <?php if (is_admin()): ?>
                        <th>Updated</th>
                        <th>Created By</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($equipment_list)): ?>
                    <tr>
                        <td colspan="<?php echo is_admin() ? '13' : '11'; ?>" class="text-center text-muted py-4">
                            No equipment found
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($equipment_list as $index => $equip): ?>
                        <tr>
                            <td><?php echo $offset + $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($equip['type_name']); ?></td>
                            <td>
                                <a href="view_equipment.php?id=<?php echo $equip['id']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($equip['label']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($equip['brand'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($equip['serial_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($equip['location'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($equip['floor_no'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($equip['department'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($equip['condition_status']): ?>
                                <span class="badge bg-<?php 
                                    echo $equip['condition_status'] === 'New' ? 'success' : 
                                        ($equip['condition_status'] === 'Good' ? 'info' : 
                                        ($equip['condition_status'] === 'Needs Service' ? 'warning' : 'danger')); 
                                ?>">
                                    <?php echo $equip['condition_status']; ?>
                                </span>
                                <?php else: ?>
                                N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($equip['warranty_expiry_date']): ?>
                                    <?php 
                                    $warranty_status = get_warranty_status($equip['warranty_expiry_date']);
                                    $badge_class = strpos($warranty_status, 'Expired') !== false ? 'danger' : 
                                                  (strpos($warranty_status, 'Expiring') !== false ? 'warning' : 'success');
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                        <?php echo $warranty_status; ?>
                                    </span>
                                <?php else: ?>
                                N/A
                                <?php endif; ?>
                            </td>
                            <?php if (is_admin()): ?>
                            <td class="text-nowrap"><?php echo format_datetime($equip['updated_at']); ?></td>
                            <td><?php echo htmlspecialchars($equip['creator_id']); ?></td>
                            <?php endif; ?>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_equipment.php?id=<?php echo $equip['id']; ?>" 
                                       class="btn btn-outline-primary" title="View" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit_equipment.php?id=<?php echo $equip['id']; ?>" 
                                       class="btn btn-outline-warning" title="Edit" target="_blank">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteEquipment(<?php echo $equip['id']; ?>)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $filter_type; ?>&brand=<?php echo urlencode($filter_brand); ?>&location=<?php echo urlencode($filter_location); ?>&condition=<?php echo urlencode($filter_condition); ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $filter_type; ?>&brand=<?php echo urlencode($filter_brand); ?>&location=<?php echo urlencode($filter_location); ?>&condition=<?php echo urlencode($filter_condition); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $filter_type; ?>&brand=<?php echo urlencode($filter_brand); ?>&location=<?php echo urlencode($filter_location); ?>&condition=<?php echo urlencode($filter_condition); ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteEquipment(equipId) {
    if (confirm('Are you sure you want to delete this equipment? This action cannot be undone.')) {
        fetch('delete_equipment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${equipId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to delete equipment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}
</script>

<?php include '../../includes/footer.php'; ?>