<?php
// Simple test version of bus details page
require_once 'includes/config.php';

// Get bus ID from URL
$bus_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($bus_id <= 0) {
    die("Invalid bus ID");
}

// Simple query to get bus data
try {
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->execute([$bus_id]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        die("Bus not found");
    }
    
    // Simple output
    echo "<h1>Bus Details (Simple Version)</h1>";
    echo "<h2>Bus #" . htmlspecialchars($bus['registration_number']) . "</h2>";
    
    echo "<h3>Basic Information</h3>";
    echo "<ul>";
    echo "<li><strong>Bus Name:</strong> " . htmlspecialchars($bus['bus_name']) . "</li>";
    echo "<li><strong>Route Number:</strong> " . htmlspecialchars($bus['route_number']) . "</li>";
    echo "<li><strong>Route:</strong> " . htmlspecialchars($bus['route_description']) . "</li>";
    echo "<li><strong>Company:</strong> " . htmlspecialchars($bus['company_name']) . "</li>";
    echo "</ul>";
    
    echo "<h3>Maintenance</h3>";
    echo "<ul>";
    echo "<li><strong>Last Inspection:</strong> " . $bus['last_inspection_date'] . " (" . number_format($bus['last_inspection_mileage']) . " km)</li>";
    echo "<li><strong>Last Oil Change:</strong> " . $bus['last_oil_change_date'] . " (" . number_format($bus['last_oil_change_mileage']) . " km)</li>";
    echo "<li><strong>Last Brake Service:</strong> " . $bus['last_brake_liner_change_date'] . " (" . number_format($bus['last_brake_liner_mileage']) . " km)</li>";
    echo "<li><strong>Insurance Expiry:</strong> " . $bus['insurance_expiry_date'] . "</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
