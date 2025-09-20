<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM buses");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Columns in buses table:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Get sample data
    $sample = $pdo->query("SELECT id, registration_number, bus_name, company_name FROM buses LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Sample data:</h3>";
    echo "<pre>";
    print_r($sample);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
