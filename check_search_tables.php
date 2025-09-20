<?php
require_once 'includes/config.php';

header('Content-Type: text/plain');

try {
    // Check if required tables exist
    $tables = ['buses', 'ratings', 'users'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        die("Error: The following required tables are missing: " . implode(', ', $missingTables) . "\n");
    }
    
    echo "All required tables exist.\n\n";
    
    // Check table structures
    $tables = [
        'buses' => ['id', 'registration_number', 'route_number', 'status'],
        'ratings' => ['id', 'bus_id', 'driver_rating', 'conductor_rating', 'condition_rating'],
        'users' => ['id', 'username', 'role']
    ];
    
    foreach ($tables as $table => $requiredColumns) {
        echo "Checking table: $table\n";
        echo str_repeat("=", 50) . "\n";
        
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $columns)) {
                echo "[MISSING] Column: $col\n";
            } else {
                echo "[FOUND]   Column: $col\n";
            }
        }
        
        // Count rows
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "Total rows: $count\n\n";
    }
    
    // Test search query
    echo "\nTesting search query...\n";
    echo str_repeat("=", 50) . "\n";
    
    $testQuery = "SELECT 1 FROM buses WHERE status = 'active' LIMIT 1";
    $stmt = $pdo->query($testQuery);
    
    if ($stmt) {
        echo "Basic search query executed successfully.\n";
    } else {
        echo "Error executing search query.\n";
    }
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}

echo "\nCheck complete. If you see any [MISSING] columns, you may need to update your database schema.\n";
?>
