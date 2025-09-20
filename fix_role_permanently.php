<?php
require_once 'includes/config.php';

$username = 'wqw';

try {
    // 1. Direct SQL update to ensure role is set
    $pdo->exec("SET SQL_SAFE_UPDATES=0;");
    
    // 2. Update the role
    $stmt = $pdo->prepare("UPDATE users SET role = 'bus_owner' WHERE username = ?");
    $stmt->execute([$username]);
    
    // 3. Force refresh the user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    echo "<h2>Database Update Results</h2>";
    echo "<p>Updated user '{$user['username']}' with ID: {$user['id']}</p>";
    echo "<p>Role is now set to: " . ($user['role'] ?: 'NULL (empty)') . "</p>";
    
    // 4. Verify bus_owners entry
    $stmt = $pdo->prepare("SELECT * FROM bus_owners WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $bus_owner = $stmt->fetch();
    
    if ($bus_owner) {
        echo "<p>✅ Bus owner entry exists with company: " . htmlspecialchars($bus_owner['company_name']) . "</p>";
    } else {
        echo "<p>❌ No bus_owners entry found. Creating one...</p>";
        $stmt = $pdo->prepare("INSERT INTO bus_owners (user_id, company_name) VALUES (?, ?)");
        $stmt->execute([$user['id'], $user['username']]);
        echo "<p>✅ Created bus_owners entry</p>";
    }
    
    // 5. Final verification
    echo "<h3>Verification Query</h3>";
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'version'");
    $version = $stmt->fetch();
    echo "<p>MySQL Version: " . $version['Value'] . "</p>";
    
    $stmt = $pdo->query("SELECT * FROM users WHERE username = 'wqw'");
    $final = $stmt->fetch();
    echo "<pre>Final user data: ";
    print_r($final);
    echo "</pre>";
    
    // 6. Force a login session update
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['role'] = 'bus_owner';
    
    echo "<p>✅ Session role set to: bus_owner</p>";
    echo "<p><a href='check_owner.php' class='btn btn-primary'>Verify Status</a></p>";
    
} catch (PDOException $e) {
    die("<div class='alert alert-danger'><strong>Database Error:</strong> " . 
        htmlspecialchars($e->getMessage()) . "</div>");
}
?>
