<?php
/**
 * Database Migration Runner
 * This script applies the missing tables and columns migration
 */

// Include database configuration
require_once __DIR__ . '/../backend/config/database.php';

try {
    // Read the migration SQL file
    $migrationFile = __DIR__ . '/migration_fix_missing_tables_columns.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception('Migration file not found: ' . $migrationFile);
    }
    
    $sql = file_get_contents($migrationFile);
    
    if ($sql === false) {
        throw new Exception('Failed to read migration file');
    }
    
    // Create database connection
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "Connected to database successfully.\n";
    
    // Select the database
    $pdo->exec("USE `" . DB_NAME . "`");
    echo "Selected database: " . DB_NAME . "\n";
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', 
            preg_split('/;\s*$/m', $sql)
        ), 
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    echo "\nExecuting migration...\n";
    echo "Found " . count($statements) . " SQL statements to execute.\n\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        try {
            // Skip comments and empty statements
            if (empty(trim($statement)) || preg_match('/^\s*--/', $statement)) {
                continue;
            }
            
            $pdo->exec($statement);
            $successCount++;
            
            // Show progress for important statements
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`([^`]+)`/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "✓ Created table: $tableName\n";
            } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                preg_match('/ALTER TABLE.*?`([^`]+)`/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "✓ Modified table: $tableName\n";
            } elseif (stripos($statement, 'UPDATE') !== false) {
                echo "✓ Updated existing data\n";
            }
            
        } catch (PDOException $e) {
            $errorCount++;
            
            // Check if it's a harmless error (like column already exists)
            if (stripos($e->getMessage(), 'Duplicate column name') !== false ||
                stripos($e->getMessage(), 'Table') !== false && stripos($e->getMessage(), 'already exists') !== false) {
                echo "⚠ Warning (skipped): " . $e->getMessage() . "\n";
            } else {
                echo "✗ Error executing statement " . ($index + 1) . ": " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
            }
        }
    }
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "Migration completed!\n";
    echo "Successful statements: $successCount\n";
    echo "Errors/Warnings: $errorCount\n";
    
    // Run verification queries
    echo "\nVerifying migration results...\n";
    
    // Check user_activities table
    $result = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "' AND table_name = 'user_activities'");
    $exists = $result->fetch()['count'] > 0;
    echo ($exists ? "✓" : "✗") . " user_activities table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    
    // Check email column in employees
    $result = $pdo->query("SELECT COUNT(*) as count FROM information_schema.columns WHERE table_schema = '" . DB_NAME . "' AND table_name = 'employees' AND column_name = 'email'");
    $exists = $result->fetch()['count'] > 0;
    echo ($exists ? "✓" : "✗") . " employees.email column: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    
    // Check hours_worked column in attendance
    $result = $pdo->query("SELECT COUNT(*) as count FROM information_schema.columns WHERE table_schema = '" . DB_NAME . "' AND table_name = 'attendance' AND column_name = 'hours_worked'");
    $exists = $result->fetch()['count'] > 0;
    echo ($exists ? "✓" : "✗") . " attendance.hours_worked column: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    
    // Check pay_year column in payroll
    $result = $pdo->query("SELECT COUNT(*) as count FROM information_schema.columns WHERE table_schema = '" . DB_NAME . "' AND table_name = 'payroll' AND column_name = 'pay_year'");
    $exists = $result->fetch()['count'] > 0;
    echo ($exists ? "✓" : "✗") . " payroll.pay_year column: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    
    // Check pay_month column in payroll
    $result = $pdo->query("SELECT COUNT(*) as count FROM information_schema.columns WHERE table_schema = '" . DB_NAME . "' AND table_name = 'payroll' AND column_name = 'pay_month'");
    $exists = $result->fetch()['count'] > 0;
    echo ($exists ? "✓" : "✗") . " payroll.pay_month column: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    
    echo "\nMigration process completed successfully!\n";
    echo "You can now test your HRMS application.\n";
    echo "\nTo run the migration, execute: php run_migration.php\n";
    echo "Make sure your database configuration is correct in backend/config/database.php\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration and try again.\n";
    exit(1);
}
?>