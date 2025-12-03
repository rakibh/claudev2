<?php
// Folder: pages/users/
// File: add_user.php
// Purpose: Backend handler for adding new user (Admin Only)

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

require_admin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$employee_id = sanitize_input($_POST['employee_id'] ?? '');
$username = sanitize_input($_POST['username'] ?? '');
$first_name = sanitize_input($_POST['first_name'] ?? '');
$last_name = sanitize_input($_POST['last_name'] ?? '');
$email = sanitize_input($_POST['email'] ?? '');
$phone_primary = sanitize_input($_POST['phone_primary'] ?? '');
$phone_secondary = sanitize_input($_POST['phone_secondary'] ?? '');
$role = sanitize_input($_POST['role'] ?? 'Standard User');
$status = sanitize_input($_POST['status'] ?? 'Active');
$password = $_POST['password'] ?? '';

// Validation
if (empty($employee_id) || empty($first_name) || empty($last_name) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
    exit;
}

if (!validate_password($password)) {
    echo json_encode(['success' => false, 'message' => 'Password must be 6+ chars incl. a letter, a number, and a special character']);
    exit;
}

// Check if employee_id already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE employee_id = ?");
$stmt->execute([$employee_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Employee ID already exists']);
    exit;
}

// Check if username exists (if provided)
if ($username) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit;
    }
}

// Check if email exists (if provided)
if ($email) {
    if (!validate_email($email)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
}

// Handle profile photo upload
$profile_photo = null;
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $upload_result = upload_file(
        $_FILES['profile_photo'],
        PROFILE_UPLOAD_PATH,
        MAX_PROFILE_SIZE,
        ALLOWED_IMAGE_TYPES
    );
    
    if ($upload_result['success']) {
        $profile_photo = $upload_result['filename'];
    } else {
        echo json_encode(['success' => false, 'message' => $upload_result['message']]);
        exit;
    }
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Insert user
    $sql = "INSERT INTO users (employee_id, username, password, first_name, last_name, email, 
            phone_primary, phone_secondary, profile_photo, role, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $employee_id,
        $username ?: null,
        $password_hash,
        $first_name,
        $last_name,
        $email ?: null,
        $phone_primary ?: null,
        $phone_secondary ?: null,
        $profile_photo,
        $role,
        $status,
        get_current_user_id()
    ]);
    
    $new_user_id = $pdo->lastInsertId();
    
    // Log activity
    log_system($pdo, 'INFO', "New user created: {$employee_id} ({$first_name} {$last_name})", get_current_user_id());
    
    // Create notification
    create_notification(
        $pdo,
        'User',
        'Added',
        'New User Added',
        "User {$first_name} {$last_name} (ID: {$employee_id}) was added to the system.",
        $new_user_id,
        get_current_user_id()
    );
    
    $_SESSION['success_message'] = 'User added successfully';
    echo json_encode(['success' => true, 'redirect' => BASE_URL . '/pages/users/list_users.php']);
    
} catch (PDOException $e) {
    log_system($pdo, 'ERROR', "Failed to add user: " . $e->getMessage(), get_current_user_id());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>