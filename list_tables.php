<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=buscore_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h1>Database Tables</h1>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Show structure of buses table
    echo "<h2>Buses Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE buses");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
