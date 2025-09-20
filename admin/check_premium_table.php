<?php
require_once __DIR__ . '/includes/config.php';

// Set page title
$page_title = 'Check Premium Features Table';

// Include header
require_once __DIR__ . '/includes/header.php';

try {
    // Check if premium_features table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'premium_features'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<div class='alert alert-warning'>The premium_features table does not exist. Creating it now...</div>";
        
        // Create the table
        $sql = "CREATE TABLE `premium_features` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `bus_id` int(11) NOT NULL,
            `feature_name` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `start_date` datetime NOT NULL,
            `end_date` datetime NOT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_bus_id` (`bus_id`),
            CONSTRAINT `fk_premium_bus` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<div class='alert alert-success'>Premium features table created successfully!</div>";
    }
    
    // Show table structure
    $stmt = $pdo->query("DESCRIBE premium_features");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Premium Features Table Structure:</h3>";
    echo "<table class='table table-bordered'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Show any existing premium features
    $stmt = $pdo->query("SELECT * FROM premium_features");
    $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($features) > 0) {
        echo "<h3>Existing Premium Features:</h3>";
        echo "<table class='table table-bordered'>";
        echo "<tr><th>ID</th><th>Bus ID</th><th>Feature</th><th>Start Date</th><th>End Date</th><th>Active</th></tr>";
        
        foreach ($features as $feature) {
            echo "<tr>";
            echo "<td>" . $feature['id'] . "</td>";
            echo "<td>" . $feature['bus_id'] . "</td>";
            echo "<td>" . htmlspecialchars($feature['feature_name']) . "</td>";
            echo "<td>" . $feature['start_date'] . "</td>";
            echo "<td>" . $feature['end_date'] . "</td>";
            echo "<td>" . ($feature['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div class='alert alert-info'>No premium features found in the database.</div>";
    }
    
    echo "<p><a href='premium-features.php' class='btn btn-primary'>Go to Premium Features Management</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}

// Include footer
require_once __DIR__ . '/includes/footer.php';
?>
