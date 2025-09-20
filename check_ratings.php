<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if we can connect to the database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if ratings table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'ratings'")->rowCount();
    if ($tables === 0) {
        die("Error: 'ratings' table does not exist in the database.");
    }
    
    // Check if there are any ratings for bus_id = 2
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM ratings WHERE bus_id = 2");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Number of ratings for bus_id 2: " . $count . "<br><br>";
    
    if ($count > 0) {
        // Show sample ratings
        $stmt = $pdo->query("SELECT * FROM ratings WHERE bus_id = 2 ORDER BY created_at DESC LIMIT 5");
        echo "Sample ratings:<br>";
        echo "<pre>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
        echo "</pre>";
    } else {
        echo "No ratings found for bus_id 2. Here's a sample of all ratings in the system:<br>";
        $stmt = $pdo->query("SELECT bus_id, COUNT(*) as count FROM ratings GROUP BY bus_id ORDER BY count DESC LIMIT 5");
        echo "<pre>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Bus ID: " . $row['bus_id'] . " - Ratings: " . $row['count'] . "\n";
        }
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
