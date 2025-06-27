<?php
/**
 * HRMS Configuration File
 * Contains all configuration settings for the HRMS application
 */

// Define access constant
if (!defined('HRMS_ACCESS')) {
    define('HRMS_ACCESS', true);
}
define('APP_NAME', 'HRMS - Human Resource Management System');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production'); // development, staging, production

// Database Configuration
define('DB_HOST', 'sg2plzcpnl508506.prod.sin2.secureserver.net');
define('DB_NAME', 'crmsoftware');
define('DB_USER', 'crmsoftware');
define('DB_PASS', 'crmsoftware@123');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', 3306);

// JWT Configuration
define('JWT_SECRET', 'hrms-production-jwt-secret-key-2024-change-this-to-random-string');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRY', 3600 * 24); // 24 hours
define('JWT_REFRESH_EXPIRY', 3600 * 24 * 7); // 7 days

// API Configuration
define('API_VERSION', 'v1');
define('API_BASE_URL', '/api');
define('CORS_ALLOWED_ORIGINS', 'https://whatsapp.proschool360.com'); // Production domain
define('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization, X-Requested-With');

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', 'pdf,doc,docx,jpg,jpeg,png,gif');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('PROFILE_PICS_PATH', UPLOAD_PATH . 'profiles/');
define('DOCUMENTS_PATH', UPLOAD_PATH . 'documents/');
define('RESUMES_PATH', UPLOAD_PATH . 'resumes/');

// Email Configuration (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_ENCRYPTION', 'tls');
define('FROM_EMAIL', 'noreply@hrms.com');
define('FROM_NAME', 'HRMS System');

// Pagination Configuration
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Security Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SYMBOLS', false);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_NAME', 'HRMS_SESSION');

// Logging Configuration
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_MAX_FILES', 30);

// Timezone Configuration
define('DEFAULT_TIMEZONE', 'UTC');
date_default_timezone_set(DEFAULT_TIMEZONE);

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Payroll Configuration
define('TAX_RATE', 0.15); // 15% default tax rate
define('INSURANCE_RATE', 0.02); // 2% insurance deduction
define('PF_RATE', 0.12); // 12% Provident Fund
define('ESI_RATE', 0.0175); // 1.75% ESI
define('PROFESSIONAL_TAX', 200); // Fixed professional tax

// Attendance Configuration
define('STANDARD_WORK_HOURS', 8);
define('OVERTIME_MULTIPLIER', 1.5);
define('LATE_THRESHOLD_MINUTES', 15);
define('HALF_DAY_THRESHOLD_HOURS', 4);

// Leave Configuration
define('DEFAULT_ANNUAL_LEAVE', 21);
define('DEFAULT_SICK_LEAVE', 12);
define('DEFAULT_CASUAL_LEAVE', 12);

// Notification Configuration
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('ENABLE_SMS_NOTIFICATIONS', false);
define('ENABLE_PUSH_NOTIFICATIONS', true);

// Performance Review Configuration
define('REVIEW_CYCLE_MONTHS', 12);
define('MIN_RATING', 1);
define('MAX_RATING', 5);

// Recruitment Configuration
define('JOB_POSTING_VALIDITY_DAYS', 30);
define('APPLICATION_RETENTION_DAYS', 365);

// Backup Configuration
define('BACKUP_PATH', __DIR__ . '/../backups/');
define('BACKUP_RETENTION_DAYS', 30);
define('AUTO_BACKUP_ENABLED', true);

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 hour
define('CACHE_PATH', __DIR__ . '/../cache/');

// API Rate Limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour

// Compliance Configuration
define('GDPR_ENABLED', true);
define('DATA_RETENTION_YEARS', 7);
define('AUDIT_LOG_ENABLED', true);

// Integration Configuration
define('BIOMETRIC_INTEGRATION', false);
define('PAYROLL_INTEGRATION', false);
define('ACCOUNTING_INTEGRATION', false);

// Mobile App Configuration
define('MOBILE_APP_ENABLED', true);
define('MOBILE_API_VERSION', 'v1');
define('PUSH_NOTIFICATION_KEY', 'your-firebase-key');

// Reports Configuration
define('REPORT_CACHE_ENABLED', true);
define('REPORT_CACHE_TTL', 1800); // 30 minutes
define('MAX_EXPORT_RECORDS', 10000);

// Development Configuration
if (APP_ENV === 'development') {
    define('DEBUG_MODE', true);
    define('SQL_DEBUG', true);
    define('API_DEBUG', true);
} else {
    define('DEBUG_MODE', false);
    define('SQL_DEBUG', false);
    define('API_DEBUG', false);
}

// Create required directories if they don't exist
$required_dirs = [
    UPLOAD_PATH,
    PROFILE_PICS_PATH,
    DOCUMENTS_PATH,
    RESUMES_PATH,
    LOG_PATH,
    BACKUP_PATH,
    CACHE_PATH
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Autoloader for classes
spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . '/../';
    $file = $base_dir . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Global error handler
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $error_log = LOG_PATH . 'error_' . date('Y-m-d') . '.log';
    $log_message = date('Y-m-d H:i:s') . " - Error: $message in $file on line $line\n";
    file_put_contents($error_log, $log_message, FILE_APPEND | LOCK_EX);
    
    if (APP_ENV === 'development') {
        echo "<br><b>Error:</b> $message in <b>$file</b> on line <b>$line</b><br>";
    }
    
    return true;
});

// Global exception handler
set_exception_handler(function($exception) {
    $error_log = LOG_PATH . 'exception_' . date('Y-m-d') . '.log';
    $log_message = date('Y-m-d H:i:s') . " - Exception: " . $exception->getMessage() . 
                   " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    file_put_contents($error_log, $log_message, FILE_APPEND | LOCK_EX);
    
    if (APP_ENV === 'development') {
        echo "<br><b>Uncaught Exception:</b> " . $exception->getMessage() . 
             " in <b>" . $exception->getFile() . "</b> on line <b>" . $exception->getLine() . "</b><br>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    } else {
        echo "An error occurred. Please try again later.";
    }
});

?>