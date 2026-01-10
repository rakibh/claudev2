<?php
// Folder: includes/
// File: functions.php
// Purpose: Common helper functions used throughout the application

// ============================================================================
// SECURITY FUNCTIONS
// ============================================================================

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool True if valid
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate IP address (IPv4)
 * @param string $ip IP address to validate
 * @return bool True if valid
 */
function validate_ip($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @return bool True if meets requirements
 */
function validate_password($password) {
    return preg_match(PASSWORD_PATTERN, $password);
}

// ============================================================================
// DATE AND TIME FUNCTIONS
// ============================================================================

/**
 * Format date for display
 * @param string $date Date string
 * @param string $format Output format
 * @return string Formatted date or 'N/A'
 */
function format_date($date, $format = DATE_FORMAT) {
    if (!$date) return 'N/A';
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

/**
 * Format datetime for display
 * @param string $datetime Datetime string
 * @param string $format Output format
 * @return string Formatted datetime or 'N/A'
 */
function format_datetime($datetime, $format = DATETIME_FORMAT) {
    if (!$datetime) return 'N/A';
    $timestamp = strtotime($datetime);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

/**
 * Get time ago string (relative time)
 * @param string $datetime Datetime string
 * @return string Time ago string
 */
function time_ago($datetime) {
    if (!$datetime) return 'N/A';
    
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 0) return 'In the future';
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    }
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    }
    if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
    if ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    }
    
    return format_datetime($datetime);
}

/**
 * Convert date to database format
 * @param string $date Date string
 * @return string Date in Y-m-d format
 */
function date_to_db($date) {
    if (!$date) return null;
    $timestamp = strtotime($date);
    return $timestamp ? date('Y-m-d', $timestamp) : null;
}

/**
 * Convert datetime to database format
 * @param string $datetime Datetime string
 * @return string Datetime in Y-m-d H:i:s format
 */
function datetime_to_db($datetime) {
    if (!$datetime) return null;
    $timestamp = strtotime($datetime);
    return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Get client IP address
 * @return string Client IP address
 */
function get_client_ip() {
    $ip = '0.0.0.0';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Format file size in human readable format
 * @param int $bytes File size in bytes
 * @return string Formatted file size
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
 * @param int $length Password length
 * @return string Random password
 */
function generate_random_password($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    $chars_length = strlen($chars);
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $chars_length - 1)];
    }
    
    return $password;
}

/**
 * Truncate string with ellipsis
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $ellipsis Ellipsis string
 * @return string Truncated text
 */
function truncate_text($text, $length = 100, $ellipsis = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length - strlen($ellipsis)) . $ellipsis;
}

/**
 * Convert array to CSV string
 * @param array $data Data array
 * @return string CSV string
 */
function array_to_csv($data) {
    $output = fopen('php://temp', 'w+');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    return $csv;
}

// ============================================================================
// LOGGING FUNCTIONS
// ============================================================================

/**
 * Log system activity
 * @param PDO $pdo Database connection
 * @param string $level Log level (INFO, WARNING, ERROR)
 * @param string $message Log message
 * @param int|null $user_id User ID
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
 * Log user revision
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param int $changed_by User who made the change
 * @param string $field_name Field name
 * @param mixed $old_value Old value
 * @param mixed $new_value New value
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
 * @param PDO $pdo Database connection
 * @param int $equipment_id Equipment ID
 * @param int $changed_by User who made the change
 * @param string $field_name Field name
 * @param mixed $old_value Old value
 * @param mixed $new_value New value
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
 * @param PDO $pdo Database connection
 * @param int $network_id Network ID
 * @param int $changed_by User who made the change
 * @param string $field_name Field name
 * @param mixed $old_value Old value
 * @param mixed $new_value New value
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

// ============================================================================
// NOTIFICATION FUNCTIONS
// ============================================================================

/**
 * Create notification
 * @param PDO $pdo Database connection
 * @param string $type Notification type
 * @param string $event Notification event
 * @param string $title Notification title
 * @param string $message Notification message
 * @param int|null $related_id Related record ID
 * @param int|null $created_by User who created the notification
 * @return int|false Notification ID or false on failure
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
 * Mark notification as read
 * @param PDO $pdo Database connection
 * @param int $notification_id Notification ID
 * @param int $user_id User ID
 * @return bool Success status
 */
