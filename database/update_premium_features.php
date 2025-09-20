<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Add premium features columns if they don't exist
    $columns = [
        'premium_features' => "TEXT NULL COMMENT 'JSON object of enabled premium features'",
        'premium_expires_at' => "DATETIME NULL DEFAULT NULL",
        'is_premium_active' => "TINYINT(1) NOT NULL DEFAULT '0'"
    ];
    
    // Check which columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM buses");
    $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    // Add missing columns
    foreach ($columns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            $pdo->exec("ALTER TABLE `buses` ADD COLUMN `$column` $definition");
            echo "Added column: $column\n";
        } else {
            echo "Column already exists: $column\n";
        }
    }
    
    // Create premium packages table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `premium_packages` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `description` text,
        `features` text NOT NULL COMMENT 'JSON array of features',
        `price` decimal(10,2) NOT NULL,
        `duration_days` int(11) NOT NULL COMMENT '0 for lifetime',
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "Ensured premium_packages table exists\n";
    
    // Commit the transaction
    $pdo->commit();
    
    echo "Database update completed successfully!\n";
    
} catch (Exception $e) {
    // Rollback the transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
