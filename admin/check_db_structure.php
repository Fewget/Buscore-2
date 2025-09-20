<?php
require_once __DIR__ . '/includes/config.php';

try {
    // Check if ratings table exists
    $ratingsTableExists = $pdo->query("SHOW TABLES LIKE 'ratings'")->rowCount() > 0;
    
    // Check if reviews table exists
    $reviewsTableExists = $pdo->query("SHOW TABLES LIKE 'reviews'")->rowCount() > 0;
    
    // Get buses table structure
    $busesTable = $pdo->query("SHOW CREATE TABLE buses")->fetch(PDO::FETCH_ASSOC);
    
    // Get ratings table structure if it exists
    $ratingsTable = [];
    if ($ratingsTableExists) {
        $ratingsTable = $pdo->query("SHOW CREATE TABLE ratings")->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get reviews table structure if it exists
    $reviewsTable = [];
    if ($reviewsTableExists) {
        $reviewsTable = $pdo->query("SHOW CREATE TABLE reviews")->fetch(PDO::FETCH_ASSOC);
    }
    
    // Output results
    echo "<h2>Database Structure Check</h2>";
    
    echo "<h3>Buses Table</h3>";
    echo "<pre>" . htmlspecialchars($busesTable['Create Table'] ?? 'Not found') . "</pre>";
    
    echo "<h3>Ratings Table " . ($ratingsTableExists ? "(Exists)" : "(Missing)") . "</h3>";
    if ($ratingsTableExists) {
        echo "<pre>" . htmlspecialchars($ratingsTable['Create Table']) . "</pre>";
    } else {
        echo "<p>The 'ratings' table is missing. This is required for the admin dashboard.</p>";
        echo "<p>Would you like to create it now? <a href='create_ratings_table.php' class='btn btn-primary'>Create Ratings Table</a></p>";
    }
    
    echo "<h3>Reviews Table " . ($reviewsTableExists ? "(Exists)" : "(Missing)") . "</h3>";
    if ($reviewsTableExists) {
        echo "<pre>" . htmlspecialchars($reviewsTable['Create Table']) . "</pre>";
    } else {
        echo "<p>The 'reviews' table is missing. This is required for the admin dashboard.</p>";
        echo "<p>Would you like to create it now? <a href='create_reviews_table.php' class='btn btn-primary'>Create Reviews Table</a></p>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}
?>
