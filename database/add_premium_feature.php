<?php
require_once '../includes/config.php';

try {
    // Check if the column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM buses LIKE 'is_premium'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add the is_premium column
        $pdo->exec("ALTER TABLE buses ADD COLUMN is_premium TINYINT(1) NOT NULL DEFAULT 0 AFTER company_name");
        echo "Successfully added 'is_premium' column to 'buses' table.\n";
    } else {
        echo "'is_premium' column already exists in 'buses' table.\n";
    }
    
    // Create a stored procedure to toggle premium status
    $pdo->exec("
    DROP PROCEDURE IF EXISTS toggle_bus_premium;
    CREATE PROCEDURE toggle_bus_premium(IN bus_id INT)
    BEGIN
        UPDATE buses 
        SET is_premium = NOT is_premium 
        WHERE id = bus_id;
    END;
    ");
    echo "Stored procedure 'toggle_bus_premium' created/updated.\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

echo "Premium feature setup completed. You can now access the premium toggle in the bus owner dashboard.";
?>
