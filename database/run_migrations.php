<?php
require_once '../includes/config.php';

try {
    // Read the SQL file
    $sql = file_get_contents('create_bus_owners_table.sql');
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "Migration completed successfully. Table 'bus_owners' has been created or already exists.";
} catch (PDOException $e) {
    die("Error running migration: " . $e->getMessage());
}
