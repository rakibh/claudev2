<?php
// ==================== pages/tools/repair_database.php ====================
// Folder: pages/tools/
// File: repair_database.php
// Purpose: Check and repair database tables

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_admin();

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tables = ['users', 'equipments', 'network_info', 'notifications', 'tasks', 'system_logs'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("CHECK TABLE {$table}");
            $check = $stmt->fetch();
            
            if ($check['Msg_text'] !== 'OK') {
                $stmt = $pdo->query("REPAIR TABLE {$table}");
                $repair = $stmt->fetch();
                $results[] = ['table' => $table, 'status' => 'Repaired: ' . $repair['Msg_text']];
            } else {
                $results[] = ['table' => $table, 'status' => 'OK'];
            }
        } catch (PDOException $e) {
            $results[] = ['table' => $table, 'status' => 'Error: ' . $e->getMessage()];
        }
    }
    
    log_system($pdo, 'INFO', 'Database repair completed', get_current_user_id());
}

define('PAGE_TITLE', 'Database Repair');
include '../../includes/header.php';
?>

<h1 class="h2"><i class="bi bi-wrench me-2"></i>Database Repair</h1>

<div class="card mb-4">
    <div class="card-body">
        <form method="POST">
            <button type="submit" class="btn btn-warning"><i class="bi bi-play-circle me-1"></i>Check & Repair</button>
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