function mark_notification_read($pdo, $notification_id, $user_id) {
    try {
        $sql = "UPDATE user_notification_status SET is_read = 1, read_at = NOW() 
                WHERE notification_id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$notification_id, $user_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Mark notification as acknowledged
 * @param PDO $pdo Database connection
 * @param int $notification_id Notification ID
 * @param int $user_id User ID
 * @return bool Success status
 */
function mark_notification_acknowledged($pdo, $notification_id, $user_id) {
    try {
        $sql = "UPDATE user_notification_status SET is_acknowledged = 1, acknowledged_at = NOW() 
                WHERE notification_id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$notification_id, $user_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get unread notification count for user
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return int Unread notification count
 */
function get_unread_notification_count($pdo, $user_id) {
    try {
        $sql = "SELECT COUNT(*) FROM user_notification_status 
                WHERE user_id = ? AND is_acknowledged = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// ============================================================================
// FILE UPLOAD FUNCTIONS
// ============================================================================

/**
 * Upload file with enhanced security
 * @param array $file File array from $_FILES
 * @param string $destination_path Destination directory
 * @param int $max_size Maximum file size in bytes
 * @param array $allowed_types Allowed MIME types
 * @return array ['success' => bool, 'filename' => string, 'filepath' => string, 'message' => string]
 */
function upload_file($file, $destination_path, $max_size, $allowed_types) {
    // Check upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $message = $error_messages[$file['error']] ?? 'Unknown upload error';
        return ['success' => false, 'message' => $message];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed (' . format_file_size($max_size) . ')'];
    }
    
    // Check if file is empty
    if ($file['size'] === 0) {
        return ['success' => false, 'message' => 'File is empty'];
    }
    
    // Get MIME type from file contents (more reliable than $_FILES['type'])
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // Validate MIME type
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types)];
    }
    
    // Get file extension
    $original_filename = basename($file['name']);
    $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    
    // Define allowed extensions based on MIME type
    $allowed_extensions = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'application/pdf' => ['pdf']
    ];
    
    // Validate extension matches MIME type
    if (isset($allowed_extensions[$mime_type])) {
        if (!in_array($extension, $allowed_extensions[$mime_type])) {
            return ['success' => false, 'message' => 'File extension does not match file type'];
        }
    } else {
        return ['success' => false, 'message' => 'Unsupported file type'];
    }
    
    // Check for double extensions (e.g., .php.jpg)
    $filename_parts = explode('.', $original_filename);
    if (count($filename_parts) > 2) {
        $dangerous_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'pht', 'phar', 'js', 'html', 'htm', 'sh', 'bat', 'exe'];
        for ($i = 0; $i < count($filename_parts) - 1; $i++) {
            if (in_array(strtolower($filename_parts[$i]), $dangerous_extensions)) {
                return ['success' => false, 'message' => 'Invalid file name'];
            }
        }
    }
    
    // Generate safe filename
    $safe_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($original_filename, PATHINFO_FILENAME));
    $unique_filename = uniqid() . '_' . time() . '_' . $safe_filename . '.' . $extension;
    
    // Ensure destination directory exists
    if (!file_exists($destination_path)) {
        if (!mkdir($destination_path, 0755, true)) {
            return ['success' => false, 'message' => 'Failed to create destination directory'];
        }
    }
    
    // Full filepath
    $filepath = $destination_path . '/' . $unique_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Set proper permissions
        chmod($filepath, 0644);
        
        // Additional security: For images, re-encode to strip any embedded code
        if (in_array($mime_type, ['image/jpeg', 'image/png'])) {
            try {
                $image_info = getimagesize($filepath);
                if ($image_info !== false) {
                    // Re-encode image to remove any malicious code
                    switch ($mime_type) {
                        case 'image/jpeg':
                            $img = imagecreatefromjpeg($filepath);
                            if ($img) {
                                imagejpeg($img, $filepath, 90);
                                imagedestroy($img);
                            }
                            break;
                        case 'image/png':
                            $img = imagecreatefrompng($filepath);
                            if ($img) {
                                imagepng($img, $filepath, 9);
                                imagedestroy($img);
                            }
                            break;
                    }
                }
            } catch (Exception $e) {
                // If re-encoding fails, delete the file for safety
                unlink($filepath);
                return ['success' => false, 'message' => 'Image validation failed'];
            }
        }
        
        return [
            'success' => true,
            'filename' => $unique_filename,
            'filepath' => $filepath,
            'message' => 'File uploaded successfully'
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

/**
 * Delete file from server
 * @param string $filepath Full file path
 * @return bool Success status
 */
function delete_file($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Validate uploaded file before processing
 * @param array $file File array from $_FILES
 * @param int $max_size Maximum file size in bytes
 * @param array $allowed_types Allowed MIME types
 * @return array ['valid' => bool, 'message' => string]
 */
function validate_uploaded_file($file, $max_size, $allowed_types) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'message' => 'Invalid file upload'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'message' => 'File upload error'];
    }
    
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'message' => 'File too large'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['valid' => false, 'message' => 'Invalid file type'];
    }
    
    return ['valid' => true, 'message' => 'File is valid'];
}

