<?php
// ==================== pages/tools/system_settings.php ====================
// Folder: pages/tools/
// File: system_settings.php
// Purpose: Manage system settings

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([sanitize_input($value), $key]);
    }
    $_SESSION['success_message'] = 'Settings updated';
    header('Location: system_settings.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM system_settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

define('PAGE_TITLE', 'System Settings');
include '../../includes/header.php';
?>

<h1 class="h2"><i class="bi bi-gear me-2"></i>System Settings</h1>

<form method="POST">
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">System Name</label>
                    <input type="text" class="form-control" name="settings[system_name]" value="<?php echo htmlspecialchars($settings['system_name'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Timezone</label>
                    <input type="text" class="form-control" name="settings[timezone]" value="<?php echo htmlspecialchars($settings['timezone'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Records Per Page</label>
                    <input type="number" class="form-control" name="settings[records_per_page]" value="<?php echo htmlspecialchars($settings['records_per_page'] ?? '100'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notification Refresh (seconds)</label>
                    <input type="number" class="form-control" name="settings[notification_refresh]" value="<?php echo htmlspecialchars($settings['notification_refresh'] ?? '30'); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i>Save Changes</button>
        </div>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>
