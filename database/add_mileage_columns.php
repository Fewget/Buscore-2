<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // Add current_mileage column if it doesn't exist
    $pdo->exec("ALTER TABLE `buses` 
        ADD COLUMN IF NOT EXISTS `current_mileage` INT NULL DEFAULT NULL AFTER `insurance_expiry_date`,
        ADD COLUMN IF NOT EXISTS `mileage_recorded_date` DATE NULL DEFAULT NULL AFTER `current_mileage`");
    
    echo "Successfully added mileage tracking columns to buses table.\n";
    
} catch (PDOException $e) {
    die("Error adding mileage columns: " . $e->getMessage());
}

// Add an index on mileage_recorded_date for better query performance
try {
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_mileage_recorded_date ON buses(mileage_recorded_date)");
    echo "Successfully created index on mileage_recorded_date.\n";
} catch (PDOException $e) {
    echo "Note: Could not create index on mileage_recorded_date: " . $e->getMessage() . "\n";
}

echo "Mileage tracking setup completed successfully.\n";
?>
