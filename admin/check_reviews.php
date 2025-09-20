<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

// Check if tables exist
$tables = $pdo->query("SHOW TABLES LIKE 'reviews'")->fetchAll();
if (empty($tables)) {
    die("The 'reviews' table doesn't exist in the database.");
}

// Check if there are any reviews
$reviewCount = $pdo->query("SELECT COUNT(*) as count FROM reviews")->fetch()['count'];
echo "Total reviews in database: " . $reviewCount . "<br><br>";

// Show sample reviews if any exist
if ($reviewCount > 0) {
    echo "<h3>Sample Reviews:</h3>";
    $reviews = $pdo->query("SELECT * FROM reviews LIMIT 5")->fetchAll();
    echo "<pre>";
    print_r($reviews);
    echo "</pre>";
} else {
    echo "No reviews found in the database. Would you like to add a test review? ";
    echo "<a href='add_test_review.php' class='btn btn-primary'>Add Test Review</a>";
}
?>
