<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'buscore_db';

try {
    // Connect without selecting a database first
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "<p>Database '$dbname' checked/created successfully.</p>";
    
    // Select the database
    $pdo->exec("USE `$dbname`");
    
    // Check if buses table exists (required for foreign key)
    $result = $pdo->query("SHOW TABLES LIKE 'buses'");
    if ($result->rowCount() == 0) {
        die("<p style='color:red;'>Error: The 'buses' table does not exist. Please create it first.</p>");
    }
    
    // Check if service_records table exists
    $result = $pdo->query("SHOW TABLES LIKE 'service_records'");
    if ($result->rowCount() == 0) {
        // Table doesn't exist, create it
        $sql = "
        CREATE TABLE `service_records` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `bus_id` INT NOT NULL,
            `service_type` ENUM('engine_oil', 'brake_pads', 'tire_rotation', 'other') NOT NULL,
            `service_date` DATE NOT NULL,
            `mileage` INT NOT NULL,
            `description` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`bus_id`) REFERENCES `buses`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $pdo->exec($sql);
        echo "<p>Service records table created successfully!</p>";
    } else {
        echo "<p>Service records table already exists.</p>";
    }
    
    // Verify the table structure
    $columns = $pdo->query("SHOW COLUMNS FROM `service_records`")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Service Records Table Structure:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    echo "<p style='color:green;font-weight:bold;'>Setup completed successfully! <a href='../bus-owner/add-service-record.php?bus_id=30'>Try adding a service record now</a></p>";
    
} catch (PDOException $e) {
    die("<h2>Database Error</h2><div style='color:red;'><p>" . $e->getMessage() . "</p><p>Error Code: " . $e->getCode() . "</p></div>");
}
?>
