<?php
require_once '../includes/config.php';

try {
    // Add new columns to buses table if they don't exist
    $pdo->exec("ALTER TABLE `buses` 
        ADD COLUMN IF NOT EXISTS `owner_name` VARCHAR(100) NULL AFTER `route_description`,
        ADD COLUMN IF NOT EXISTS `owner_contact` VARCHAR(20) NULL AFTER `owner_name`,
        ADD COLUMN IF NOT EXISTS `owner_email` VARCHAR(100) NULL AFTER `owner_contact`,
        ADD COLUMN IF NOT EXISTS `is_approved` TINYINT(1) NOT NULL DEFAULT 0 AFTER `owner_email`,
        ADD COLUMN IF NOT EXISTS `created_by` INT NULL AFTER `is_approved`,
        ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        
    echo "Database updated successfully. <a href='../index.php'>Go to homepage</a>";
    
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
?>
