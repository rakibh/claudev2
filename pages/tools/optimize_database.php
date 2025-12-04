<?php
// ==================== pages/tools/optimize_database.php ====================
// Folder: pages/tools/
// File: optimize_database.php
// Purpose: Optimize database tables for better performance

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_admin();

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tables = ['users', 'equipments', 'equipment_custom_values', 'network_info', 'notifications', 'user_notification_status', 'tasks', 'system_logs'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("OPTIMIZE TABLE {$table}");
            $result = $stmt->fetch();
            $results[] = ['table' => $table, 'status' => $result['Msg_text'] ?? 'OK'];
        } catch (PDOException $e) {
            $results[] = ['table' => $table, 'status' => 'Error: ' . $e->getMessage()];
        }
    }
    
    $pdo->exec("UPDATE system_settings SET setting_value = NOW() WHERE setting_key = 'last_optimization_time'");
    log_system($pdo, 'INFO', 'Database optimization completed', get_current_user_id());
}

$stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'last_optimization_time'");
$last_opt = $stmt->fetchColumn();

define('PAGE_TITLE', 'Database Optimization');
include '../../includes/header.php';
?>

<h1 class="h2"><i class="bi bi-speedometer2 me-2"></i>Database Optimization</h1>

<div class="card mb-4">
    <div class="card-body">
        <p>Last optimization: <?php echo $last_opt ? format_datetime($last_opt) : 'Never'; ?></p>
        <form method="POST">
            <button type="submit" class="btn btn-primary"><i class="bi bi-play-circle me-1"></i>Run Optimization</button>
        </form>
    </div>
</div>

<?php if (!empty($results)): ?>
<div class="card">
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>Table</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($results as $r): ?>
                <tr><td><?php echo $r['table']; ?></td><td><?php echo $r['status']; ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>