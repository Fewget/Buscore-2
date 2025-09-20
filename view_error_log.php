<?php
// Display all errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

$logFile = __DIR__ . '/logs/php_errors.log';

// Clear the log if requested
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    file_put_contents($logFile, '');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Display the log
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    echo "<pre>" . htmlspecialchars($logContent) . "</pre>";
} else {
    echo "Log file not found at: " . htmlspecialchars($logFile);
}

// Add a button to clear the log
echo "<p><a href='?clear=1'>Clear Log</a>";
?>
