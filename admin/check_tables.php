<?php
require_once __DIR__ . '/includes/config.php';

echo "<h1>Database Tables Check</h1>";

try {
    // Check database connection
    echo "<h2>Database Connection</h2>";
    echo "<p>Connected to database: " . DB_NAME . " on " . DB_HOST . "</p>";

    // List all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tables in Database</h2>";
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table";
            
            // Show table structure
            $columns = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
            echo "<ul>";
            foreach ($columns as $column) {
                echo "<li>{$column['Field']} - {$column['Type']}";
            }
            echo "</ul>";
            
            // Show row count
            $count = $pdo->query("SELECT COUNT(*) as count FROM `$table`")->fetch(PDO::FETCH_ASSOC);
            echo "<p>Rows: {$count['count']}</p>";
            
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No tables found in the database.</p>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color:red;'><h2>Error:</h2><p>" . $e->getMessage() . "</p></div>";
}
?>
