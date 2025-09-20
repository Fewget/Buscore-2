<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

try {
    // Get first bus and user
    $bus = $pdo->query("SELECT id FROM buses LIMIT 1")->fetch();
    $user = $pdo->query("SELECT id FROM users WHERE role = 'user' LIMIT 1")->fetch();
    
    if (!$bus || !$user) {
        die("Could not find a bus or user to create a test review.");
    }
    
    // Insert test review
    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, bus_id, comment, is_approved, created_at) 
                          VALUES (?, ?, ?, 1, NOW())");
    $stmt->execute([
        $user['id'],
        $bus['id'],
        'This is a test review to check the admin panel functionality.'
    ]);
    
    header("Location: check_reviews.php?success=1");
    exit();
    
} catch (PDOException $e) {
    die("Error creating test review: " . $e->getMessage());
}
?>
