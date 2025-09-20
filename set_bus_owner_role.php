<?php
require_once 'includes/config.php';

try {
    // 1. Ensure the role ENUM includes 'bus_owner'
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'owner', 'user', 'bus_owner') DEFAULT 'user'");
    
    // 2. Update the role to 'bus_owner' for user 'wqw'
    $stmt = $pdo->prepare("UPDATE users SET role = 'bus_owner' WHERE username = 'wqw'");
    $stmt->execute();
    
    // 3. Ensure the user exists in bus_owners table
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'wqw'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO bus_owners (user_id, company_name) VALUES (?, ?)");
        $stmt->execute([$user['id'], 'wqw']);
    }
    
    // 4. Verify the update
    $stmt = $pdo->query("SELECT u.username, u.role, bo.company_name 
                         FROM users u 
                         LEFT JOIN bus_owners bo ON u.id = bo.user_id 
                         WHERE u.username = 'wqw'");
    $result = $stmt->fetch();
    
    echo "<h2>Update Results</h2>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result && $result['role'] === 'bus_owner') {
        echo "<div class='alert alert-success'>✅ Success! User 'wqw' is now a bus owner.</div>";
        echo "<p><a href='check_owner.php' class='btn btn-success'>Check Bus Owner Status</a></p>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ Something went wrong. Please check the database manually.</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}
?>
