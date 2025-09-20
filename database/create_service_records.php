<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // Check if table exists
    $result = $pdo->query("SHOW TABLES LIKE 'service_records'");
    
    if ($result->rowCount() == 0) {
        // Table doesn't exist, create it
        $sql = "
        CREATE TABLE service_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bus_id INT NOT NULL,
            service_type ENUM('engine_oil', 'brake_pads', 'tire_rotation', 'other') NOT NULL,
            service_date DATE NOT NULL,
            mileage INT NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $pdo->exec($sql);
        echo "Service records table created successfully!<br>";
    } else {
        echo "Service records table already exists.<br>";
    }
    
    // Check if the foreign key constraint exists
    $result = $pdo->query("
        SELECT COUNT(*) as count 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE CONSTRAINT_SCHEMA = 'buscore_db' 
        AND TABLE_NAME = 'service_records' 
        AND CONSTRAINT_NAME = 'service_records_ibfk_1'
    ")->fetch();
    
    if ($result['count'] == 0) {
        // Add foreign key constraint
        $pdo->exec("
            ALTER TABLE service_records
            ADD CONSTRAINT fk_bus_id
            FOREIGN KEY (bus_id) REFERENCES buses(id)
            ON DELETE CASCADE
        ") or die(print_r($pdo->errorInfo(), true));
        echo "Foreign key constraint added successfully!<br>";
    } else {
        echo "Foreign key constraint already exists.<br>";
    }
    
    echo "<p>Service records table is ready to use. <a href='../bus-owner/add-service-record.php?bus_id=30'>Go back to add service record</a></p>";
    
} catch (PDOException $e) {
    die("<h2>Database Error</h2><p>" . $e->getMessage() . "</p>");
}
?>
