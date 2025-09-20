<?php
require_once 'includes/config.php';

try {
    // 1. Add is_active column if it doesn't exist
    $pdo->exec("ALTER TABLE users 
               ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 
               COMMENT '1 = active, 0 = inactive'");
    
    echo "<h2>✅ Updated Users Table</h2>";
    echo "<p>Added is_active column if it didn't exist.</p>";
    
    // 2. Ensure the user is active
    $pdo->exec("UPDATE users SET is_active = 1 WHERE username = 'wqw'");
    
    // 3. Verify the update
    $stmt = $pdo->query("SELECT username, is_active, role FROM users WHERE username = 'wqw'");
    $user = $stmt->fetch();
    
    echo "<h3>User Status</h3>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    if ($user['is_active'] == 1) {
        echo "<div class='alert alert-success'>✅ User 'wqw' is now active and should be able to log in.</div>";
        echo "<p><a href='login.php' class='btn btn-success'>Try Logging In Now</a></p>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ Something went wrong. Please try logging in again.</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}
?>
