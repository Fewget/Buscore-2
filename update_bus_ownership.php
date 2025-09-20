<?php
require_once 'includes/config.php';

try {
    // Add ownership column if it doesn't exist
    $pdo->exec("ALTER TABLE `buses` ADD COLUMN IF NOT EXISTS `ownership` ENUM('government', 'private') NOT NULL DEFAULT 'private' AFTER `type`");
    
    // Update existing buses to have a default ownership
    $pdo->exec("UPDATE `buses` SET `ownership` = 'private' WHERE `ownership` = '' OR `ownership` IS NULL");
    
    echo "Database update completed successfully. You can now close this tab.";
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
?>
