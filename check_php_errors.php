<?php
// Display PHP errors on screen
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Show the error log path
$error_log = ini_get('error_log');
echo "<h2>PHP Error Log Location:</h2>";
echo "<p>" . ($error_log ? $error_log : 'Not set in php.ini') . "</p>";

// Try to read the error log
if ($error_log && file_exists($error_log)) {
    echo "<h2>Last 20 Error Log Entries:</h2>";
    echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 500px; overflow: auto;'>";
    $log_content = file($error_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $log_content = array_slice($log_content, -20); // Get last 20 lines
    echo implode("\n", $log_content);
    echo "</pre>";
} else {
    echo "<p>Could not find error log file.</p>";
}

// Test database connection
try {
    require_once 'includes/config.php';
    echo "<h2>Database Connection Test</h2>";
    echo "<p>Connection successful!</p>";
    
    // Test query
    $stmt = $pdo->query("SELECT VERSION()");
    $version = $stmt->fetchColumn();
    echo "<p>MySQL Version: " . htmlspecialchars($version) . "</p>";
    
} catch (PDOException $e) {
    echo "<h2>Database Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}

// Show current PHP settings
echo "<h2>PHP Settings</h2>";
echo "<pre>";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "error_reporting: " . ini_get('error_reporting') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "error_log: " . ini_get('error_log') . "\n";
echo "</pre>";
?>
