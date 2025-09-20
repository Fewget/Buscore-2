<?php
require_once 'includes/config.php';

try {
    echo "<h2>Testing Bus Data</h2>";
    
    // Check if bus with ID 2 exists
    $bus_id = 2;
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->execute([$bus_id]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bus) {
        echo "<h3>Bus Found (ID: $bus_id)</h3>";
        echo "<pre>";
        print_r($bus);
        echo "</pre>";
        
        // Get owner information
        if (!empty($bus['user_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$bus['user_id']]);
            $owner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h3>Owner Information</h3>";
            echo "<pre>";
            print_r($owner);
            echo "</pre>";
        }
        
    } else {
        echo "<div style='color: red;'>No bus found with ID: $bus_id</div>";
        
        // List all buses
        $stmt = $pdo->query("SELECT id, registration_number, model, user_id FROM buses ORDER BY id LIMIT 10");
        $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($buses) {
            echo "<h3>Available Buses (First 10):</h3>";
            echo "<table border='1' cellpadding='5'>
                <tr>
                    <th>ID</th>
                    <th>Registration</th>
                    <th>Model</th>
                    <th>Owner ID</th>
                </tr>";
            
            foreach ($buses as $b) {
                echo "<tr>
                    <td>{$b['id']}</td>
                    <td>{$b['registration_number']}</td>
                    <td>" . htmlspecialchars($b['model'] ?? 'N/A') . "</td>
                    <td>{$b['user_id']}</td>
                </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No buses found in the database.</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
