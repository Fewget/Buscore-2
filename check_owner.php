<?php
require_once 'includes/config.php';

$username = 'wqw';

try {
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.role, bo.company_name 
                         FROM users u 
                         LEFT JOIN bus_owners bo ON u.id = bo.user_id 
                         WHERE u.username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<h2>User Found</h2>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        if ($user['role'] === 'bus_owner') {
            echo "<p>✅ User '{$user['username']}' is registered as a bus owner.</p>";
            if (!empty($user['company_name'])) {
                echo "<p>Company Name: {$user['company_name']}</p>";
            } else {
                echo "<p>⚠️ No company name registered for this bus owner.</p>";
            }
        } else {
            echo "<p>❌ User '{$user['username']}' is not registered as a bus owner. Current role: {$user['role']}</p>";
        }
    } else {
        echo "<p>❌ User '{$username}' not found in the database.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Show all bus owners for reference
echo "<h3>All Bus Owners</h3>";
try {
    $stmt = $pdo->query("SELECT u.id, u.username, u.role, u.created_at, bo.company_name 
                        FROM users u 
                        LEFT JOIN bus_owners bo ON u.id = bo.user_id 
                        WHERE u.role = 'bus_owner'");
    $owners = $stmt->fetchAll();
    
    if (count($owners) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Company</th><th>Registered On</th></tr>";
        foreach ($owners as $owner) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($owner['id']) . "</td>";
            echo "<td>" . htmlspecialchars($owner['username']) . "</td>";
            echo "<td>" . htmlspecialchars($owner['company_name'] ?? 'Not set') . "</td>";
            echo "<td>" . htmlspecialchars($owner['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No bus owners found in the database.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Error fetching bus owners: " . $e->getMessage() . "</p>";
}
?>
