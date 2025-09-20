<?php
require_once 'includes/config.php';

try {
    // Make bus_name column nullable
    $sql = "ALTER TABLE `buses` MODIFY COLUMN `bus_name` VARCHAR(255) NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo "Success: The bus_name column is now nullable.\n";
    
    // Verify the change
    $stmt = $pdo->query("SHOW COLUMNS FROM `buses` WHERE Field = 'bus_name'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "Current bus_name column definition:\n";
        echo "Type: " . $column['Type'] . "\n";
        echo "Null: " . $column['Null'] . "\n";
        echo "Key: " . $column['Key'] . "\n";
        echo "Default: " . ($column['Default'] ?? 'NULL') . "\n";
        echo "Extra: " . $column['Extra'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Show the current table structure for debugging
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE buses");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            echo "\nCurrent table structure:\n";
            echo $result['Create Table'] . "\n";
        }
    } catch (PDOException $e2) {
        echo "\nCould not retrieve table structure: " . $e2->getMessage() . "\n";
    }
}
?>
