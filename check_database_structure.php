<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

function checkTableExists($pdo, $tableName) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
    return $stmt->rowCount() > 0;
}

function getTableColumns($pdo, $tableName) {
    $stmt = $pdo->query("DESCRIBE `$tableName`");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

try {
    echo "<h2>Database Structure Check</h2>";
    
    // Check if tables exist
    $tables = ['buses', 'ratings', 'users'];
    
    foreach ($tables as $table) {
        if (checkTableExists($pdo, $table)) {
            echo "<h3>Table: $table</h3>";
            echo "<pre>Columns: " . implode(", ", getTableColumns($pdo, $table)) . "</pre>";
        } else {
            echo "<div style='color: red;'>Table '$table' does not exist!</div>";
        }
    }
    
    // Check for foreign keys
    echo "<h3>Foreign Key Checks</h3>";
    $stmt = $pdo->query("SELECT * FROM information_schema.TABLE_CONSTRAINTS 
                         WHERE CONSTRAINT_SCHEMA = DATABASE() 
                         AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($fks)) {
        echo "<div>No foreign keys found.</div>";
    } else {
        echo "<pre>" . print_r($fks, true) . "</pre>";
    }
    
    // Check if the current user has insert permissions
    echo "<h3>User Permissions</h3>";
    $stmt = $pdo->query("SELECT CURRENT_USER(), DATABASE()");
    $userInfo = $stmt->fetch(PDO::FETCH_NUM);
    echo "<div>Current User: {$userInfo[0]}</div>";
    echo "<div>Current Database: {$userInfo[1]}</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>Database Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Add link to update database
if (file_exists('database/update_buses_table.php')) {
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='database/update_buses_table.php' class='btn btn-primary'>Update Database Structure</a>";
    echo "</div>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    .btn { display: inline-block; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px 0; }
    .btn:hover { background: #0056b3; }
</style>
