<?php
// Simple test script to check database connection and bus data

try {
    // Database configuration
    $host = '127.0.0.1';
    $dbname = 'buscore_db';
    $user = 'root';
    $pass = '';
    
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Connection Successful!</h2>";
    
    // Test query for bus with ID 2
    $bus_id = 2;
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->execute([$bus_id]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bus) {
        echo "<h3>Bus Found (ID: $bus_id)</h3>";
        echo "<pre>";
        print_r($bus);
        echo "</pre>";
    } else {
        echo "<h3>No bus found with ID: $bus_id</h3>";
    }
    
} catch (PDOException $e) {
    die("<h2>Connection failed:</h2> <p>" . $e->getMessage() . "</p>");
}
?>
