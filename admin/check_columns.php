<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

// Get table structure
$stmt = $pdo->query("DESCRIBE buses");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Buses Table Structure</h2>";
echo "<pre>";
print_r($columns);
echo "</pre>";

// Check if we need to add columns
$requiredColumns = ['show_bus_name', 'show_company_name'];
$missingColumns = array_diff($requiredColumns, $columns);

if (!empty($missingColumns)) {
    echo "<h3>Adding missing columns:</h3>";
    foreach ($missingColumns as $column) {
        $sql = "ALTER TABLE buses ADD COLUMN $column TINYINT(1) DEFAULT 1";
        try {
            $pdo->exec($sql);
            echo "<p>Added column: $column</p>";
        } catch (PDOException $e) {
            echo "<p>Error adding column $column: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p>All required columns exist.</p>";
}

echo "<p><a href='buses.php'>Back to Buses</a></p>";
?>
