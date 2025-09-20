<?php
require_once 'includes/config.php';

// Check if bus ID is provided
$bus_id = isset($_GET['id']) ? (int)$_GET['id'] : 2; // Default to bus ID 2 if not specified

try {
    // Test database connection
    echo "<h2>Testing Database Connection</h2>";
    echo "<p>Database: " . DB_NAME . "</p>";
    
    // Get bus details
    $stmt = $pdo->prepare("SELECT id, registration_number, status FROM buses WHERE id = ?");
    $stmt->execute([$bus_id]);
    $bus = $stmt->fetch();
    
    if ($bus) {
        echo "<h2>Bus Found</h2>";
        echo "<pre>" . print_r($bus, true) . "</pre>";
        
        // Check if status is active
        if ($bus['status'] !== 'active') {
            echo "<p>Note: Bus status is '{$bus['status']}'. Only 'active' buses are shown on the details page.</p>";
        }
    } else {
        echo "<h2>Bus Not Found</h2>";
        echo "<p>No bus found with ID: {$bus_id}</p>";
        
        // List available buses for reference
        $stmt = $pdo->query("SELECT id, registration_number, status FROM buses LIMIT 10");
        $buses = $stmt->fetchAll();
        
        if (count($buses) > 0) {
            echo "<h3>Available Buses (first 10):</h3>";
            echo "<pre>" . print_r($buses, true) . "</pre>";
        } else {
            echo "<p>No buses found in the database.</p>";
        }
    }
    
    // Check if the ratings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'ratings'");
    if ($stmt->rowCount() === 0) {
        echo "<h2>Missing Table</h2>";
        echo "<p>The 'ratings' table does not exist in the database.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Database Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Show PHP info for debugging
echo "<h2>PHP Info</h2>
<p>PHP Version: " . phpversion() . "</p>
<p>PDO Drivers: " . print_r(PDO::getAvailableDrivers(), true) . "</p>";
?>
