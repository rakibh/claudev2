<?php
// Folder: pages/users/
// File: edit_user.php
// Purpose: Edit user information - Admin can edit all fields, Standard User can edit own info (except Role, Employee ID, Status)

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_user_id = get_current_user_id();
$is_admin = is_admin();

if ($user_id === 0) {
    $_SESSION['error_message'] = 'Invalid user ID';
    header('Location: list_users.php');
    exit;
}

// Check permissions: Admin can edit anyone, Standard User can only edit self
if (!$is_admin && $user_id !== $current_user_id) {
    $_SESSION['error_message'] = 'You do not have permission to edit this user';
    header('Location: ' . BASE_URL . '/pages/dashboard_user.php');
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone_primary = sanitize_input($_POST['phone_primary'] ?? '');
    $phone_secondary = sanitize_input($_POST['phone_secondary'] ?? '');
    
    // Admin-only fields
    $employee_id = $is_admin ? sanitize_input($_POST['employee_id'] ?? '') : $user['employee_id'];
    $role = $is_admin ? sanitize_input($_POST['role'] ?? 'Standard User') : $user['role'];
    $status = $is_admin ? sanitize_input($_POST['status'] ?? 'Active') : $user['status'];
    
    // Password change (optional)
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate required fields
    if (empty($first_name) || empty($last_name)) {
        $errors[] = 'First name and last name are required';
    }
    
    if ($is_admin && empty($employee_id)) {
        $errors[] = 'Employee ID is required';
    }
    
    // Validate email if provided
    if ($email && !validate_email($email)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check employee_id uniqueness (admin only)
    if ($is_admin && $employee_id !== $user['employee_id']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE employee_id = ? AND id != ?");
        $stmt->execute([$employee_id, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Employee ID already exists';
        }
    }
    
    // Check username uniqueness if changed
    if ($username && $username !== $user['username']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already exists';
        }
    }
    
    // Check email uniqueness if changed
    if ($email && $email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already exists';
        }
    }
    
    // Handle password change
    $update_password = false;
    if ($new_password) {
        if ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        } elseif (!validate_password($new_password)) {
            $errors[] = 'Password must be 6+ chars incl. a letter, a number, and a special character';
        } else {
            $update_password = true;
        }
    }
    
    // Handle profile photo upload
    $profile_photo = $user['profile_photo'];
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_file(
            $_FILES['profile_photo'],
            PROFILE_UPLOAD_PATH,
            MAX_PROFILE_SIZE,
            ALLOWED_IMAGE_TYPES
        );
        
        if ($upload_result['success']) {
            // Delete old photo
            if ($user['profile_photo']) {
                delete_file(PROFILE_UPLOAD_PATH . '/' . $user['profile_photo']);
            }
            $profile_photo = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    } elseif (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
        // Remove photo
        if ($user['profile_photo']) {
            delete_file(PROFILE_UPLOAD_PATH . '/' . $user['profile_photo']);
        }
        $profile_photo = null;
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update user information
            $sql = "UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, 
                    phone_primary = ?, phone_secondary = ?, profile_photo = ?";
            $params = [
                $first_name,
                $last_name,
                $username ?: null,
                $email ?: null,
                $phone_primary ?: null,
                $phone_secondary ?: null,
                $profile_photo
            ];
            
            // Admin can update additional fields
            if ($is_admin) {
                $sql .= ", employee_id = ?, role = ?, status = ?";
                $params[] = $employee_id;
                $params[] = $role;
                $params[] = $status;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $user_id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Log revisions for changed fields
            $changed_fields = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'username' => $username,
                'email' => $email,
                'phone_primary' => $phone_primary,
                'phone_secondary' => $phone_secondary
            ];
            
            if ($is_admin) {
                $changed_fields['employee_id'] = $employee_id;
                $changed_fields['role'] = $role;
                $changed_fields['status'] = $status;
            }
            
            foreach ($changed_fields as $field => $new_value) {
                if ($new_value !== $user[$field]) {
                    log_user_revision($pdo, $user_id, $current_user_id, $field, $user[$field], $new_value);
                }
            }
            
            // Update password if requested
            if ($update_password) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$password_hash, $user_id]);
                log_user_revision($pdo, $user_id, $current_user_id, 'password', '***', '*** (changed)');
            }
            
            $pdo->commit();
            
            // Update session if editing own profile
            if ($user_id === $current_user_id) {
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['username'] = $username;
                $_SESSION['profile_photo'] = $profile_photo;
                if ($is_admin) {
                    $_SESSION['role'] = $role;
                    $_SESSION['employee_id'] = $employee_id;
                }
            }
            
            log_system($pdo, 'INFO', "User updated: {$employee_id} ({$first_name} {$last_name})", $current_user_id);
            
            // Create notification
            create_notification(
                $pdo,
                'User',
                'Updated',
                'User Information Updated',
                "User {$first_name} {$last_name} (ID: {$employee_id}) information was updated.",
                $user_id,
                $current_user_id
            );
            
            $_SESSION['success_message'] = 'User updated successfully';
            header('Location: ' . ($is_admin ? 'list_users.php' : 'profile.php'));
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            log_system($pdo, 'ERROR', "Failed to update user: " . $e->getMessage(), $current_user_id);
            $errors[] = 'Database error occurred';
        }
    }
}

