<?php
require_once __DIR__ . '/includes/config.php';

// Set page title
$page_title = 'Check Buses Table';

// Include header
require_once __DIR__ . '/includes/header.php';

try {
    // Check buses table structure
    $stmt = $pdo->query("DESCRIBE buses");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Buses Table Columns:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . htmlspecialchars($column) . "</li>";
    }
    echo "</ul>";
    
    // Show sample data
    echo "<h3>Sample Data (first 5 records):</h3>";
    $stmt = $pdo->query("SELECT * FROM buses LIMIT 5");
    $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($sampleData) > 0) {
        echo "<table class='table table-bordered'>";
        // Table header
        echo "<tr>";
        foreach (array_keys($sampleData[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>";
        
        // Table rows
        foreach ($sampleData as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data found in the buses table.</p>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}

// Include footer
require_once __DIR__ . '/includes/footer.php';
?>
