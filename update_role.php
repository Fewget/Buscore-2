<?php
session_start();
require_once 'includes/config.php';

// Only allow this to be run in development
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied');
}

try {
    // Update the user's role to bus_owner
    $stmt = $pdo->prepare("UPDATE users SET role = 'bus_owner' WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    // Update the session
    $_SESSION['role'] = 'bus_owner';
    
    echo "Your account has been updated to bus_owner role. <a href='profile.php'>Go to Dashboard</a>";
} catch (PDOException $e) {
    die("Error updating role: " . $e->getMessage());
}
