<?php
// Folder: includes/
// File: functions.php
// Purpose: Common helper functions used throughout the application

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate IP address
 */
function validate_ip($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
}

/**
 * Validate password strength
 */
function validate_password($password) {
    return preg_match(PASSWORD_PATTERN, $password);
}

/**
 * Format date for display
 */
function format_date($date, $format = DATE_FORMAT) {
    if (!$date) return 'N/A';
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

/**
 * Format datetime for display
 */
function format_datetime($datetime, $format = DATETIME_FORMAT) {
    if (!$datetime) return 'N/A';
    $timestamp = strtotime($datetime);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

/**
 * Get time ago string
 */
function time_ago($datetime) {
    if (!$datetime) return 'N/A';
    
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return format_datetime($datetime);
}

/**
 * Get client IP address
 */
function get_client_ip() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return $ip;
}

/**
 * Log system activity
 */
function log_system($pdo, $level, $message, $user_id = null) {
    try {
        $sql = "INSERT INTO system_logs (log_level, message, user_id, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$level, $message, $user_id, get_client_ip()]);
    } catch (PDOException $e) {
        error_log("Failed to write system log: " . $e->getMessage());
    }
}

/**
 * Create notification
 */
function create_notification($pdo, $type, $event, $title, $message, $related_id = null, $created_by = null) {
    try {
        // Insert notification
        $sql = "INSERT INTO notifications (type, event, title, message, related_id, created_by) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$type, $event, $title, $message, $related_id, $created_by]);
        $notification_id = $pdo->lastInsertId();
        
        // Create status entries for all active users
        $sql = "INSERT INTO user_notification_status (notification_id, user_id) 
                SELECT ?, id FROM users WHERE status = 'Active'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$notification_id]);
        
        return $notification_id;
    } catch (PDOException $e) {
        log_system($pdo, 'ERROR', "Failed to create notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Log user revision
 */
function log_user_revision($pdo, $user_id, $changed_by, $field_name, $old_value, $new_value) {
    try {
        $sql = "INSERT INTO user_revisions (user_id, changed_by, field_name, old_value, new_value) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $changed_by, $field_name, $old_value, $new_value]);
    } catch (PDOException $e) {
        error_log("Failed to log user revision: " . $e->getMessage());
    }
}

/**
 * Log equipment revision
 */
function log_equipment_revision($pdo, $equipment_id, $changed_by, $field_name, $old_value, $new_value) {
    try {
        $sql = "INSERT INTO equipment_revisions (equipment_id, changed_by, field_name, old_value, new_value) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$equipment_id, $changed_by, $field_name, $old_value, $new_value]);
    } catch (PDOException $e) {
        error_log("Failed to log equipment revision: " . $e->getMessage());
    }
}

/**
 * Log network revision
 */
function log_network_revision($pdo, $network_id, $changed_by, $field_name, $old_value, $new_value) {
    try {
        $sql = "INSERT INTO network_revisions (network_id, changed_by, field_name, old_value, new_value) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$network_id, $changed_by, $field_name, $old_value, $new_value]);
    } catch (PDOException $e) {
        error_log("Failed to log network revision: " . $e->getMessage());
    }
}

/**
 * Upload file
 */
function upload_file($file, $destination_path, $max_size, $allowed_types) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $destination_path . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

/**
 * Delete file
 */
function delete_file($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Format file size
 */
function format_file_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}

/**
 * Generate random password
 */
function generate_random_password($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Check if task is overdue
 */
function is_task_overdue($due_date) {
    if (!$due_date) return false;
    return strtotime($due_date) < time();
}

/**
 * Get warranty status
 */
function get_warranty_status($expiry_date) {
    if (!$expiry_date) return 'N/A';
    
    $expiry_timestamp = strtotime($expiry_date);
    $current_timestamp = time();
    $days_remaining = ($expiry_timestamp - $current_timestamp) / 86400;
    
    if ($days_remaining < 0) return 'Expired';
    if ($days_remaining <= 15) return 'Expiring Soon (15 days)';
    if ($days_remaining <= 30) return 'Expiring Soon (30 days)';
    return 'Active';
}

/**
 * Paginate results
 */
function paginate($total_records, $current_page, $records_per_page = RECORDS_PER_PAGE) {
    $total_pages = ceil($total_records / $records_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'records_per_page' => $records_per_page,
        'offset' => $offset
    ];
}

/**
 * Generate pagination HTML
 */
function generate_pagination_html($pagination, $url) {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<nav><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($pagination['current_page'] > 1) {
        $prev_page = $pagination['current_page'] - 1;
        $html .= "<li class='page-item'><a class='page-link' href='{$url}?page={$prev_page}'>Previous</a></li>";
    }
    
    // Page numbers
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        $active = ($i == $pagination['current_page']) ? 'active' : '';
        $html .= "<li class='page-item {$active}'><a class='page-link' href='{$url}?page={$i}'>{$i}</a></li>";
    }
    
    // Next button
    if ($pagination['current_page'] < $pagination['total_pages']) {
        $next_page = $pagination['current_page'] + 1;
        $html .= "<li class='page-item'><a class='page-link' href='{$url}?page={$next_page}'>Next</a></li>";
    }
    
    $html .= '</ul></nav>';
    return $html;
}
?>