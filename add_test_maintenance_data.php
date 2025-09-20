<?php
require_once 'includes/config.php';

// This script adds test maintenance data to a bus record
$bus_id = 2; // Change this to your bus ID

$updateQuery = "
    UPDATE buses SET 
        last_inspection_date = '2025-08-15',
        last_inspection_mileage = 12500,
        last_oil_change_date = '2025-08-20',
        last_oil_change_mileage = 12300,
        last_brake_liner_change_date = '2025-07-10',
        last_brake_liner_mileage = 11500,
        last_tyre_change_date = '2025-06-01',
        last_tyre_change_mileage = 10500,
        last_battery_change_date = '2025-01-15',
        last_battery_change_mileage = 8500,
        insurance_expiry_date = '2025-12-31'
    WHERE id = ?";

try {
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute([$bus_id]);
    
    if ($stmt->rowCount() > 0) {
        echo "Successfully updated maintenance data for bus ID: " . $bus_id . "<br>";
        echo "<a href='bus_details.php?id=" . $bus_id . "'>View Bus Details</a>";
    } else {
        echo "No bus found with ID: " . $bus_id;
    }
} catch (PDOException $e) {
    die("Error updating bus data: " . $e->getMessage());
}
?>
