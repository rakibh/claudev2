<?php
// Folder: pages/users/
// File: profile.php
// Purpose: User profile page - view and edit own information

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_login();

$user_id = get_current_user_id();

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone_primary = sanitize_input($_POST['phone_primary'] ?? '');
    $phone_secondary = sanitize_input($_POST['phone_secondary'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate required fields
    if (empty($first_name) || empty($last_name)) {
        $errors[] = 'First name and last name are required';
    }
    
    // Validate email if provided
    if ($email && !validate_email($email)) {
        $errors[] = 'Invalid email format';
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
        if (empty($current_password)) {
            $errors[] = 'Current password is required to set new password';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        } elseif (!validate_password($new_password)) {
            $errors[] = 'New password must be 6+ chars incl. a letter, a number, and a special character';
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
            
            // Update basic info
            $sql = "UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, 
                    phone_primary = ?, phone_secondary = ?, profile_photo = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $first_name,
                $last_name,
                $username ?: null,
                $email ?: null,
                $phone_primary ?: null,
                $phone_secondary ?: null,
                $profile_photo,
                $user_id
            ]);
            
            // Log changes
            foreach (['first_name', 'last_name', 'username', 'email', 'phone_primary', 'phone_secondary'] as $field) {
                if ($_POST[$field] !== $user[$field]) {
                    log_user_revision($pdo, $user_id, $user_id, $field, $user[$field], $_POST[$field]);
                }
            }
            
            // Update password if requested
            if ($update_password) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, force_password_change = 0 WHERE id = ?");
                $stmt->execute([$password_hash, $user_id]);
                log_user_revision($pdo, $user_id, $user_id, 'password', '***', '*** (changed by user)');
            }
            
            $pdo->commit();
            
            // Update session data
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['username'] = $username;
            $_SESSION['profile_photo'] = $profile_photo;
            
            log_system($pdo, 'INFO', "User updated their profile", $user_id);
            $_SESSION['success_message'] = 'Profile updated successfully';
            header('Location: profile.php');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            log_system($pdo, 'ERROR', "Failed to update profile: " . $e->getMessage(), $user_id);
            $errors[] = 'Database error occurred';
        }
    }
}

define('PAGE_TITLE', 'My Profile');

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-person-circle me-2"></i>My Profile</h1>
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
                    <label for="employee_id" class="form-label">Employee ID</label>
                    <input type="text" class="form-control" id="employee_id" 
                           value="<?php echo htmlspecialchars($user['employee_id']); ?>" disabled>
                    <small class="text-muted">Cannot be changed</small>
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
                
                <div class="col-md-6">
                    <label for="role" class="form-label">Role</label>
                    <input type="text" class="form-control" id="role" 
                           value="<?php echo htmlspecialchars($user['role']); ?>" disabled>
                    <small class="text-muted">Cannot be changed</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-key me-2"></i>Change Password (Optional)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password">
                </div>
                
                <div class="col-md-4">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                    <small class="text-muted">Min 6 chars with letter, number, special char</small>
                </div>
                
                <div class="col-md-4">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
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
</script>

<?php include '../../includes/footer.php'; ?>