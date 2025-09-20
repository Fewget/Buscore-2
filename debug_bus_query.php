<?php
require_once 'includes/config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

try {
    // Test with a known user ID (you may need to adjust this)
    $testUserId = 2; // Change this to a valid user ID
    
    echo "Testing with user ID: $testUserId\n\n";
    
    // Test the base query without subqueries
    echo "Testing base query...\n";
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE user_id = ?");
    $stmt->execute([$testUserId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($result) . " buses.\n";
    print_r($result);
    
    // Test the ratings subquery
    echo "\nTesting ratings subquery...\n";
    $busId = !empty($result) ? $result[0]['id'] : 1; // Use first bus or default to 1
    
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count FROM ratings WHERE bus_id = ?");
    $stmt->execute([$busId]);
    $ratings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Ratings for bus $busId: \n";
    print_r($ratings);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    
    // Show the last query error if available
    if (isset($pdo)) {
        $errorInfo = $pdo->errorInfo();
        echo "PDO Error: " . ($errorInfo[2] ?? 'No error details') . "\n";
    }
}

echo "\nDebug complete.";
