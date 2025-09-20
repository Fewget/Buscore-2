<?php
// Test PHP configuration
echo "PHP Version: " . phpversion() . "\n";

// Test error logging
$logFile = __DIR__ . '/logs/php_errors.log';
$testMessage = "[Test] PHP is working correctly at " . date('Y-m-d H:i:s') . "\n";

// Try to write to log file
if (file_put_contents($logFile, $testMessage, FILE_APPEND) === false) {
    echo "Error: Could not write to log file at $logFile\n";
    echo "Check file permissions and directory existence.\n";
} else {
    echo "Successfully wrote to log file at $logFile\n";
}

// Test database connection
if (file_exists('includes/config.php')) {
    require_once 'includes/config.php';
    
    try {
        $pdo->query('SELECT 1');
        echo "Database connection successful!\n";
    } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "Warning: includes/config.php not found\n";
}
