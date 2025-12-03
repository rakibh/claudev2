
<?php
// Folder: pages/notifications/
// File: list_notifications.php
// Purpose: Display all notifications for current user with filtering

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

define('PAGE_TITLE', 'Notifications');

$user_id = get_current_user_id();

// Get filter parameters
$filter_type = $_GET['type'] ?? '';
$filter_read = $_GET['read'] ?? '';
$filter_acknowledged = $_GET['acknowledged'] ?? '';
$search = $_GET['search'] ?? '';

// Build SQL query
$sql = "SELECT n.*, uns.is_read, uns.is_acknowledged, uns.read_at, uns.acknowledged_at,
        u.first_name, u.last_name, u.employee_id
        FROM notifications n
        JOIN user_notification_status uns ON n.id = uns.notification_id
        LEFT JOIN users u ON n.created_by = u.id
        WHERE uns.user_id = ?";

$params = [$user_id];

if ($filter_type) {
    $sql .= " AND n.type = ?";
    $params[] = $filter_type;
}

if ($filter_read !== '') {
    $sql .= " AND uns.is_read = ?";
    $params[] = $filter_read;
}

if ($filter_acknowledged !== '') {
    $sql .= " AND uns.is_acknowledged = ?";
    $params[] = $filter_acknowledged;
}

if ($search) {
    $sql .= " AND (n.title LIKE ? OR n.message LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql .= " ORDER BY n.created_at DESC";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = RECORDS_PER_PAGE;
$offset = ($page - 1) * $records_per_page;

// Get total count
$count_sql = str_replace("SELECT n.*, uns.is_read, uns.is_acknowledged, uns.read_at, uns.acknowledged_at, u.first_name, u.last_name, u.employee_id", "SELECT COUNT(*)", $sql);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();

// Get paginated results
$sql .= " LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Calculate pagination
$total_pages = ceil($total_records / $records_per_page);

// Mark as read if viewing specific notification
if (isset($_GET['id'])) {
    $notif_id = (int)$_GET['id'];
    $sql = "UPDATE user_notification_status SET is_read = 1, read_at = NOW() 
            WHERE notification_id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$notif_id, $user_id]);
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Notifications</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <option value="Equipment" <?php echo $filter_type === 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                    <option value="Network" <?php echo $filter_type === 'Network' ? 'selected' : ''; ?>>Network</option>
                    <option value="User" <?php echo $filter_type === 'User' ? 'selected' : ''; ?>>User</option>
                    <option value="Task" <?php echo $filter_type === 'Task' ? 'selected' : ''; ?>>Task</option>
                    <option value="Warranty" <?php echo $filter_type === 'Warranty' ? 'selected' : ''; ?>>Warranty</option>
                    <option value="System" <?php echo $filter_type === 'System' ? 'selected' : ''; ?>>System</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="read" class="form-label">Read Status</label>
                <select class="form-select" id="read" name="read">
                    <option value="">All</option>
                    <option value="0" <?php echo $filter_read === '0' ? 'selected' : ''; ?>>Unread</option>
                    <option value="1" <?php echo $filter_read === '1' ? 'selected' : ''; ?>>Read</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="acknowledged" class="form-label">Acknowledged</label>
                <select class="form-select" id="acknowledged" name="acknowledged">
                    <option value="">All</option>
                    <option value="0" <?php echo $filter_acknowledged === '0' ? 'selected' : ''; ?>>No</option>
                    <option value="1" <?php echo $filter_acknowledged === '1' ? 'selected' : ''; ?>>Yes</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="<?php echo BASE_URL; ?>/pages/notifications/list_notifications.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Notifications List -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>From</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifications)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No notifications found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($notifications as $index => $notif): ?>
                        <tr class="<?php echo !$notif['is_read'] ? 'fw-bold' : ''; ?>">
                            <td><?php echo $offset + $index + 1; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $notif['type'] === 'Equipment' ? 'success' : 
                                        ($notif['type'] === 'Network' ? 'info' : 
                                        ($notif['type'] === 'Task' ? 'warning' : 
                                        ($notif['type'] === 'User' ? 'primary' : 'secondary'))); 
                                ?>">
                                    <?php echo htmlspecialchars($notif['type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($notif['title']); ?></td>
                            <td><?php echo htmlspecialchars(substr($notif['message'], 0, 100)) . (strlen($notif['message']) > 100 ? '...' : ''); ?></td>
                            <td>
                                <?php 
                                if ($notif['first_name']) {
                                    echo htmlspecialchars($notif['first_name'] . ' ' . $notif['last_name']);
                                } else {
                                    echo 'System';
                                }
                                ?>
                            </td>
                            <td>
                                <small><?php echo time_ago($notif['created_at']); ?></small>
                            </td>
                            <td>
                                <?php if (!$notif['is_read']): ?>
                                    <span class="badge bg-primary">Unread</span>
                                <?php endif; ?>
                                <?php if (!$notif['is_acknowledged']): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Acknowledged</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$notif['is_acknowledged']): ?>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="acknowledgeNotification(<?php echo $notif['id']; ?>)">
                                        Acknowledge
                                    </button>
                                <?php endif; ?>
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
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&type=<?php echo $filter_type; ?>&read=<?php echo $filter_read; ?>&acknowledged=<?php echo $filter_acknowledged; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $filter_type; ?>&read=<?php echo $filter_read; ?>&acknowledged=<?php echo $filter_acknowledged; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&type=<?php echo $filter_type; ?>&read=<?php echo $filter_read; ?>&acknowledged=<?php echo $filter_acknowledged; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script>
function acknowledgeNotification(id) {
    if (confirm('Mark this notification as acknowledged?')) {
        fetch(BASE_URL + '/pages/notifications/acknowledge_notification.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to acknowledge notification');
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