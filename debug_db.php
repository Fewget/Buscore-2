<?php
require_once 'includes/config.php';

try {
    // Get table structure
    $stmt = $pdo->query("DESCRIBE buses");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Buses Table Structure:</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Get a sample bus record
    $stmt = $pdo->query("SELECT * FROM buses LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Sample Bus Record:</h2>";
    echo "<pre>";
    print_r($sample);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>
