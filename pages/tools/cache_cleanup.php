<?php
// ==================== pages/tools/cache_cleanup.php ====================
// Folder: pages/tools/
// File: cache_cleanup.php
// Purpose: Clean cache and temporary files

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

$result = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deleted = 0;
    $freed = 0;
    
    if (isset($_POST['clean_cache'])) {
        $files = glob(CACHE_PATH . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $freed += filesize($file);
                unlink($file);
                $deleted++;
            }
        }
    }
    
    if (isset($_POST['clean_temp'])) {
        $files = glob(TEMP_UPLOAD_PATH . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $freed += filesize($file);
                unlink($file);
                $deleted++;
            }
        }
    }
    
    $result = "Deleted {$deleted} files, freed " . format_file_size($freed);
    log_system($pdo, 'INFO', "Cache cleanup: {$result}", get_current_user_id());
}

define('PAGE_TITLE', 'Cache Cleanup');
include '../../includes/header.php';
?>

<h1 class="h2"><i class="bi bi-trash me-2"></i>Cache Cleanup</h1>

<div class="card">
    <div class="card-body">
        <?php if ($result): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($result); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <button type="submit" name="clean_cache" class="btn btn-warning me-2">
                <i class="bi bi-trash me-1"></i>Clean System Cache
            </button>
            <button type="submit" name="clean_temp" class="btn btn-warning">
                <i class="bi bi-trash me-1"></i>Clean Temporary Files
            </button>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
