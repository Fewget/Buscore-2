<?php
require_once '../includes/config.php';

try {
    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop the buses table if it exists (it might have a foreign key to bus_owners)
    $pdo->exec("DROP TABLE IF EXISTS buses");
    
    // Drop the bus_owners table
    $pdo->exec("DROP TABLE IF EXISTS bus_owners");
    
    // Recreate bus_owners table with correct structure
    $sql = "
    CREATE TABLE IF NOT EXISTS `bus_owners` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `company_name` varchar(255) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sql);
    
    // Recreate buses table with correct foreign key
    $sql = "
    CREATE TABLE IF NOT EXISTS `buses` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `registration_number` varchar(50) NOT NULL,
      `bus_name` varchar(100) NOT NULL,
      `route_number` varchar(50) NOT NULL,
      `route_description` text,
      `company_name` varchar(255) NOT NULL,
      `user_id` int(11) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `registration_number` (`registration_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sql);
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Tables have been recreated successfully.\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
