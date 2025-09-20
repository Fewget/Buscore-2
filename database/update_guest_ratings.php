<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/update_for_guest_ratings.sql');
    
    // Split into individual queries
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
            echo "Executed: " . substr($query, 0, 100) . "...<br>\n";
        }
    }
    
    echo "Database updated successfully for guest ratings!";
    
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
?>
