<?php
require_once '../includes/config.php';

try {
    // First, check the current structure
    echo "<h2>Current Buses Table Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE buses");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
    echo "</pre>";
    
    // Add missing columns one by one with error handling
    $alterStatements = [
        "ALTER TABLE `buses` ADD COLUMN `route_number` VARCHAR(20) NULL AFTER `registration_number`",
        "ALTER TABLE `buses` ADD COLUMN `route_description` TEXT NULL AFTER `route_number`",
        "ALTER TABLE `buses` ADD COLUMN `bus_name` VARCHAR(100) NULL AFTER `registration_number`",
        "ALTER TABLE `buses` ADD COLUMN `company_name` VARCHAR(100) NULL AFTER `bus_name`",
        "ALTER TABLE `buses` ADD COLUMN `is_approved` TINYINT(1) NOT NULL DEFAULT 0 AFTER `route_description`",
        "ALTER TABLE `buses` ADD COLUMN `created_by` INT NULL AFTER `is_approved`",
        "ALTER TABLE `buses` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        "ALTER TABLE `buses` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    echo "<h2>Adding missing columns:</h2>";
    foreach ($alterStatements as $sql) {
        try {
            $pdo->exec($sql);
            echo "<div style='color:green;'>✓ " . htmlspecialchars($sql) . "</div>";
        } catch (PDOException $e) {
            echo "<div style='color:orange;'>⚠ " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<h2>Final Buses Table Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE buses");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
    echo "</pre>";
    
    echo "<div style='margin-top:20px;'><a href='../add-bus.php' class='btn btn-primary'>Try Adding a Bus Again</a></div>";
    
} catch (PDOException $e) {
    die("<div style='color:red;'>Error updating database: " . $e->getMessage() . "</div>");
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
    .btn { display: inline-block; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 10px; }
    .btn:hover { background: #0056b3; }
</style>