// ============================================================================
// TASK FUNCTIONS
// ============================================================================

/**
 * Check if task is overdue
 * @param string $due_date Due date string
 * @return bool True if overdue
 */
function is_task_overdue($due_date) {
    if (!$due_date) return false;
    return strtotime($due_date) < time();
}

/**
 * Get task priority badge class
 * @param string $priority Priority level
 * @return string Bootstrap badge class
 */
function get_task_priority_class($priority) {
    $classes = [
        'Critical' => 'danger',
        'High' => 'warning',
        'Medium' => 'info',
        'Low' => 'secondary'
    ];
    return $classes[$priority] ?? 'secondary';
}

/**
 * Get task status badge class
 * @param string $status Task status
 * @return string Bootstrap badge class
 */
function get_task_status_class($status) {
    $classes = [
        'Completed' => 'success',
        'Started' => 'info',
        'Cancelled' => 'secondary',
        'Pending' => 'warning'
    ];
    return $classes[$status] ?? 'secondary';
}

// ============================================================================
// EQUIPMENT FUNCTIONS
// ============================================================================

/**
 * Get warranty status
 * @param string $expiry_date Warranty expiry date
 * @return string Warranty status
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
 * Get equipment status badge class
 * @param string $status Equipment status
 * @return string Bootstrap badge class
 */
function get_equipment_status_class($status) {
    $classes = [
        'In Use' => 'success',
        'Available' => 'primary',
        'Under Repair' => 'warning',
        'Retired' => 'secondary'
    ];
    return $classes[$status] ?? 'secondary';
}

/**
 * Get equipment condition badge class
 * @param string $condition Equipment condition
 * @return string Bootstrap badge class
 */
function get_equipment_condition_class($condition) {
    $classes = [
        'New' => 'success',
        'Good' => 'info',
        'Needs Service' => 'warning',
        'Damaged' => 'danger'
    ];
    return $classes[$condition] ?? 'secondary';
}

// ============================================================================
// PAGINATION FUNCTIONS
// ============================================================================

/**
 * Calculate pagination data
 * @param int $total_records Total number of records
 * @param int $current_page Current page number
 * @param int $records_per_page Records per page
 * @return array Pagination data
 */
