<?php
// Debug script to display all PHP errors and logs

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

// Set error log file
ini_set('error_log', __DIR__ . '/logs/debug_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

echo "<h1>HRMS Debug - Error Logs</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .error{background:#ffebee;padding:10px;margin:10px 0;border-left:4px solid #f44336;} .info{background:#e3f2fd;padding:10px;margin:10px 0;border-left:4px solid #2196f3;} pre{background:#f5f5f5;padding:10px;overflow:auto;}</style>";

// Check PHP error log
$phpErrorLog = ini_get('error_log');
echo "<div class='info'><strong>PHP Error Log Location:</strong> " . ($phpErrorLog ?: 'Default system log') . "</div>";

// Check custom error log
$customErrorLog = __DIR__ . '/logs/debug_errors.log';
echo "<div class='info'><strong>Custom Error Log:</strong> $customErrorLog</div>";

// Check Apache/server error logs (common locations)
$serverLogs = [
    'C:/xampp/apache/logs/error.log',
    'C:/wamp/logs/apache_error.log',
    'C:/laragon/etc/apache2/logs/error.log',
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log'
];

echo "<h2>Server Error Logs</h2>";
foreach ($serverLogs as $logFile) {
    if (file_exists($logFile) && is_readable($logFile)) {
        echo "<div class='info'><strong>Found server log:</strong> $logFile</div>";
        $lines = file($logFile);
        $recentLines = array_slice($lines, -20); // Last 20 lines
        echo "<h3>Last 20 lines from $logFile:</h3>";
        echo "<pre>" . htmlspecialchars(implode('', $recentLines)) . "</pre>";
    }
}

// Check custom application logs
$appLogs = [
    __DIR__ . '/logs/api.log',
    __DIR__ . '/logs/auth.log',
    __DIR__ . '/logs/error.log',
    __DIR__ . '/logs/debug.log'
];

echo "<h2>Application Logs</h2>";
foreach ($appLogs as $logFile) {
    if (file_exists($logFile) && is_readable($logFile)) {
        echo "<div class='info'><strong>Found app log:</strong> $logFile</div>";
        $content = file_get_contents($logFile);
        $lines = explode("\n", $content);
        $recentLines = array_slice($lines, -20); // Last 20 lines
        echo "<h3>Last 20 lines from $logFile:</h3>";
        echo "<pre>" . htmlspecialchars(implode("\n", $recentLines)) . "</pre>";
    } else {
        echo "<div class='error'>Log file not found or not readable: $logFile</div>";
    }
}

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    
    $host = DB_HOST;
    $dbname = DB_NAME;
    $username = DB_USER;
    $password = DB_PASS;
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='info'><strong>Database connection:</strong> SUCCESS</div>";
    
    // Test users table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div class='info'><strong>Users table:</strong> {$result['count']} users found</div>";
    
    // Check for admin user
    $stmt = $pdo->prepare("SELECT id, username, email, status FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<div class='info'><strong>Admin user found:</strong> ID={$admin['id']}, Username={$admin['username']}, Email={$admin['email']}, Status={$admin['status']}</div>";
    } else {
        echo "<div class='error'><strong>No admin user found!</strong> This might be why login is failing.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Database connection failed:</strong> " . $e->getMessage() . "</div>";
}

// Test authentication flow
echo "<h2>Authentication Test</h2>";
try {
    require_once __DIR__ . '/utils/Auth.php';
    echo "<div class='info'><strong>Auth class:</strong> Loaded successfully</div>";
    
    // Test with dummy credentials to see error handling
    $auth = new Auth();
    $result = $auth->authenticate('test@example.com', 'wrongpassword');
    echo "<div class='info'><strong>Test auth result:</strong> " . json_encode($result) . "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Auth test failed:</strong> " . $e->getMessage() . "</div>";
}

// Display current PHP configuration
echo "<h2>PHP Configuration</h2>";
echo "<div class='info'><strong>PHP Version:</strong> " . PHP_VERSION . "</div>";
echo "<div class='info'><strong>Error Reporting:</strong> " . error_reporting() . "</div>";
echo "<div class='info'><strong>Display Errors:</strong> " . (ini_get('display_errors') ? 'ON' : 'OFF') . "</div>";
echo "<div class='info'><strong>Log Errors:</strong> " . (ini_get('log_errors') ? 'ON' : 'OFF') . "</div>";
echo "<div class='info'><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . " seconds</div>";
echo "<div class='info'><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</div>";

// Log this debug session
error_log("[" . date('Y-m-d H:i:s') . "] Debug script executed from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'CLI'));

echo "<h2>Recent Error Log Entries</h2>";
if (file_exists($customErrorLog)) {
    $content = file_get_contents($customErrorLog);
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
} else {
    echo "<div class='error'>No custom error log found yet.</div>";
}

echo "<p><strong>Debug completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>