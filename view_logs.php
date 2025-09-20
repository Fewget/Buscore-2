<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define log file path
$logFile = __DIR__ . '/logs/php_errors.log';

// Check if log file exists
if (!file_exists($logFile)) {
    die("Log file not found at: " . htmlspecialchars($logFile) . "<br>" .
        "Make sure the 'logs' directory exists and is writable.");
}

// Get log content
$logContent = file_get_contents($logFile);

// Display log content
?>
<!DOCTYPE html>
<html>
<head>
    <title>Error Logs</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { 
            background: #f5f5f5; 
            padding: 15px; 
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 600px;
            overflow-y: auto;
        }
        .controls { margin-bottom: 20px; }
        .btn { 
            padding: 8px 15px; 
            margin-right: 10px; 
            background: #4CAF50; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #45a049; }
        .btn-clear { background: #f44336; }
        .btn-clear:hover { background: #d32f2f; }
    </style>
</head>
<body>
    <h1>PHP Error Logs</h1>
    <div class="controls">
        <a href="?action=refresh" class="btn">Refresh</a>
        <a href="?action=clear" class="btn btn-clear" 
           onclick="return confirm('Are you sure you want to clear the logs?')">Clear Logs</a>
        <a href="add-bus.php" class="btn">Back to Form</a>
    </div>
    <pre><?php 
    if (isset($_GET['action']) && $_GET['action'] === 'clear') {
        file_put_contents($logFile, '');
        echo "Logs cleared at " . date('Y-m-d H:i:s');
    } else {
        echo htmlspecialchars($logContent); 
    }
    ?></pre>
</body>
</html>
