<?php
// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/logs';
$logFile = $logDir . '/php_errors.log';

// Create directory if it doesn't exist
if (!is_dir($logDir)) {
    if (mkdir($logDir, 0755, true)) {
        echo "Created logs directory at: " . htmlspecialchars($logDir) . "<br>";
    } else {
        die("Failed to create logs directory at: " . htmlspecialchars($logDir));
    }
}

// Create log file if it doesn't exist
if (!file_exists($logFile)) {
    if (file_put_contents($logFile, "[Log file created at " . date('Y-m-d H:i:s') . "]\n") === false) {
        die("Failed to create log file at: " . htmlspecialchars($logFile));
    }
    echo "Created log file at: " . htmlspecialchars($logFile) . "<br>";
}

// Set permissions
if (chmod($logFile, 0666)) {
    echo "Set permissions on log file<br>";
} else {
    echo "Warning: Could not set permissions on log file<br>";
}

// Test writing to log file
$testMessage = "[Test message at " . date('Y-m-d H:i:s') . "] Test writing to log file\n";
if (file_put_contents($logFile, $testMessage, FILE_APPEND) !== false) {
    echo "Successfully wrote to log file<br>";
} else {
    echo "Warning: Could not write to log file<br>";
}

// Show directory permissions
echo "<h3>Directory Permissions:</h3>";
echo "<pre>" . shell_exec('icacls ' . escapeshellarg($logDir) . ' 2>&1') . "</pre>";

// Show file permissions
echo "<h3>File Permissions:</h3>";
echo "<pre>" . shell_exec('icacls ' . escapeshellarg($logFile) . ' 2>&1') . "</pre>";

// Show next steps
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Try submitting the form again</li>";
echo "<li><a href='view_logs.php' target='_blank'>View Logs</a></li>";
echo "<li><a href='add-bus.php'>Go to Bus Form</a></li>";
echo "</ol>";

// Create a .htaccess file to prevent directory listing
$htaccess = $logDir . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Options -Indexes\nDeny from all");
    echo "<p>Created .htaccess file to secure logs directory</p>";
}
?>
