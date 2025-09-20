<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

echo "<h2>Updating Users Table</h2>";

try {
    // Check if is_active column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        echo "Adding 'is_active' column to users table...<br>";
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1 COMMENT '1 for active, 0 for inactive'");
        echo "✅ Added 'is_active' column to users table<br>";
    } else {
        echo "✅ 'is_active' column already exists in users table<br>";
    }
    
    // Check if role column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $roleColumnExists = $stmt->rowCount() > 0;
    
    if (!$roleColumnExists) {
        echo "Adding 'role' column to users table...<br>";
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user' COMMENT 'user, admin, or bus_owner'");
        echo "✅ Added 'role' column to users table<br>";
    } else {
        echo "✅ 'role' column already exists in users table<br>";
    }
    
    // Check if last_login column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
    $lastLoginExists = $stmt->rowCount() > 0;
    
    if (!$lastLoginExists) {
        echo "Adding 'last_login' column to users table...<br>";
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME NULL DEFAULT NULL");
        echo "✅ Added 'last_login' column to users table<br>";
    } else {
        echo "✅ 'last_login' column already exists in users table<br>";
    }
    
    // Check if we need to update existing users to be active
    $pdo->exec("UPDATE users SET is_active = 1 WHERE is_active IS NULL OR is_active = 0");
    echo "✅ Updated all existing users to be active<br>";
    
    // Show current users
    echo "<h3>Current Users:</h3>";
    $users = $pdo->query("SELECT id, username, email, role, is_active, last_login FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($users, true) . "</pre>";
    
    echo "<div style='background-color: #d4edda; padding: 15px; margin: 15px 0; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    echo "<h3>✅ Database Update Complete!</h3>";
    echo "<p>Please try logging in again. If you still have issues, please check the following:</p>";
    echo "<ol>";
    echo "<li>Make sure your username and password are correct</li>";
    echo "<li>Clear your browser cache and cookies</li>";
    echo "<li>Try using a different browser or incognito window</li>";
    echo "</ol>";
    echo "<p>If you're still having trouble, please contact support with the information shown above.</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</div>";
    
    // Show the full SQL error for debugging
    echo "<div style='background-color: #f8d7da; padding: 15px; margin: 15px 0; border: 1px solid #f5c6cb; border-radius: 4px;'>";
    echo "<h4>Debug Information:</h4>";
    echo "<pre>Error Code: " . $e->getCode() . "\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    echo "</pre>";
    
    // Show database structure for debugging
    echo "<h4>Database Structure:</h4>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>Tables: " . print_r($tables, true) . "</pre>";
    
    if (in_array('users', $tables)) {
        $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>Users table structure: " . print_r($columns, true) . "</pre>";
    }
    
    echo "</div>";
}
?>
