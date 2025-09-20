<?php
require_once 'includes/config.php';

try {
    // Check users table structure
    $stmt = $pdo->query("SHOW CREATE TABLE users");
    $table = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Users Table Structure</h2>";
    echo "<pre>" . htmlspecialchars($table['Create Table']) . "</pre>";
    
    // Check for triggers that might be interfering
    $stmt = $pdo->query("SHOW TRIGGERS LIKE 'users'");
    $triggers = $stmt->fetchAll();
    
    if (count($triggers) > 0) {
        echo "<h3>Triggers on users table:</h3>";
        echo "<pre>";
        print_r($triggers);
        echo "</pre>";
    } else {
        echo "<p>No triggers found on users table.</p>";
    }
    
    // Direct SQL to update the role
    echo "<h3>Attempting direct update...</h3>";
    $pdo->exec("UPDATE users SET role = 'bus_owner' WHERE username = 'wqw'");
    
    // Verify
    $stmt = $pdo->query("SELECT username, role FROM users WHERE username = 'wqw'");
    $result = $stmt->fetch();
    
    echo "<h3>After update:</h3>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    // Check if column is ENUM with invalid values
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Role column definition:</h3>";
    echo "<pre>";
    print_r($column);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}
?>
