<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // List of tables to clear (in order to respect foreign key constraints)
    $tables = [
        'ratings',
        'buses',
        'users',
        'activity_logs'
    ];
    
    // Disable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // Clear each table
    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`");
        echo "<p>Cleared table: $table</p>";
    }
    
    // Re-enable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    // Commit transaction
    $pdo->commit();
    
    echo "<h2 style='color:green;'>✓ Database cleared successfully</h2>";
    
    // Show table status
    echo "<h3>Table Status:</h3>";
    $result = $pdo->query("SHOW TABLE STATUS");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Table</th><th>Rows</th><th>Auto Increment</th></tr>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
        echo "<td>" . $row['Rows'] . "</td>";
        echo "<td>" . $row['Auto_increment'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h2 style='color:red;'>Error clearing database:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><strong>Warning:</strong> This operation cannot be undone. Make sure you have a backup if needed.</p>
<p><a href='admin/'>Go to Admin Panel</a> | <a href='index.php'>Go to Home</a></p>
