<?php
// ==================== pages/tools/system_logs.php ====================
// Folder: pages/tools/
// File: system_logs.php
// Purpose: View and manage system logs

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_admin();

$search = $_GET['search'] ?? '';
$level = $_GET['level'] ?? '';

$sql = "SELECT * FROM system_logs WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND message LIKE ?";
    $params[] = "%{$search}%";
}

if ($level) {
    $sql .= " AND log_level = ?";
    $params[] = $level;
}

$sql .= " ORDER BY created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

if (isset($_POST['clear_logs'])) {
    $pdo->exec("TRUNCATE TABLE system_logs");
    $_SESSION['success_message'] = 'Logs cleared';
    header('Location: system_logs.php');
    exit;
}

define('PAGE_TITLE', 'System Logs');
include '../../includes/header.php';
?>

<h1 class="h2"><i class="bi bi-file-text me-2"></i>System Logs</h1>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="level">
                    <option value="">All Levels</option>
                    <option value="INFO" <?php echo $level === 'INFO' ? 'selected' : ''; ?>>INFO</option>
                    <option value="WARNING" <?php echo $level === 'WARNING' ? 'selected' : ''; ?>>WARNING</option>
                    <option value="ERROR" <?php echo $level === 'ERROR' ? 'selected' : ''; ?>>ERROR</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>Time</th><th>Level</th><th>Message</th><th>IP</th></tr></thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr class="<?php echo $log['log_level'] === 'ERROR' ? 'table-danger' : ($log['log_level'] === 'WARNING' ? 'table-warning' : ''); ?>">
                    <td class="text-nowrap"><?php echo format_datetime($log['created_at']); ?></td>
                    <td><?php echo $log['log_level']; ?></td>
                    <td><?php echo htmlspecialchars($log['message']); ?></td>
                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <form method="POST" class="mt-3">
            <button type="submit" name="clear_logs" class="btn btn-danger" onclick="return confirm('Clear all logs?')">
                <i class="bi bi-trash me-1"></i>Clear All Logs
            </button>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
