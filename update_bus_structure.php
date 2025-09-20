<?php
require_once 'includes/config.php';

try {
    // First, check if the 'type' column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM `buses` LIKE 'type'");
    $typeExists = $stmt->rowCount() > 0;
    
    // Add 'ownership' column if it doesn't exist
    $pdo->exec("ALTER TABLE `buses` ADD COLUMN IF NOT EXISTS `ownership` ENUM('government', 'private') NULL DEFAULT NULL");
    
    // If 'type' column exists, migrate its values to 'ownership' and then drop it
    if ($typeExists) {
        // Migrate existing type values to ownership
        $pdo->exec("UPDATE `buses` SET `ownership` = `type` WHERE `ownership` IS NULL");
        
        // Check if the column is not already dropped (in case of partial previous runs)
        $stmt = $pdo->query("SHOW COLUMNS FROM `buses` LIKE 'type'");
        if ($stmt->rowCount() > 0) {
            // First, drop any foreign key constraints that might reference this column
            try {
                $pdo->exec("ALTER TABLE `buses` DROP FOREIGN KEY IF EXISTS fk_bus_type");
                // Then drop the column
                $pdo->exec("ALTER TABLE `buses` DROP COLUMN `type`");
            } catch (PDOException $e) {
                echo "Note: Could not drop 'type' column. It might be referenced by other tables. You may need to handle this manually.<br>";
            }
        }
    }
    
    // Ensure all buses have an ownership value
    $pdo->exec("UPDATE `buses` SET `ownership` = 'private' WHERE `ownership` IS NULL");
    
    // Add the 'bus_name' and 'company_name' columns if they don't exist
    $pdo->exec("ALTER TABLE `buses` 
                ADD COLUMN IF NOT EXISTS `bus_name` VARCHAR(100) NULL AFTER `registration_number`,
                ADD COLUMN IF NOT EXISTS `company_name` VARCHAR(100) NULL AFTER `bus_name`");
    
    echo "Database update completed successfully. You can now close this tab.";
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
?>
