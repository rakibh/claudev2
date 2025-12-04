<?php
// ==================== pages/tools/backup_database.php ====================
// Folder: pages/tools/
// File: backup_database.php
// Purpose: Create and manage database backups

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    $filename = 'backup_' . DB_NAME . '_' . date('Ymd_His') . '.sql';
    $filepath = BACKUP_PATH . '/' . $filename;
    
    $command = sprintf('mysqldump --user=%s --password=%s --host=%s %s > %s',
        DB_USER, DB_PASS, DB_HOST, DB_NAME, escapeshellarg($filepath));
    
    exec($command, $output, $return_code);
    
    if ($return_code === 0 && file_exists($filepath)) {
        $_SESSION['success_message'] = 'Backup created successfully';
        log_system($pdo, 'INFO', "Database backup created: {$filename}", get_current_user_id());
    } else {
        $_SESSION['error_message'] = 'Backup failed';
    }
    
    header('Location: backup_database.php');
    exit;
}

if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    $filepath = BACKUP_PATH . '/' . $file;
    if (file_exists($filepath)) {
        unlink($filepath);
        $_SESSION['success_message'] = 'Backup deleted';
    }
    header('Location: backup_database.php');
    exit;
}

$backups = glob(BACKUP_PATH . '/backup_*.sql');
usort($backups, function($a, $b) { return filemtime($b) - filemtime($a); });

define('PAGE_TITLE', 'Database Backup');
include '../../includes/header.php';
?>

<h1 class="h2"><i class="bi bi-download me-2"></i>Database Backup</h1>

<div class="card mb-4">
    <div class="card-body">
        <form method="POST">
            <button type="submit" name="create_backup" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Create New Backup
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>#</th><th>Filename</th><th>Size</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($backups)): ?>
                <tr><td colspan="5" class="text-center text-muted">No backups found</td></tr>
                <?php else: ?>
                    <?php foreach ($backups as $i => $backup): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo basename($backup); ?></td>
                        <td><?php echo format_file_size(filesize($backup)); ?></td>
                        <td><?php echo date('d/m/Y H:i:s', filemtime($backup)); ?></td>
                        <td>
                            <a href="<?php echo BASE_URL . '/backups/' . basename($backup); ?>" class="btn btn-sm btn-outline-primary" download>
                                <i class="bi bi-download"></i>
                            </a>
                            <a href="?delete=<?php echo urlencode(basename($backup)); ?>" class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Delete this backup?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

