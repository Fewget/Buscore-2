<?php
// Database configuration
require_once 'includes/config.php';

try {
    // Add company_name column to users table if it doesn't exist
    $sql = "ALTER TABLE `users` 
            ADD COLUMN IF NOT EXISTS `company_name` VARCHAR(255) NULL DEFAULT NULL AFTER `full_name`,
            ADD INDEX IF NOT EXISTS `idx_company_name` (`company_name`)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo "Database updated successfully. company_name column added to users table.\n";
    
    // Verify the column was added
    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'company_name'");
    if ($stmt->rowCount() > 0) {
        echo "Verification: company_name column exists in users table.\n";
    } else {
        echo "Warning: Failed to verify company_name column. Please check the database manually.\n";
    }
    
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage() . "\n");
}

echo "Database update complete. You can now register users with company names.\n";
?>
