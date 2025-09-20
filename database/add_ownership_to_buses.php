<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Check if ownership column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM `buses` LIKE 'ownership'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add ownership column with default value 'private' to maintain backward compatibility
        $pdo->exec("ALTER TABLE `buses` ADD COLUMN `ownership` ENUM('government', 'private') NOT NULL DEFAULT 'private' AFTER `type`");
        
        echo "Successfully added 'ownership' column to 'buses' table.\n";
    } else {
        echo "'ownership' column already exists in 'buses' table.\n";
    }
    
    // Update existing buses to have a default ownership
    $pdo->exec("UPDATE `buses` SET `ownership` = 'private' WHERE `ownership` = '' OR `ownership` IS NULL");
    
    echo "Database update completed successfully.\n";
    
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage() . "\n");
}

// Add a button to go back to the previous page
echo '<br><a href="javascript:history.back()" class="btn btn-primary">Go Back</a>';
?>
