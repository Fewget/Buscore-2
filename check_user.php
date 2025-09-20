<?php
require_once 'includes/config.php';

$username = 'wqw';

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<h2>User Found</h2>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        // Check bus_owners table
        $stmt = $pdo->prepare("SELECT * FROM bus_owners WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $bus_owner = $stmt->fetch();
        
        echo "<h3>Bus Owner Status</h3>";
        if ($bus_owner) {
            echo "<p>✅ User is registered in bus_owners table</p>";
            echo "<pre>";
            print_r($bus_owner);
            echo "</pre>";
        } else {
            echo "<p>❌ User is NOT registered in bus_owners table</p>";
        }
    } else {
        echo "<p>❌ User '$username' not found in users table.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Show database structure for debugging
echo "<h3>Database Structure</h3>";
try {
    $tables = ['users', 'bus_owners'];
    foreach ($tables as $table) {
        echo "<h4>Table: $table</h4>";
        $result = $pdo->query("SHOW COLUMNS FROM $table");
        echo "<pre>" . print_r($result->fetchAll(PDO::FETCH_COLUMN), true) . "</pre>";
    }
} catch (PDOException $e) {
    echo "<p>Error checking table structure: " . $e->getMessage() . "</p>";
}
?>
