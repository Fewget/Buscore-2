<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

// Get table structure
try {
    // Check if table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'ratings'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("The 'ratings' table doesn't exist in the database.");
    }
    
    // Get table structure
    $stmt = $pdo->query("DESCRIBE ratings");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Ratings Table Columns:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Show sample data
    echo "<h3>Sample Data (first 5 rows):</h3>";
    $sampleData = $pdo->query("SELECT * FROM ratings LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($sampleData);
    echo "</pre>";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
