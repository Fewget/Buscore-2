<?php
require_once 'includes/config.php';

$username = 'wqw';

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Update user role to bus_owner
    $stmt = $pdo->prepare("UPDATE users SET role = 'bus_owner' WHERE username = ?");
    $stmt->execute([$username]);
    
    // 2. Get user ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        // 3. Insert into bus_owners table if not exists
        $stmt = $pdo->prepare("INSERT IGNORE INTO bus_owners (user_id) VALUES (?)");
        $stmt->execute([$user['id']]);
        
        // 4. Commit transaction
        $pdo->commit();
        
        echo "✅ Successfully updated '$username' to bus owner role.";
        echo "<p><a href='check_owner.php'>Check Bus Owner Status</a></p>";
    } else {
        $pdo->rollBack();
        echo "❌ User '$username' not found.";
    }
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage();
}

// Show current status
echo "<h3>Current Status of '$username'</h3>";
$stmt = $pdo->prepare("SELECT u.id, u.username, u.role, bo.company_name 
                      FROM users u 
                      LEFT JOIN bus_owners bo ON u.id = bo.user_id 
                      WHERE u.username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

echo "<pre>";
print_r($user);
echo "</pre>";
?>