function paginate($total_records, $current_page, $records_per_page = RECORDS_PER_PAGE) {
    $total_pages = ceil($total_records / $records_per_page);
    $current_page = max(1, min($current_page, max(1, $total_pages)));
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'records_per_page' => $records_per_page,
        'offset' => $offset,
        'has_previous' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}

/**
 * Generate pagination HTML
 * @param array $pagination Pagination data from paginate()
 * @param string $url Base URL
 * @param array $params Additional URL parameters
 * @return string HTML pagination
 */
function generate_pagination_html($pagination, $url, $params = []) {
    if ($pagination['total_pages'] <= 1) return '';
    
    $query_string = http_build_query($params);
    $separator = strpos($url, '?') !== false ? '&' : '?';
    
    $html = '<nav><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($pagination['has_previous']) {
        $prev_page = $pagination['current_page'] - 1;
        $prev_url = $url . $separator . 'page=' . $prev_page . ($query_string ? '&' . $query_string : '');
        $html .= "<li class='page-item'><a class='page-link' href='{$prev_url}'>Previous</a></li>";
    } else {
        $html .= "<li class='page-item disabled'><span class='page-link'>Previous</span></li>";
    }
    
    // Page numbers with ellipsis
    $max_visible_pages = 5;
    $start_page = max(1, $pagination['current_page'] - floor($max_visible_pages / 2));
    $end_page = min($pagination['total_pages'], $start_page + $max_visible_pages - 1);
    
    if ($end_page - $start_page < $max_visible_pages - 1) {
        $start_page = max(1, $end_page - $max_visible_pages + 1);
    }
    
    // First page
    if ($start_page > 1) {
        $first_url = $url . $separator . 'page=1' . ($query_string ? '&' . $query_string : '');
        $html .= "<li class='page-item'><a class='page-link' href='{$first_url}'>1</a></li>";
        if ($start_page > 2) {
            $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
    }
    
    // Page numbers
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = ($i == $pagination['current_page']) ? 'active' : '';
        $page_url = $url . $separator . 'page=' . $i . ($query_string ? '&' . $query_string : '');
        $html .= "<li class='page-item {$active}'><a class='page-link' href='{$page_url}'>{$i}</a></li>";
    }
    
    // Last page
    if ($end_page < $pagination['total_pages']) {
        if ($end_page < $pagination['total_pages'] - 1) {
            $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
        $last_url = $url . $separator . 'page=' . $pagination['total_pages'] . ($query_string ? '&' . $query_string : '');
        $html .= "<li class='page-item'><a class='page-link' href='{$last_url}'>{$pagination['total_pages']}</a></li>";
    }
    
    // Next button
    if ($pagination['has_next']) {
        $next_page = $pagination['current_page'] + 1;
        $next_url = $url . $separator . 'page=' . $next_page . ($query_string ? '&' . $query_string : '');
        $html .= "<li class='page-item'><a class='page-link' href='{$next_url}'>Next</a></li>";
    } else {
        $html .= "<li class='page-item disabled'><span class='page-link'>Next</span></li>";
    }
    
    $html .= '</ul></nav>';
    return $html;
}

// ============================================================================
// EXPORT FUNCTIONS
// ============================================================================

/**
 * Export data to CSV
 * @param array $data Data array
 * @param array $headers Column headers
 * @param string $filename Output filename
 */
