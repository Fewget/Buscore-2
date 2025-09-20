<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Add updated_at column if it doesn't exist
    $sql = "ALTER TABLE `buses` 
            ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    
    $pdo->exec($sql);
    
    echo "Successfully added updated_at column to buses table. <a href='../index.php'>Go to homepage</a>";
    
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
?>
