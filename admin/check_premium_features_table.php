<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check admin access
checkAdminAccess();

// Create premium_features table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `premium_features` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bus_id` int(11) NOT NULL,
    `feature_name` varchar(50) NOT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT '1',
    `start_date` datetime NOT NULL,
    `end_date` datetime NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `bus_id` (`bus_id`),
    KEY `feature_name` (`feature_name`),
    KEY `is_active` (`is_active`),
    KEY `end_date` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    $pdo->exec($sql);
    echo "Premium features table verified/created successfully. <a href='buses.php'>Go back to buses</a>";
} catch (PDOException $e) {
    die("Error creating premium_features table: " . $e->getMessage());
}
