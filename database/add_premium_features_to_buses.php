<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $pdo->exec("ALTER TABLE `buses` 
        ADD COLUMN `premium_features` TEXT NULL COMMENT 'JSON object of enabled premium features',
        ADD COLUMN `premium_expires_at` DATETIME NULL DEFAULT NULL AFTER `premium_features`,
        ADD COLUMN `is_premium_active` TINYINT(1) NOT NULL DEFAULT '0' AFTER `premium_expires_at`");
    
    echo "Successfully added premium feature columns to buses table\n";
    
    // Update existing buses with empty premium features
    $pdo->exec("UPDATE `buses` SET `premium_features` = '{}' WHERE `premium_features` IS NULL");
    
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Columns already exist. Skipping.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
