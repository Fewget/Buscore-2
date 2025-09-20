<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

echo "<h2>Checking Buses Table Structure</h2>";

try {
    // Get table structure
    $stmt = $pdo->query("SHOW COLUMNS FROM buses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Buses Table Columns:</h3>";
    echo "<table class='table table-bordered'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasBusNameCol = false;
    $hasCompanyNameCol = false;
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'show_bus_name') $hasBusNameCol = true;
        if ($column['Field'] === 'show_company_name') $hasCompanyNameCol = true;
    }
    
    echo "</table>";
    
    // Add missing columns if needed
    if (!$hasBusNameCol || !$hasCompanyNameCol) {
        echo "<h3>Adding Missing Columns</h3>";
        
        if (!$hasBusNameCol) {
            $pdo->exec("ALTER TABLE buses ADD COLUMN show_bus_name TINYINT(1) DEFAULT 1");
            echo "<p>Added column: show_bus_name</p>";
        }
        
        if (!$hasCompanyNameCol) {
            $pdo->exec("ALTER TABLE buses ADD COLUMN show_company_name TINYINT(1) DEFAULT 1");
            echo "<p>Added column: show_company_name</p>";
        }
        
        echo "<div class='alert alert-success'>Columns have been added successfully!</div>";
    } else {
        echo "<div class='alert alert-success'>All required columns exist in the buses table.</div>";
    }
    
    // Show sample data
    $buses = $pdo->query("SELECT id, registration_number, show_bus_name, show_company_name FROM buses LIMIT 5")->fetchAll();
    
    echo "<h3>Sample Data (First 5 Buses)</h3>";
    echo "<table class='table table-bordered'>";
    echo "<tr><th>ID</th><th>Reg. Number</th><th>Show Bus Name</th><th>Show Company</th></tr>";
    
    foreach ($buses as $bus) {
        echo "<tr>";
        echo "<td>" . $bus['id'] . "</td>";
        echo "<td>" . htmlspecialchars($bus['registration_number']) . "</td>";
        echo "<td>" . ($bus['show_bus_name'] ? '✅ ON' : '❌ OFF') . "</td>";
        echo "<td>" . ($bus['show_company_name'] ? '✅ ON' : '❌ OFF') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "In file: " . $e->getFile() . " on line " . $e->getLine();
    echo "</div>";
}

echo "<p><a href='buses.php' class='btn btn-primary'>Back to Buses</a></p>";
?>
