<?php
require_once 'includes/config.php';

try {
    // Check if ratings table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'ratings'")->rowCount() > 0;
    
    if ($tableExists) {
        // Disable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        
        // Drop existing ratings table if it exists
        echo "Dropping existing ratings table...\n";
        $pdo->exec("DROP TABLE IF EXISTS ratings");
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    }
    
    // Create new ratings table with correct schema
    echo "Creating new ratings table...\n";
    $createTable = "
        CREATE TABLE ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bus_id INT NOT NULL,
            user_id INT NULL,
            driver_rating TINYINT NOT NULL,
            conductor_rating TINYINT NOT NULL,
            bus_condition_rating TINYINT NOT NULL,
            comment TEXT NULL,
            user_ip VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($createTable);
    echo "Ratings table created successfully!\n";
    
    // Show the current structure
    echo "\nCurrent ratings table structure:\n";
    $stmt = $pdo->query("DESCRIBE ratings");
    echo str_pad("Field", 20) . str_pad("Type", 30) . "Null\tKey\tDefault\n";
    echo str_repeat("-", 60) . "\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo str_pad($row['Field'], 20) . 
             str_pad($row['Type'], 30) . 
             $row['Null'] . "\t" . 
             ($row['Key'] ?: '') . "\t" . 
             ($row['Default'] ?? 'NULL') . "\n";
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

echo "\nDone. Please try submitting the form again.\n";
?>
