<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

// Test user credentials
$test_username = 'wqw';
$test_password = '12345678';

echo "<h2>Testing Login for user: $test_username</h2>";

try {
    // 1. Test database connection
    $pdo->query('SELECT 1');
    echo "✅ Database connection successful<br><br>";
    
    // 2. Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$test_username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ User '$test_username' found in database<br>";
        echo "<pre>User data: " . print_r($user, true) . "</pre>";
        
        // 3. Check if user is active (default to active if not set)
        $isActive = $user['is_active'] ?? 1; // Default to active if not set
        
        if ($isActive != 1) {
            echo "❌ User account is not active<br>";
            // Update the user to be active
            $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?")->execute([$user['id']]);
            echo "✅ User account has been activated<br>";
        } else {
            echo "✅ User account is active<br>";
            
            // 4. Test password verification
            if (password_verify($test_password, $user['password'])) {
                echo "✅ Password is correct<br>";
                
                // 5. Check if password needs rehashing
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    echo "ℹ️ Password needs rehashing (this is normal for old passwords)<br>";
                }
                
                // 6. Test session
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                echo "✅ Session variables set successfully<br>";
                echo "<pre>Session data: " . print_r($_SESSION, true) . "</pre>";
                
                // 7. Check where user would be redirected
                $redirect = ($user['role'] === 'admin') ? '/admin/dashboard.php' : '/';
                echo "✅ Login successful! Would redirect to: " . htmlspecialchars($redirect) . "<br>";
                
            } else {
                echo "❌ Password is incorrect<br>";
                echo "Hash in database: " . $user['password'] . "<br>";
                echo "Hash of '$test_password': " . password_hash($test_password, PASSWORD_DEFAULT) . "<br>";
            }
        }
    } else {
        echo "❌ User '$test_username' not found in database<br>";
        
        // List all users for debugging
        echo "<h3>All users in database:</h3>";
        $all_users = $pdo->query("SELECT id, username, is_active FROM users")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($all_users, true) . "</pre>";
    }
    
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}

// 8. Check if user is in bus_owners table if not found in users
if (!isset($user)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM bus_owners WHERE username = ?");
        $stmt->execute([$test_username]);
        $bus_owner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bus_owner) {
            echo "<br>ℹ️ User found in bus_owners table but not in users table. This might be the issue.<br>";
            echo "<pre>Bus owner data: " . print_r($bus_owner, true) . "</pre>";
        }
    } catch (PDOException $e) {
        echo "<br>ℹ️ Could not check bus_owners table: " . $e->getMessage() . "<br>";
    }
}

// 9. Check if users table exists
$tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
if (empty($tables)) {
    echo "<br>❌ The 'users' table does not exist in the database!<br>";
}

// 10. Check database structure
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "<h3>Database Tables:</h3><pre>" . print_r($tables, true) . "</pre>";

// 11. Check users table structure
if (in_array('users', $tables)) {
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Users table columns:</h3><pre>" . print_r($columns, true) . "</pre>";
}

// 12. List all users with their roles
$users = $pdo->query("SELECT id, username, role, is_active FROM users")->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>All Users:</h3><pre>" . print_r($users, true) . "</pre>";

// 13. Check if the user exists but with different case (MySQL is case-insensitive by default)
$stmt = $pdo->prepare("SELECT username FROM users WHERE LOWER(username) = LOWER(?)");
$stmt->execute([$test_username]);
$similar_user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($similar_user && strcasecmp($similar_user['username'], $test_username) !== 0) {
    echo "<div style='background-color: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffeeba;'>";
    echo "ℹ️ Note: Found a similar username with different case: <strong>" . htmlspecialchars($similar_user['username']) . "</strong><br>";
    echo "MySQL is case-insensitive by default, but your application might be case-sensitive.</div>";
}

// 14. Check for any PHP session or cookie issues
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<h3>Session Information:</h3>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Session status: " . session_status() . "<br>";
    echo "<pre>Session data: " . print_r($_SESSION, true) . "</pre>";
} else {
    echo "<h3>Session Information:</h3>";
    echo "Session is not active. Status: " . session_status() . "<br>";
}

// 15. Check for common PHP configuration issues
$checks = [
    'session.auto_start' => ini_get('session.auto_start'),
    'session.use_cookies' => ini_get('session.use_cookies'),
    'session.use_only_cookies' => ini_get('session.use_only_cookies'),
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
    'session.save_path' => is_writable(session_save_path()) ? 'writable' : 'NOT writable: ' . session_save_path(),
];

echo "<h3>PHP Session Configuration:</h3><pre>";
foreach ($checks as $key => $value) {
    echo str_pad($key, 30) . ": $value<br>";
}
echo "</pre>";

// 16. Check for any error logs
$error_log = ini_get('error_log');
echo "<h3>Error Log:</h3>";
echo "Error log location: " . ($error_log ?: 'Not set in php.ini') . "<br>";
if ($error_log && file_exists($error_log)) {
    $log_content = file_get_contents($error_log);
    echo "<pre>Last 20 lines of error log:\n" . 
         implode("\n", array_slice(explode("\n", $log_content), -20)) . "</pre>";
} else {
    echo "Error log file not found or not accessible.<br>";
}

echo "<h3>Test Complete</h3>";
?>
