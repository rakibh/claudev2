<?php
// File: login.php
// Purpose: User login page with security features and validation
// NOTE: PHP and backend logic remains unchanged as requested.

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/constants.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: pages/dashboard_admin.php');
    } else {
        header('Location: pages/dashboard_user.php');
    }
    exit;
}

$error = '';
$error_code = '';

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
        $error_code = '403';
    } else {
        $login_id = sanitize_input($_POST['login_id'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip_address = get_client_ip();
        
        // Input validation
        if (empty($login_id) || empty($password)) {
            $error = 'Please enter both login ID and password.';
            $error_code = '422';
        } elseif (!validate_password($password)) {
            $error = 'Password must be 6+ chars incl. a letter, a number, and a special character.';
            $error_code = '422';
        } else {
            // Check login attempts
            $sql = "SELECT COUNT(*) as attempt_count FROM login_attempts 
                    WHERE login_id = ? AND ip_address = ? 
                    AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND) 
                    AND success = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$login_id, $ip_address, LOGIN_ATTEMPT_WINDOW]);
            $attempts = $stmt->fetch()['attempt_count'];
            
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $error = 'Too many attempts. Try again later.';
                $error_code = '429';
            } else {
                // Find user
                $sql = "SELECT * FROM users WHERE (employee_id = ? OR username = ?) LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$login_id, $login_id]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Check account status
                    if ($user['status'] !== 'Active') {
                        $error = 'Your account is inactive. Contact admin.';
                        $error_code = '403';
                        
                        // Log failed attempt
                        $sql = "INSERT INTO login_attempts (login_id, ip_address, success) VALUES (?, ?, 0)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$login_id, $ip_address]);
                    } else {
                        // Successful login
                        session_regenerate_id(true);
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['employee_id'] = $user['employee_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['profile_photo'] = $user['profile_photo'];
                        $_SESSION['force_password_change'] = $user['force_password_change'];
                        
                        // Log successful attempt
                        $sql = "INSERT INTO login_attempts (login_id, ip_address, success) VALUES (?, ?, 1)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$login_id, $ip_address]);
                        
                        // Log system activity
                        log_system($pdo, 'INFO', "User '{$user['username']}' logged in successfully", $user['id']);
                        
                        // Redirect based on role
                        if ($user['role'] === 'Admin') {
                            header('Location: pages/dashboard_admin.php');
                        } else {
                            header('Location: pages/dashboard_user.php');
                        }
                        exit;
                    }
                } else {
                    // Invalid credentials
                    $error = 'Invalid credentials.';
                    $error_code = '401';
                    
                    // Log failed attempt
                    $sql = "INSERT INTO login_attempts (login_id, ip_address, success) VALUES (?, ?, 0)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$login_id, $ip_address]);
                    
                    // Log system activity
                    log_system($pdo, 'WARNING', "Failed login attempt for ID: {$login_id}");
                }
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* --- UI/UX Improvements Start Here (Font updated to Inter) --- */

        body {
            /* Font updated to prioritize Inter, as requested */
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #74a1f3 0%, #a276b9 100%); 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0; 
        }
        .login-container {
            max-width: 420px; 
            width: 100%;
            padding: 15px;
        }
        .login-card {
            background: white;
            border-radius: 16px; 
            box-shadow: 0 15px 50px rgba(0,0,0,0.15); 
            padding: 30px 40px; 
            transition: transform 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-2px);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h3 {
            font-weight: 700; 
            color: #1a202c; 
            margin-bottom: 8px; 
            font-size: 1.75rem; 
        }
        .login-header p {
            color: #718096; 
            font-size: 15px;
        }
        /* Form Label and Control refinement */
        .form-label {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 0.25rem;
            font-size: 0.95rem;
        }
        .form-control {
            border-radius: 8px; 
            padding: 12px 15px; 
            border: 1px solid #e2e8f0;
            height: auto; 
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.1rem rgba(102, 126, 234, 0.4); 
        }

        /* Login Button Refinement */
        .btn-login {
            background: linear-gradient(135deg, #4c68d7 0%, #6f42c1 100%); 
            border: none;
            padding: 14px; 
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 8px; 
            transition: all 0.2s ease-in-out;
            color: white !important; 
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.5); 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .btn-login:active {
            transform: translateY(0);
            box-shadow: none;
        }
        .btn-login:disabled {
            background: #cbd5e0; 
            opacity: 1;
            transform: none;
            cursor: not-allowed;
            color: #718096 !important; 
        }

        /* Password Toggle */
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px; 
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0; 
            font-size: 1.1rem;
            transition: color 0.15s;
        }
        .password-toggle:hover {
            color: #667eea;
        }
        .input-group {
            position: relative;
        }

        /* Alert Styling Improvement */
        .alert-danger {
            background-color: #fcebeb;
            border-color: #fcd5d5;
            color: #c53030;
            border-radius: 8px;
            font-weight: 500;
        }

        /* --- UI/UX Improvements End Here --- */
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h3><?php echo SYSTEM_NAME; ?></h3>
                <p>Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="mb-3">
                    <label for="login_id" class="form-label">Employee ID / Username</label>
                    <input type="text" class="form-control" id="login_id" name="login_id" 
                           placeholder="E-101 or rakib" value="<?php echo htmlspecialchars($login_id ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="••••••••" required minlength="6">
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-login" id="loginBtn" disabled>
                    <span id="btnText">Sign In</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2"></span>
                </button>
            </form>
        </div>
    </div>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable/disable login button based on input
        const loginIdInput = document.getElementById('login_id');
        const passwordInput = document.getElementById('password');
        const loginBtn = document.getElementById('loginBtn');
        
        function checkInputs() {
            const loginId = loginIdInput.value.trim();
            const password = passwordInput.value;
            loginBtn.disabled = !(loginId && password.length >= 6); 
        }
        
        loginIdInput.addEventListener('input', checkInputs);
        passwordInput.addEventListener('input', checkInputs);
        
        // Prevent Enter key submission in input fields
        loginIdInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') e.preventDefault();
        });
        passwordInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') e.preventDefault();
        });
        
        // Form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{6,}$/;
            
            if (!passwordPattern.test(password)) {
                e.preventDefault();
                alert('Password must be 6+ chars incl. a letter, a number, and a special character.');
                return false;
            }
            
            // Show loading state and disable button on successful client-side validation
            loginBtn.disabled = true;
            document.getElementById('btnText').textContent = 'Authenticating...'; 
            document.getElementById('btnSpinner').classList.remove('d-none');
        });
        
        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>