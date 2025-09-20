<?php
require_once 'includes/config.php';

try {
    // Set new admin password
    $newPassword = 'admin123';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update admin password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashedPassword]);
    
    echo "<h2>Admin Password Reset</h2>";
    echo "<p>✅ Admin password has been reset to: <strong>admin123</strong></p>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    die("<h2>Error</h2><p>" . $e->getMessage() . "</p>");
}
?>
