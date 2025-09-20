<?php
require_once 'includes/config.php';

try {
    // 1. Modify the ENUM to include 'bus_owner'
    $sql = "ALTER TABLE users 
            MODIFY COLUMN role ENUM('admin', 'owner', 'user', 'bus_owner') DEFAULT 'user'";
    $pdo->exec($sql);
    
    echo "<h2>✅ Updated role ENUM to include 'bus_owner'</h2>";
    
    // 2. Now update the user's role
    $stmt = $pdo->prepare("UPDATE users SET role = 'bus_owner' WHERE username = 'wqw'");
    $stmt->execute();
    
    // 3. Verify the update
    $stmt = $pdo->query("SELECT username, role FROM users WHERE username = 'wqw'");
    $user = $stmt->fetch();
    
    echo "<h3>User Status After Update:</h3>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    if ($user['role'] === 'bus_owner') {
        echo "<div class='alert alert-success'>✅ Success! User role is now 'bus_owner'</div>";
        echo "<p><a href='check_owner.php' class='btn btn-success'>Check Bus Owner Status</a></p>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ Role update may not have worked. Current role: " . 
             htmlspecialchars($user['role']) . "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}
?>
