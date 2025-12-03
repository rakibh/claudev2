<?php
// Folder: config/
// File: constants.php
// Purpose: Global constants and configuration settings

// Base paths
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'http://localhost/claudev2');

// Upload directories
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('PROFILE_UPLOAD_PATH', UPLOAD_PATH . '/profiles');
define('WARRANTY_UPLOAD_PATH', UPLOAD_PATH . '/warranty');
define('TEMP_UPLOAD_PATH', UPLOAD_PATH . '/temp');

// Backup directory
define('BACKUP_PATH', BASE_PATH . '/backups');

// Cache directory
define('CACHE_PATH', BASE_PATH . '/cache');

// Log directory
define('LOG_PATH', BASE_PATH . '/logs');
define('SYSTEM_LOG_FILE', LOG_PATH . '/system.log');

// File upload settings
define('MAX_PROFILE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_WARRANTY_SIZE', 15 * 1024 * 1024); // 15MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']);

// Pagination
define('RECORDS_PER_PAGE', 100);

// Date and time formats
define('DATE_FORMAT', 'd/m/Y');
define('TIME_FORMAT', 'h:i:s A');
define('DATETIME_FORMAT', 'd/m/Y h:i:s A');
define('DB_DATE_FORMAT', 'Y-m-d');
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');

// Timezone
define('TIMEZONE', 'Asia/Dhaka');

// System settings
define('SYSTEM_NAME', 'IT Equipment Manager');
define('NOTIFICATION_REFRESH_INTERVAL', 30); // seconds

// Password requirements
define('MIN_PASSWORD_LENGTH', 6);
define('PASSWORD_PATTERN', '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{6,}$/');

// Login attempt limits
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 600); // 10 minutes in seconds

// Create directories if they don't exist
$directories = [
    UPLOAD_PATH,
    PROFILE_UPLOAD_PATH,
    WARRANTY_UPLOAD_PATH,
    TEMP_UPLOAD_PATH,
    BACKUP_PATH,
    CACHE_PATH,
    LOG_PATH
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>