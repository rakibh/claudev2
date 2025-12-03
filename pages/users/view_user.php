<?php
// Folder: pages/users/
// File: view_user.php
// Purpose: View complete user profile with revision history (Admin Only)

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_admin();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id === 0) {
    $_SESSION['error_message'] = 'Invalid user ID';
    header('Location: list_users.php');
    exit;
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error_message'] = 'User not found';
    header('Location: list_users.php');
    exit;
}

// Get revision history (last 15)
$stmt = $pdo->prepare("SELECT ur.*, u.first_name, u.last_name, u.employee_id 
    FROM user_revisions ur 
    LEFT JOIN users u ON ur.changed_by = u.id 
    WHERE ur.user_id = ? 
    ORDER BY ur.changed_at DESC 
    LIMIT 15");
$stmt->execute([$user_id]);
$revisions = $stmt->fetchAll();

define('PAGE_TITLE', 'View User');

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-person me-2"></i>User Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            <a href="list_users.php" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>
</div>

<!-- User Information Card -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>User Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 text-center mb-3 mb-md-0">
                <img src="<?php echo $user['profile_photo'] ? BASE_URL . '/uploads/profiles/' . $user['profile_photo'] : BASE_URL . '/assets/images/default-avatar.png'; ?>" 
                     alt="Profile Photo" class="img-fluid rounded" style="max-width: 200px;">
            </div>
            <div class="col-md-9">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Employee ID:</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['employee_id']); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Username:</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">First Name:</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['first_name']); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Last Name:</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['last_name']); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Email:</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Primary Phone:</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['phone_primary'] ?? 'N/A'); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Secondary Phone:</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['phone_secondary'] ?? 'N/A'); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Role:</label>
                        <p class="form-control-plaintext">
                            <span class="badge bg-<?php echo $user['role'] === 'Admin' ? 'primary' : 'secondary'; ?>">
                                <?php echo $user['role']; ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status:</label>
                        <p class="form-control-plaintext">
                            <span class="badge bg-<?php echo $user['status'] === 'Active' ? 'success' : 'danger'; ?>">
                                <?php echo $user['status']; ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Created At:</label>
                        <p class="form-control-plaintext"><?php echo format_datetime($user['created_at']); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Last Updated:</label>
                        <p class="form-control-plaintext"><?php echo format_datetime($user['updated_at']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revision History -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Revision History (Last 15 Changes)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($revisions)): ?>
        <div class="text-center text-muted py-4">
            <i class="bi bi-inbox fs-1"></i>
            <p class="mt-2 mb-0">No revision history available</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Changed By</th>
                        <th>Field Changed</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($revisions as $rev): ?>
                    <tr>
                        <td class="text-nowrap"><?php echo format_datetime($rev['changed_at']); ?></td>
                        <td>
                            <?php 
                            if ($rev['first_name']) {
                                echo htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']);
                            } else {
                                echo 'System';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($rev['field_name']); ?></td>
                        <td><?php echo htmlspecialchars($rev['old_value'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($rev['new_value'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>