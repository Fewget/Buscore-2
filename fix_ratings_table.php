<?php
require_once 'includes/config.php';

try {
    // Check if ratings table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'ratings'")->rowCount() > 0;
    
    if ($tableExists) {
        // Add missing columns if they don't exist
        $alterSql = [
            "ALTER TABLE ratings ADD COLUMN IF NOT EXISTS driver_rating TINYINT NOT NULL AFTER bus_id",
            "ALTER TABLE ratings ADD COLUMN IF NOT EXISTS conductor_rating TINYINT NOT NULL AFTER driver_rating",
            "ALTER TABLE ratings ADD COLUMN IF NOT EXISTS condition_rating TINYINT NOT NULL AFTER conductor_rating",
            "ALTER TABLE ratings ADD COLUMN IF NOT EXISTS comments TEXT NULL AFTER condition_rating",
            "ALTER TABLE ratings ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER comments"
        ];
        
        foreach ($alterSql as $sql) {
            try {
                $pdo->exec($sql);
                echo "Executed: $sql<br>\n";
            } catch (PDOException $e) {
                echo "Error executing '$sql': " . $e->getMessage() . "<br>\n";
            }
        }
        
        echo "<br>Ratings table updated successfully!<br>";
    } else {
        // Create the ratings table if it doesn't exist
        $createTable = "
            CREATE TABLE IF NOT EXISTS ratings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                bus_id INT NOT NULL,
                user_id INT NULL,
                driver_rating TINYINT NOT NULL,
                conductor_rating TINYINT NOT NULL,
                condition_rating TINYINT NOT NULL,
                comments TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $pdo->exec($createTable);
        echo "Created ratings table successfully!<br>";
    }
    
    // Show the current structure
    echo "<h3>Current Ratings Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE ratings");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

echo "<p><a href='add-bus.php'>Go back to Add Bus form</a></p>";
?>
