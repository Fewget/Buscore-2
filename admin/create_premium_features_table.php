<?php
require_once __DIR__ . '/includes/config.php';

// Set page title
$page_title = 'Create Premium Features Table';

// Include header
require_once __DIR__ . '/includes/header.php';

try {
    // Create premium_features table
    $sql = "CREATE TABLE IF NOT EXISTS `premium_features` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `bus_id` INT(11) NOT NULL,
        `feature_name` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `start_date` DATETIME NOT NULL,
        `end_date` DATETIME NOT NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_bus_id` (`bus_id`),
        CONSTRAINT `fk_premium_bus` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    
    echo "<div class='alert alert-success'>Premium features table created successfully!</div>";
    
    // Show the table structure
    $stmt = $pdo->query("SHOW CREATE TABLE premium_features");
    $table = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Table Structure:</h3>";
    echo "<pre>" . htmlspecialchars($table['Create Table']) . "</pre>";
    
    echo "<p><a href='premium-features.php' class='btn btn-primary'>Go to Premium Features Management</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}

// Include footer
require_once __DIR__ . '/includes/footer.php';
?>
