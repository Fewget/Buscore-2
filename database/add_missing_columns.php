<?php
require_once '../includes/config.php';

try {
    // Add missing columns to buses table
    $pdo->exec("ALTER TABLE `buses` 
        ADD COLUMN IF NOT EXISTS `route_description` TEXT NULL AFTER `route_number`,
        ADD COLUMN IF NOT EXISTS `bus_name` VARCHAR(100) NULL AFTER `registration_number`,
        ADD COLUMN IF NOT EXISTS `company_name` VARCHAR(100) NULL AFTER `bus_name`,
        ADD COLUMN IF NOT EXISTS `is_approved` TINYINT(1) NOT NULL DEFAULT 0 AFTER `route_description`,
        ADD COLUMN IF NOT EXISTS `created_by` INT NULL AFTER `is_approved`,
        ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    
    // Add missing columns to ratings table if they don't exist
    $pdo->exec("ALTER TABLE `ratings`
        ADD COLUMN IF NOT EXISTS `driver_rating` TINYINT(1) NOT NULL AFTER `user_id`,
        ADD COLUMN IF NOT EXISTS `conductor_rating` TINYINT(1) NOT NULL AFTER `driver_rating`,
        ADD COLUMN IF NOT EXISTS `condition_rating` TINYINT(1) NOT NULL AFTER `conductor_rating`,
        ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `condition_rating`");
    
    echo "Database updated successfully. <a href='../index.php'>Go to homepage</a>";
    
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
?>
