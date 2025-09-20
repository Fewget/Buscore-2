<?php
require_once '../includes/config.php';

try {
    // Drop the existing foreign key constraint if it exists
    $pdo->exec("ALTER TABLE bus_owners DROP FOREIGN KEY IF EXISTS fk_bus_owner_user");
    
    // Drop the existing table if it exists
    $pdo->exec("DROP TABLE IF EXISTS bus_owners");
    
    // Recreate the table with the correct column name
    $sql = "
    CREATE TABLE IF NOT EXISTS `bus_owners` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `owner_id` int(11) NOT NULL,
      `company_name` varchar(255) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `owner_id` (`owner_id`),
      CONSTRAINT `fk_bus_owner_user` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($sql);
    
    echo "Table 'bus_owners' has been recreated with the correct structure.\n";
    
    // Verify the structure
    $stmt = $pdo->query("SHOW CREATE TABLE bus_owners");
    $table = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Current table structure:\n";
    echo $table['Create Table'] . "\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
