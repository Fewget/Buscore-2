<?php
require_once 'includes/config.php';

$username = 'wqw';

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Update user role to 'bus_owner'
    $stmt = $pdo->prepare("UPDATE users SET role = 'bus_owner' WHERE username = ?");
    $stmt->execute([$username]);
    
    // 2. Verify the update
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    // 3. Commit the transaction
    $pdo->commit();
    
    echo "<h2>✅ User Role Updated Successfully</h2>";
    echo "<p>Username: " . htmlspecialchars($user['username']) . "</p>";
    echo "<p>New Role: " . htmlspecialchars($user['role']) . "</p>";
    
    // 4. Verify bus owner status
    $stmt = $pdo->prepare("SELECT * FROM bus_owners WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $bus_owner = $stmt->fetch();
    
    echo "<h3>Bus Owner Status</h3>";
    if ($bus_owner) {
        echo "<p>✅ User is registered as a bus owner</p>";
        echo "<p>Company Name: " . htmlspecialchars($bus_owner['company_name']) . "</p>";
    } else {
        echo "<p>❌ User is not registered as a bus owner. Creating entry...</p>";
        $stmt = $pdo->prepare("INSERT INTO bus_owners (user_id, company_name) VALUES (?, ?)");
        $stmt->execute([$user['id'], $user['username']]);
        echo "<p>✅ Created bus owner entry</p>";
    }
    
    echo "<p><a href='check_owner.php' class='btn btn-primary mt-3'>Check Bus Owner Status</a></p>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
