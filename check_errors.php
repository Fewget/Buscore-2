<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Display PHP errors
echo "<h2>PHP Error Log</h2>";
echo "<pre>";
$logFile = ini_get('error_log');
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    // Show last 20 lines
    $logLines = explode("\n", $logContent);
    $logLines = array_slice($logLines, -20);
    echo "Last 20 error log entries:\n";
    echo implode("\n", $logLines);
} else {
    echo "Error log file not found at: " . htmlspecialchars($logFile);
}
echo "</pre>";

// Check session status
echo "<h2>Session Status</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "Session variables:\n";
    print_r($_SESSION);
} else {
    echo "No active session";
}
echo "</pre>";

// Check file permissions
echo "<h2>File Permissions</h2>";
echo "<pre>";
$filesToCheck = [
    __FILE__,
    __DIR__ . '/includes/config.php',
    __DIR__ . '/login.php',
    __DIR__ . '/admin/dashboard.php',
    session_save_path()
];

foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        echo sprintf(
            "%s : %s (%o)\n",
            $file,
            substr(sprintf('%o', $perms), -4),
            $perms
        );
    } else {
        echo "$file : Not found\n";
    }
}
echo "</pre>";

// Check PHP configuration
echo "<h2>PHP Configuration</h2>";
echo "<pre>";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.use_strict_mode: " . ini_get('session.use_strict_mode') . "\n";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "\n";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "\n";
echo "</pre>";
?>

<h2>Next Steps</h2>
<ol>
    <li><a href="login.php">Try logging in again</a></li>
    <li><a href="?phpinfo=1">View PHP Info</a></li>
    <li><a href="reset_admin.php">Reset Admin Password</a></li>
</ol>
