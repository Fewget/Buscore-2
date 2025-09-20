<?php
require_once 'includes/config.php';

header('Content-Type: text/plain');

try {
    // Check if ratings table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'ratings'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create ratings table if it doesn't exist
        $createTable = "
            CREATE TABLE ratings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                bus_id INT NOT NULL,
                user_id INT NULL,
                driver_rating TINYINT NOT NULL,
                conductor_rating TINYINT NOT NULL,
                condition_rating TINYINT NOT NULL,
                comments TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $pdo->exec($createTable);
        echo "Created ratings table.\n";
    } else {
        // Add missing columns if they don't exist
        $alterSql = [
            "ALTER TABLE ratings ADD COLUMN IF NOT EXISTS driver_rating TINYINT NOT NULL DEFAULT 0 AFTER user_id",
            "ALTER TABLE ratings ADD COLUMN IF NOT EXISTS conductor_rating TINYINT NOT NULL DEFAULT 0 AFTER driver_rating",
            "ALTER TABLE ratings ADD COLUMN IF NOT EXISTS condition_rating TINYINT NOT NULL DEFAULT 0 AFTER conductor_rating",
            "ALTER TABLE ratings ADD COLUMN IF NOT EXISTS comments TEXT NULL AFTER condition_rating"
        ];
        
        foreach ($alterSql as $sql) {
            try {
                $pdo->exec($sql);
                echo "Executed: $sql\n";
            } catch (PDOException $e) {
                echo "Error executing '$sql': " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Show current table structure
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
