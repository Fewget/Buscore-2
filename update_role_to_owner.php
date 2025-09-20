<?php
require_once 'includes/config.php';

try {
    // 1. First, update the role to 'owner' for the user 'wqw'
    $stmt = $pdo->prepare("UPDATE users SET role = 'owner' WHERE username = 'wqw'");
    $stmt->execute();
    
    // 2. Verify the update
    $stmt = $pdo->query("SELECT username, role FROM users WHERE username = 'wqw'");
    $user = $stmt->fetch();
    
    echo "<h2>Role Update Results</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    if ($user['role'] === 'owner') {
        echo "<div class='alert alert-success'>✅ Success! User role is now 'owner'</div>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ Role update may not have worked. Current role: " . 
             htmlspecialchars($user['role']) . "</div>";
    }
    
    // 3. Check if we need to update the ENUM values
    if ($user['role'] !== 'owner') {
        echo "<h3>Updating ENUM values...</h3>";
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'owner', 'user') DEFAULT 'user'");
        echo "<p>✅ Updated ENUM values to include only: 'admin', 'owner', 'user'</p>";
        
        // Try updating again
        $pdo->exec("UPDATE users SET role = 'owner' WHERE username = 'wqw'");
        $stmt = $pdo->query("SELECT username, role FROM users WHERE username = 'wqw'");
        $user = $stmt->fetch();
        
        echo "<h3>After ENUM update:</h3>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    }
    
    echo "<p><a href='check_owner.php' class='btn btn-primary'>Check Bus Owner Status</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}
?>
