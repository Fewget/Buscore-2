<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check admin access
checkAdminAccess();

// Function to create the premium_features table
function createPremiumFeaturesTable($pdo) {
    $sql = "CREATE TABLE IF NOT EXISTS `premium_features` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `bus_id` int(11) NOT NULL,
        `feature_name` varchar(50) NOT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT '1',
        `start_date` datetime NOT NULL,
        `end_date` datetime NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `bus_id` (`bus_id`),
        KEY `feature_name` (`feature_name`),
        KEY `is_active` (`is_active`),
        KEY `end_date` (`end_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    return $pdo->exec($sql);
}

// Function to check and add columns to buses table
function checkBusesTable($pdo) {
    $columns = [
        'status' => "ENUM('active', 'inactive', 'maintenance') DEFAULT 'active'",
        'show_company_name' => 'TINYINT(1) DEFAULT 1',
        'show_bus_name' => 'TINYINT(1) DEFAULT 1',
        'type' => "ENUM('private', 'government') DEFAULT 'private'"
    ];
    
    $added = [];
    foreach ($columns as $column => $definition) {
        try {
            $check = $pdo->query("SHOW COLUMNS FROM buses LIKE '$column'");
            if ($check->rowCount() == 0) {
                $pdo->exec("ALTER TABLE buses ADD COLUMN $column $definition");
                $added[] = "Added column: $column";
            }
        } catch (PDOException $e) {
            // Ignore if column already exists
            if (strpos($e->getMessage(), 'duplicate') === false) {
                die("Error checking/adding column $column: " . $e->getMessage());
            }
        }
    }
    
    return $added;
}

// Main execution
echo "<h2>Premium Features Database Check</h2>";

try {
    // Check and create premium_features table
    $result = createPremiumFeaturesTable($pdo);
    echo "<div class='alert alert-success'>✅ Premium features table verified/created successfully.</div>";
    
    // Check and update buses table
    $addedColumns = checkBusesTable($pdo);
    if (!empty($addedColumns)) {
        echo "<div class='alert alert-info'>Updated buses table:<ul>";
        foreach ($addedColumns as $message) {
            echo "<li>$message</li>";
        }
        echo "</ul></div>";
    } else {
        echo "<div class='alert alert-success'>✅ Buses table structure is up to date.</div>";
    }
    
    echo "<p><a href='buses.php' class='btn btn-primary'>Go back to Buses Management</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<style>
.alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
.alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }
.alert-info { color: #31708f; background-color: #d9edf7; border-color: #bce8f1; }
.alert-danger { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
</style>