define('PAGE_TITLE', 'Edit User');

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-pencil-square me-2"></i>Edit User</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?php echo $is_admin ? 'list_users.php' : 'profile.php'; ?>" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <!-- Profile Photo Section -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-image me-2"></i>Profile Photo</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <img id="profilePreview" 
                         src="<?php echo $user['profile_photo'] ? BASE_URL . '/uploads/profiles/' . $user['profile_photo'] : BASE_URL . '/assets/images/default-avatar.png'; ?>" 
                         alt="Profile" class="img-fluid rounded" style="max-width: 200px;">
                </div>
                <div class="col-md-9">
                    <div class="mb-3">
                        <label for="profile_photo" class="form-label">Upload New Photo</label>
                        <input type="file" class="form-control" id="profile_photo" name="profile_photo" 
                               accept="image/jpeg,image/jpg,image/png" onchange="previewImage(this)">
                        <small class="text-muted">Maximum 5MB. Formats: JPEG, JPG, PNG</small>
                    </div>
                    <?php if ($user['profile_photo']): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remove_photo" name="remove_photo" value="1">
                        <label class="form-check-label" for="remove_photo">
                            Remove current photo
                        </label>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Basic Information -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="employee_id" name="employee_id" 
                           value="<?php echo htmlspecialchars($user['employee_id']); ?>" 
                           <?php echo $is_admin ? 'required' : 'disabled'; ?>>
                    <?php if (!$is_admin): ?>
                    <small class="text-muted">Cannot be changed by non-admin users</small>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="phone_primary" class="form-label">Primary Phone</label>
                    <input type="text" class="form-control" id="phone_primary" name="phone_primary" 
                           value="<?php echo htmlspecialchars($user['phone_primary'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="phone_secondary" class="form-label">Secondary Phone</label>
                    <input type="text" class="form-control" id="phone_secondary" name="phone_secondary" 
                           value="<?php echo htmlspecialchars($user['phone_secondary'] ?? ''); ?>">
                </div>
                
                <?php if ($is_admin): ?>
                <div class="col-md-6">
                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="Standard User" <?php echo $user['role'] === 'Standard User' ? 'selected' : ''; ?>>Standard User</option>
                        <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="Active" <?php echo $user['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $user['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <?php else: ?>
                <div class="col-md-6">
                    <label for="role" class="form-label">Role</label>
                    <input type="text" class="form-control" id="role" 
                           value="<?php echo htmlspecialchars($user['role']); ?>" disabled>
                    <small class="text-muted">Cannot be changed by non-admin users</small>
                </div>
                
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <input type="text" class="form-control" id="status" 
                           value="<?php echo htmlspecialchars($user['status']); ?>" disabled>
                    <small class="text-muted">Cannot be changed by non-admin users</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Change Password (Optional) -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-key me-2"></i>Change Password (Optional)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                    <small class="text-muted">Min 6 chars with letter, number, special char</small>
                </div>
                
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="<?php echo $is_admin ? 'list_users.php' : 'profile.php'; ?>" class="btn btn-secondary">
            <i class="bi bi-x-circle me-1"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-2"></i>Save Changes
        </button>
    </div>
</form>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Validate password match
document.querySelector('form').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword && newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    
    if (newPassword) {
        const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{6,}$/;
        if (!passwordPattern.test(newPassword)) {
            e.preventDefault();
            alert('Password must be 6+ chars incl. a letter, a number, and a special character.');
            return false;
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>