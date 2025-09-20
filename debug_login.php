<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/config.php';

// Test database connection
try {
    $pdo->query('SELECT 1');
    echo "✅ Database connection successful<br>";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// Test user lookup
$username = 'admin';
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User 'admin' found in database<br>";
        echo "<pre>User data: " . print_r($user, true) . "</pre>";
        
        // Test password verification
        $password = 'admin123';
        if (password_verify($password, $user['password'])) {
            echo "✅ Password verification successful<br>";
            
            // Test session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            echo "✅ Session variables set successfully<br>";
            echo "<pre>Session data: " . print_r($_SESSION, true) . "</pre>";
            
            // Test redirect
            $redirect = ($user['role'] === 'admin') ? '/admin/dashboard.php' : '/';
            echo "✅ Would redirect to: " . htmlspecialchars($redirect) . "<br>";
            
        } else {
            echo "❌ Password verification failed<br>";
            echo "Password hash in DB: " . $user['password'] . "<br>";
            echo "Hash of 'admin123': " . password_hash('admin123', PASSWORD_DEFAULT) . "<br>";
        }
    } else {
        echo "❌ User 'admin' not found in database<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Error querying database: " . $e->getMessage() . "<br>";
}

// Check for any PHP errors
$errors = error_get_last();
if ($errors) {
    echo "<h3>PHP Errors:</h3>";
    echo "<pre>" . print_r($errors, true) . "</pre>";
}

// Check PHP info
if (isset($_GET['phpinfo'])) {
    phpinfo();
    exit;
}
?>

<h3>Next Steps:</h3>
<ol>
    <li><a href="login.php">Try logging in again</a></li>
    <li><a href="debug_login.php?phpinfo=1">View PHP Info</a> (for server configuration)</li>
    <li><a href="reset_admin.php">Reset Admin Password</a> (if needed)</li>
</ol>
