<?php
// Folder: pages/network/
// File: list_network_info.php
// Purpose: Display paginated list of network information with search

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

define('PAGE_TITLE', 'Network Management');

// Get search parameter
$search = $_GET['search'] ?? '';

// Build SQL query
$sql = "SELECT n.*, e.label as equipment_label, e.serial_number as equipment_serial,
        u.employee_id as creator_id
        FROM network_info n
        LEFT JOIN equipments e ON n.equipment_id = e.id
        LEFT JOIN users u ON n.created_by = u.id
        WHERE 1=1";
$params = [];

if ($search) {
    // Remove whitespace for search
    $search_clean = str_replace(' ', '', $search);
    $sql .= " AND (REPLACE(n.ip_address, ' ', '') LIKE ? OR 
              REPLACE(n.mac_address, ' ', '') LIKE ? OR 
              n.cable_no LIKE ? OR n.switch_no LIKE ?)";
    $search_param = "%{$search_clean}%";
    $params = [$search_param, $search_param, "%{$search}%", "%{$search}%"];
}

$sql .= " ORDER BY INET_ATON(n.ip_address)";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = RECORDS_PER_PAGE;
$offset = ($page - 1) * $records_per_page;

// Get total count
$count_sql = str_replace("SELECT n.*, e.label as equipment_label, e.serial_number as equipment_serial, u.employee_id as creator_id", "SELECT COUNT(*)", $sql);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();

// Get paginated results
$sql .= " LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$network_list = $stmt->fetchAll();

$total_pages = ceil($total_records / $records_per_page);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-hdd-network me-2"></i>Network Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="add_network_info.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Add Network Info
            </a>
            <a href="assign_network_info.php" class="btn btn-sm btn-success">
                <i class="bi bi-link me-1"></i>Assign to Equipment
            </a>
        </div>
    </div>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-10">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="IP Address, MAC Address, Cable No, Switch No..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <small class="text-muted">Search ignores whitespace</small>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Network Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>IP Address</th>
                        <th>MAC Address</th>
                        <th>Cable No</th>
                        <th>Patch Panel No</th>
                        <th>Patch Panel Port</th>
                        <th>Patch Panel Location</th>
                        <th>Switch No</th>
                        <th>Switch Port</th>
                        <th>Switch Location</th>
                        <th>Attached Equipment</th>
                        <th>Remarks</th>
                        <?php if (is_admin()): ?>
                        <th>Created By</th>
                        <th>Updated</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($network_list)): ?>
                    <tr>
                        <td colspan="<?php echo is_admin() ? '16' : '14'; ?>" class="text-center text-muted py-4">
                            No network information found
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($network_list as $index => $network): ?>
                        <tr>
                            <td><?php echo $offset + $index + 1; ?></td>
                            <td>
                                <a href="view_network_info.php?id=<?php echo $network['id']; ?>" target="_blank">
                                    <?php 
                                    // Highlight search match
                                    $ip = htmlspecialchars($network['ip_address']);
                                    if ($search) {
                                        $ip = str_ireplace($search, '<mark>' . htmlspecialchars($search) . '</mark>', $ip);
                                    }
                                    echo $ip;
                                    ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($network['mac_address'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($network['cable_no'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($network['patch_panel_no'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($network['patch_panel_port'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($network['patch_panel_location'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($network['switch_no'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($network['switch_port'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($network['switch_location'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($network['equipment_id']): ?>
                                <a href="../equipment/view_equipment.php?id=<?php echo $network['equipment_id']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($network['equipment_label']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(substr($network['remarks'] ?? '', 0, 50)); ?></td>
                            <?php if (is_admin()): ?>
                            <td><?php echo htmlspecialchars($network['creator_id']); ?></td>
                            <td class="text-nowrap"><?php echo format_datetime($network['updated_at']); ?></td>
                            <?php endif; ?>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_network_info.php?id=<?php echo $network['id']; ?>" 
                                       class="btn btn-outline-primary" title="View" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit_network_info.php?id=<?php echo $network['id']; ?>" 
                                       class="btn btn-outline-warning" title="Edit" target="_blank">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if (!$network['equipment_id']): ?>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteNetwork(<?php echo $network['id']; ?>)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
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
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteNetwork(networkId) {
    if (confirm('Are you sure you want to delete this network information?')) {
        fetch('delete_network_info.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${networkId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to delete network info');
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