<?php
require_once '../includes/config.php';

try {
    // Add maintenance-related columns to buses table if they don't exist
    $alterQueries = [
        "ALTER TABLE `buses` 
         ADD COLUMN IF NOT EXISTS `last_inspection_date` DATE NULL AFTER `ownership`,
         ADD COLUMN IF NOT EXISTS `last_inspection_mileage` INT NULL AFTER `last_inspection_date`,
         ADD COLUMN IF NOT EXISTS `last_oil_change_date` DATE NULL AFTER `last_inspection_mileage`,
         ADD COLUMN IF NOT EXISTS `last_oil_change_mileage` INT NULL AFTER `last_oil_change_date`,
         ADD COLUMN IF NOT EXISTS `last_brake_liner_change_date` DATE NULL AFTER `last_oil_change_mileage`,
         ADD COLUMN IF NOT EXISTS `last_brake_liner_mileage` INT NULL AFTER `last_brake_liner_change_date`,
         ADD COLUMN IF NOT EXISTS `last_tyre_change_date` DATE NULL AFTER `last_brake_liner_mileage`,
         ADD COLUMN IF NOT EXISTS `last_tyre_change_mileage` INT NULL AFTER `last_tyre_change_date`,
         ADD COLUMN IF NOT EXISTS `last_battery_change_date` DATE NULL AFTER `last_tyre_change_mileage`,
         ADD COLUMN IF NOT EXISTS `last_battery_change_mileage` INT NULL AFTER `last_battery_change_date`,
         ADD COLUMN IF NOT EXISTS `insurance_expiry_date` DATE NULL AFTER `last_battery_change_mileage`"
    ];

    foreach ($alterQueries as $query) {
        $pdo->exec($query);
    }

    echo "Maintenance fields added successfully to buses table.";
    
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
?>

<a href="javascript:history.back()" class="btn btn-primary mt-3">Go Back</a>
