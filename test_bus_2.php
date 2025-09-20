<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<pre>";

try {
    // Test database connection
    echo "Testing database connection...\n";
    $pdo->query('SELECT 1');
    echo "✓ Database connection successful\n\n";
    
    // Check if bus with ID 2 exists
    $bus_id = 2;
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->execute([$bus_id]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bus) {
        echo "✓ Bus found in database:\n";
        print_r($bus);
        echo "\n";
        
        // Check if there are any service records
        $stmt = $pdo->prepare("SELECT * FROM service_records WHERE bus_id = ?");
        $stmt->execute([$bus_id]);
        $service_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($service_records) {
            echo "\nService Records:\n";
            print_r($service_records);
        } else {
            echo "\nNo service records found for this bus.\n";
        }
        
        // Check ratings
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(AVG((driver_rating + conductor_rating + condition_rating) / 3), 0) as avg_rating,
                COUNT(id) as rating_count
            FROM ratings 
            WHERE bus_id = ?
        ");
        $stmt->execute([$bus_id]);
        $rating_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\nRating Information:\n";
        print_r($rating_info);
        
    } else {
        echo "✗ No bus found with ID: " . $bus_id . "\n";
        
        // List all available bus IDs for debugging
        $stmt = $pdo->query("SELECT id, registration_number FROM buses LIMIT 10");
        $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($buses) {
            echo "\nAvailable buses (first 10):\n";
            foreach ($buses as $b) {
                echo "- ID: " . $b['id'] . " (" . $b['registration_number'] . ")\n";
            }
        } else {
            echo "\nNo buses found in the database.\n";
        }
    }
    
} catch (PDOException $e) {
    echo "\nDatabase Error: " . $e->getMessage() . "\n";
    
    // Show database error details for debugging
    echo "\nError Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

<h3>PHP Info</h3>
<?php 
if (function_exists('phpinfo')) {
    phpinfo(INFO_VARIABLES | INFO_ENVIRONMENT);
} else {
    echo "phpinfo() is disabled.";
}
?>
