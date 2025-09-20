<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session variables for testing
$_SESSION['user_id'] = 1;
$_SESSION['is_admin'] = true;

// Display session info
echo '<h2>Session Information</h2>';
echo '<pre>';
print_r($_SESSION);
echo '</pre>';

// Test session ID and name
echo '<h3>Session Status</h3>';
echo 'Session ID: ' . session_id() . '<br>';
echo 'Session Name: ' . session_name() . '<br>';
echo 'Cookie Parameters: <pre>';
print_r(session_get_cookie_params());
echo '</pre>';

// Test session write
$_SESSION['test_time'] = date('Y-m-d H:i:s');

// Test session save path
$savePath = session_save_path();
echo '<h3>Session Save Path</h3>';
echo 'Save Path: ' . ($savePath ?: 'default') . '<br>';

// Check if session file exists
$sessionFile = $savePath . '/sess_' . session_id();
if (file_exists($sessionFile)) {
    echo 'Session file exists and is writable: ' . $sessionFile . '<br>';
    echo 'File size: ' . filesize($sessionFile) . ' bytes<br>';
} else {
    echo 'Session file does not exist or is not writable: ' . $sessionFile . '<br>';
    
    // Try to create a test file to check permissions
    $testFile = $savePath . '/test_write.tmp';
    if (file_put_contents($testFile, 'test') !== false) {
        echo 'Successfully wrote to test file: ' . $testFile . '<br>';
        unlink($testFile); // Clean up
    } else {
        echo 'Failed to write to test file. Check directory permissions: ' . $savePath . '<br>';
    }
}

// Test database connection
echo '<h3>Database Connection Test</h3>';
try {
    require_once __DIR__ . '/includes/config.php';
    $pdo->query('SELECT 1');
    echo 'Database connection successful!<br>';
    
    // Test user in database
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo 'User found in database: ' . htmlspecialchars($user['username'] ?? 'N/A') . '<br>';
        echo 'User data from database: <pre>' . print_r($user, true) . '</pre>';
        
        // Check admin status from database
        $isAdmin = ($user['role'] ?? '') === 'admin' || ($user['is_admin'] ?? 0) == 1;
        echo 'Is admin (from database): ' . ($isAdmin ? 'Yes' : 'No') . '<br>';
    } else {
        echo 'User not found in database!<br>';
    }
} catch (PDOException $e) {
    echo 'Database connection failed: ' . $e->getMessage() . '<br>';
}

// Test file permissions
echo '<h3>File Permissions</h3>';
$filesToCheck = [
    __FILE__,
    __DIR__ . '/includes/config.php',
    __DIR__ . '/includes/functions.php',
    session_save_path()
];

foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $readable = is_readable($file) ? 'Yes' : 'No';
        $writable = is_writable($file) ? 'Yes' : 'No';
        echo sprintf(
            '%s - Read: %s, Write: %s, Perms: %o<br>',
            $file,
            $readable,
            $writable,
            $perms & 0777
        );
    } else {
        echo $file . ' - Does not exist<br>';
    }
}

// Test AJAX request
echo '<h3>Test AJAX Request</h3>';
echo '<button id="testAjax">Test AJAX Request</button>';
echo '<div id="ajaxResult"></div>';
?>

<script>
document.getElementById('testAjax').addEventListener('click', function() {
    const resultDiv = document.getElementById('ajaxResult');
    resultDiv.textContent = 'Sending request...';
    
    fetch('update_bus_feature.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'bus_id=1&feature_name=show_bus_name&is_active=1'
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.innerHTML = '<strong>Response:</strong><pre>' + JSON.stringify(data, null, 2) + '</pre>';
    })
    .catch(error => {
        resultDiv.textContent = 'Error: ' + error.message;
    });
});
</script>