function export_to_csv($data, $headers, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Export data to Excel (using CSV with .xls extension)
 * @param array $data Data array
 * @param array $headers Column headers
 * @param string $filename Output filename
 */
function export_to_excel($data, $headers, $filename) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// ============================================================================
// DATABASE FUNCTIONS
// ============================================================================

/**
 * Backup database to SQL file
 * @param PDO $pdo Database connection
 * @param string $backup_path Backup directory path
 * @return array ['success' => bool, 'filename' => string, 'message' => string]
 */
function backup_database($pdo, $backup_path) {
    try {
        $filename = 'backup_' . DB_NAME . '_' . date('Ymd_His') . '.sql';
        $filepath = $backup_path . '/' . $filename;
        
        // Ensure backup directory exists
        if (!file_exists($backup_path)) {
            mkdir($backup_path, 0755, true);
        }
        
        // Execute mysqldump
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            DB_USER,
            DB_PASS,
            DB_HOST,
            DB_NAME,
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $return_code);
        
        if ($return_code === 0 && file_exists($filepath)) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'message' => 'Backup created successfully'
            ];
        }
        
        return ['success' => false, 'message' => 'Backup failed'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Optimize database tables
 * @param PDO $pdo Database connection
 * @param array $tables Array of table names
 * @return array Results array
 */
function optimize_database_tables($pdo, $tables) {
    $results = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("OPTIMIZE TABLE {$table}");
            $result = $stmt->fetch();
            $results[] = [
                'table' => $table,
                'status' => $result['Msg_text'] ?? 'OK'
            ];
        } catch (PDOException $e) {
            $results[] = [
                'table' => $table,
                'status' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    return $results;
}

/**
 * Repair database tables
 * @param PDO $pdo Database connection
 * @param array $tables Array of table names
 * @return array Results array
 */
function repair_database_tables($pdo, $tables) {
    $results = [];
    
    foreach ($tables as $table) {
        try {
            // Check table
            $stmt = $pdo->query("CHECK TABLE {$table}");
            $check = $stmt->fetch();
            
            if ($check['Msg_text'] !== 'OK') {
                // Repair if needed
                $stmt = $pdo->query("REPAIR TABLE {$table}");
                $repair = $stmt->fetch();
                $results[] = [
                    'table' => $table,
                    'status' => 'Repaired: ' . $repair['Msg_text']
                ];
            } else {
                $results[] = [
                    'table' => $table,
                    'status' => 'OK'
                ];
            }
        } catch (PDOException $e) {
            $results[] = [
                'table' => $table,
                'status' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    return $results;
}

/**
 * Clean old records from system_logs table
 * @param PDO $pdo Database connection
 * @param int $days Keep logs newer than this many days
 * @return int Number of deleted records
 */
function clean_old_logs($pdo, $days = 90) {
    try {
        $sql = "DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Clean old notifications
 * @param PDO $pdo Database connection
 * @param int $days Keep notifications newer than this many days
 * @return int Number of deleted records
 */
function clean_old_notifications($pdo, $days = 60) {
    try {
        $sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        return 0;
    }
}

// ============================================================================
// VALIDATION FUNCTIONS
// ============================================================================

/**
 * Validate MAC address format
 * @param string $mac MAC address
 * @return bool True if valid
 */
function validate_mac_address($mac) {
    if (strtoupper($mac) === 'N/A') return true;
    return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac);
}

/**
 * Validate phone number (basic validation)
 * @param string $phone Phone number
 * @return bool True if valid
 */
function validate_phone($phone) {
    // Allow digits, spaces, dashes, parentheses, and plus sign
    return preg_match('/^[\d\s\-\(\)\+]+$/', $phone);
}

/**
 * Validate date format (YYYY-MM-DD)
 * @param string $date Date string
 * @return bool True if valid
 */
function validate_date_format($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Validate datetime format (YYYY-MM-DD HH:MM:SS)
 * @param string $datetime Datetime string
 * @return bool True if valid
 */
function validate_datetime_format($datetime) {
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
    return $d && $d->format('Y-m-d H:i:s') === $datetime;
}

// ============================================================================
// USER INTERFACE HELPERS
// ============================================================================

/**
 * Generate breadcrumb HTML
 * @param array $items Breadcrumb items ['label' => 'Home', 'url' => '/']
 * @return string HTML breadcrumb
 */
function generate_breadcrumb($items) {
    if (empty($items)) return '';
    
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    $last_index = count($items) - 1;
    foreach ($items as $index => $item) {
        if ($index === $last_index) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($item['label']) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['label']) . '</a></li>';
        }
    }
    
    $html .= '</ol></nav>';
    return $html;
}

/**
 * Generate alert HTML
 * @param string $message Alert message
 * @param string $type Alert type (success, danger, warning, info)
 * @param bool $dismissible Is dismissible
 * @return string HTML alert
 */
function generate_alert($message, $type = 'info', $dismissible = true) {
    $dismiss_button = $dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' : '';
    $dismiss_class = $dismissible ? 'alert-dismissible fade show' : '';
    
    return "<div class='alert alert-{$type} {$dismiss_class}' role='alert'>{$message}{$dismiss_button}</div>";
}

/**
 * Generate status badge HTML
 * @param string $text Badge text
 * @param string $color Badge color (primary, success, danger, warning, info, secondary)
 * @return string HTML badge
 */
function generate_badge($text, $color = 'primary') {
    return "<span class='badge bg-{$color}'>" . htmlspecialchars($text) . "</span>";
}

/**
 * Generate icon HTML
 * @param string $icon Bootstrap icon name (without 'bi-' prefix)
 * @param string $class Additional CSS classes
 * @return string HTML icon
 */
function generate_icon($icon, $class = '') {
    return "<i class='bi bi-{$icon} {$class}'></i>";
}

// ============================================================================
// STATISTICS FUNCTIONS
// ============================================================================

/**
 * Get dashboard statistics
 * @param PDO $pdo Database connection
 * @return array Statistics array
 */
function get_dashboard_statistics($pdo) {
    $stats = [];
    
    try {
        // User statistics
        $stmt = $pdo->query("SELECT COUNT(*) as total, 
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active 
            FROM users");
        $user_stats = $stmt->fetch();
        $stats['users'] = $user_stats;
        
        // Equipment statistics
        $stmt = $pdo->query("SELECT COUNT(*) as total,
            SUM(CASE WHEN status = 'In Use' THEN 1 ELSE 0 END) as in_use,
            SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available
            FROM equipments");
        $equipment_stats = $stmt->fetch();
        $stats['equipment'] = $equipment_stats;
        
        // Task statistics
        $stmt = $pdo->query("SELECT COUNT(*) as total,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Started' THEN 1 ELSE 0 END) as started,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN due_date < NOW() AND status NOT IN ('Completed', 'Cancelled') THEN 1 ELSE 0 END) as overdue
            FROM tasks");
        $task_stats = $stmt->fetch();
        $stats['tasks'] = $task_stats;
        
        // Network statistics
        $stmt = $pdo->query("SELECT COUNT(*) as total,
            SUM(CASE WHEN equipment_id IS NOT NULL THEN 1 ELSE 0 END) as assigned
            FROM network_info");
        $network_stats = $stmt->fetch();
        $stats['network'] = $network_stats;
        
        // Warranty statistics
        $stmt = $pdo->query("SELECT 
            SUM(CASE WHEN warranty_expiry_date < NOW() THEN 1 ELSE 0 END) as expired,
            SUM(CASE WHEN warranty_expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon
            FROM equipments WHERE warranty_expiry_date IS NOT NULL");
        $warranty_stats = $stmt->fetch();
        $stats['warranty'] = $warranty_stats;
        
    } catch (PDOException $e) {
        error_log("Failed to get dashboard statistics: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Calculate percentage
 * @param int $part Part value
 * @param int $total Total value
 * @param int $decimals Decimal places
 * @return float Percentage
 */
function calculate_percentage($part, $total, $decimals = 1) {
    if ($total == 0) return 0;
    return round(($part / $total) * 100, $decimals);
}

// ============================================================================
// EMAIL FUNCTIONS (Placeholder - requires mail configuration)
// ============================================================================

/**
 * Send email notification
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param array $headers Additional headers
 * @return bool Success status
 */
function send_email($to, $subject, $message, $headers = []) {
    // Default headers
    $default_headers = [
        'From: ' . (SYSTEM_EMAIL ?? 'noreply@' . $_SERVER['HTTP_HOST']),
        'Reply-To: ' . (SYSTEM_EMAIL ?? 'noreply@' . $_SERVER['HTTP_HOST']),
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    $all_headers = array_merge($default_headers, $headers);
    
    // Send email
    return mail($to, $subject, $message, implode("\r\n", $all_headers));
}

/**
 * Send password reset email
 * @param PDO $pdo Database connection
 * @param string $email User email
 * @param string $new_password New password
 * @return bool Success status
 */
function send_password_reset_email($pdo, $email, $new_password) {
    $subject = 'Password Reset - ' . SYSTEM_NAME;
    $message = "
    <html>
    <body>
        <h2>Password Reset</h2>
        <p>Your password has been reset by an administrator.</p>
        <p><strong>New Password:</strong> {$new_password}</p>
        <p>Please log in and change your password immediately.</p>
        <p>If you did not request this change, please contact your administrator.</p>
        <hr>
        <p><small>This is an automated email from " . SYSTEM_NAME . "</small></p>
    </body>
    </html>
    ";
    
    return send_email($email, $subject, $message);
}

// ============================================================================
// SEARCH AND FILTER FUNCTIONS
// ============================================================================

/**
 * Build WHERE clause from filters
 * @param array $filters Filter array
 * @return array ['where' => string, 'params' => array]
 */
function build_where_clause($filters) {
    $where = [];
    $params = [];
    
    foreach ($filters as $field => $value) {
        if ($value !== '' && $value !== null) {
            if (is_array($value)) {
                // IN clause
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $where[] = "{$field} IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                // Equality clause
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
        }
    }
    
    return [
        'where' => implode(' AND ', $where),
        'params' => $params
    ];
}

/**
 * Highlight search terms in text
 * @param string $text Text to highlight
 * @param string $search Search term
 * @return string Highlighted text
 */
function highlight_search($text, $search) {
    if (empty($search)) return htmlspecialchars($text);
    
    $pattern = '/' . preg_quote($search, '/') . '/i';
    $highlighted = preg_replace($pattern, '<mark>$0</mark>', $text);
    
    return $highlighted;
}

// ============================================================================
// ARRAY HELPER FUNCTIONS
// ============================================================================

/**
 * Get value from nested array safely
 * @param array $array Input array
 * @param string $key Dot-notation key (e.g., 'user.profile.name')
 * @param mixed $default Default value
 * @return mixed Value or default
 */
function array_get($array, $key, $default = null) {
    if (is_null($key)) return $array;
    
    $keys = explode('.', $key);
    
    foreach ($keys as $segment) {
        if (!is_array($array) || !array_key_exists($segment, $array)) {
            return $default;
        }
        $array = $array[$segment];
    }
    
    return $array;
}

/**
 * Pluck values from array of arrays/objects
 * @param array $array Input array
 * @param string $key Key to pluck
 * @return array Plucked values
 */
function array_pluck($array, $key) {
    return array_map(function($item) use ($key) {
        return is_object($item) ? $item->$key : $item[$key];
    }, $array);
}

// ============================================================================
// DEBUG FUNCTIONS (For development only)
// ============================================================================

/**
 * Debug variable (for development only)
 * @param mixed $var Variable to debug
 * @param bool $die Die after output
 */
function debug($var, $die = false) {
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
        
        if ($die) {
            die();
        }
    }
}

/**
 * Log debug message (for development only)
 * @param string $message Debug message
 * @param mixed $context Additional context
 */
function debug_log($message, $context = null) {
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        $log_message = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if ($context) {
            $log_message .= ' | Context: ' . json_encode($context);
        }
        error_log($log_message);
    }
}

// ============================================================================
// END OF FUNCTIONS.PHP
// ============================================================================
?>