<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

function checkTable($pdo, $tableName, $requiredColumns = []) {
    echo "<h3>Checking table: $tableName</h3>";
    
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        if ($stmt->rowCount() === 0) {
            echo "<div style='color:red;'>Table '$tableName' does not exist!</div>";
            return false;
        }
        
        echo "<div>Table exists.</div>";
        
        // Check columns if any required columns are specified
        if (!empty($requiredColumns)) {
            $stmt = $pdo->query("DESCRIBE `$tableName`");
            $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $missingColumns = array_diff($requiredColumns, $existingColumns);
            
            if (!empty($missingColumns)) {
                echo "<div style='color:red;'>Missing columns: " . implode(', ', $missingColumns) . "</div>";
                return false;
            }
            
            echo "<div>All required columns exist.</div>";
        }
        
        return true;
        
    } catch (PDOException $e) {
        echo "<div style='color:red;'>Error checking table: " . $e->getMessage() . "</div>";
        return false;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Structure Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Database Structure Check</h1>
    
    <?php
    // Check buses table
    checkTable($pdo, 'buses', [
        'id',
        'registration_number',
        'bus_name',
        'company_name',
        'route_number',
        'route_description',
        'is_approved',
        'created_by',
        'created_at',
        'updated_at'
    ]);
    
    // Check ratings table
    checkTable($pdo, 'ratings', [
        'id',
        'bus_id',
        'user_id',
        'driver_rating',
        'conductor_rating',
        'condition_rating',
        'created_at'
    ]);
    
    // Check users table
    checkTable($pdo, 'users', [
        'id',
        'username',
        'password',
        'email',
        'role',
        'created_at'
    ]);
    
    // Test database connection
    try {
        echo "<h3>Database Connection Test</h3>";
        $pdo->query("SELECT 1");
        echo "<div class='success'>✓ Database connection successful</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
    }
    ?>
    
    <h3>Next Steps</h3>
    <ol>
        <li>If any tables are missing, run the database setup script</li>
        <li>If any columns are missing, run the database update script</li>
        <li>Check the PHP error log for more detailed error messages</li>
    </ol>
    
    <div style="margin-top: 20px;">
        <a href="database/update_buses_table.php" class="button">Run Database Update</a>
    </div>
</body>
</html>
