<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

echo "<h2>Fixing Toggle Switches</h2>";

try {
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Add columns if they don't exist
    $pdo->exec("ALTER TABLE buses ADD COLUMN IF NOT EXISTS show_bus_name TINYINT(1) DEFAULT 1");
    $pdo->exec("ALTER TABLE buses ADD COLUMN IF NOT EXISTS show_company_name TINYINT(1) DEFAULT 1");
    
    // 2. Update existing NULL values to default (1)
    $pdo->exec("UPDATE buses SET show_bus_name = 1 WHERE show_bus_name IS NULL OR show_bus_name = ''");
    $pdo->exec("UPDATE buses SET show_company_name = 1 WHERE show_company_name IS NULL OR show_company_name = ''");
    
    // 3. Verify the changes
    $stmt = $pdo->query("SELECT COUNT(*) as total, 
                         SUM(COALESCE(show_bus_name, 1)) as bus_name_on, 
                         SUM(COALESCE(show_company_name, 1)) as company_name_on 
                         FROM buses");
    $stats = $stmt->fetch();
    
    echo "<div class='alert alert-success'>";
    echo "<strong>Success!</strong> Database has been updated.<br>";
    echo "Total buses: " . $stats['total'] . "<br>";
    echo "Buses with show_bus_name ON: " . $stats['bus_name_on'] . "<br>";
    echo "Buses with show_company_name ON: " . $stats['company_name_on'] . "<br>";
    echo "</div>";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<div class='alert alert-danger'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "In file: " . $e->getFile() . " on line " . $e->getLine();
    echo "</div>";
}

// Show current toggle states for first 5 buses
$buses = $pdo->query("SELECT id, registration_number, show_bus_name, show_company_name FROM buses LIMIT 5")->fetchAll();

echo "<h3>Current Toggle States (First 5 Buses)</h3>";
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

echo "<p><a href='buses.php' class='btn btn-primary'>Back to Buses</a></p>";
?>
