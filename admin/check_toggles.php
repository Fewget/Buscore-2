<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

// Check if columns exist
$stmt = $pdo->query("SHOW COLUMNS FROM buses LIKE 'show_bus_name'");
$busNameCol = $stmt->fetch();

$stmt = $pdo->query("SHOW COLUMNS FROM buses LIKE 'show_company_name'");
$companyNameCol = $stmt->fetch();

// Add columns if they don't exist
if (!$busNameCol) {
    $pdo->exec("ALTER TABLE buses ADD COLUMN show_bus_name TINYINT(1) DEFAULT 1");
    echo "Added show_bus_name column<br>";
}

if (!$companyNameCol) {
    $pdo->exec("ALTER TABLE buses ADD COLUMN show_company_name TINYINT(1) DEFAULT 1");
    echo "Added show_company_name column<br>";
}

// Check current values for first bus
$bus = $pdo->query("SELECT id, show_bus_name, show_company_name FROM buses LIMIT 1")->fetch();

echo "<h2>Current Toggle States for First Bus (ID: " . ($bus['id'] ?? 'N/A') . ")</h2>";
echo "show_bus_name: " . ($bus['show_bus_name'] ? 'ON' : 'OFF') . "<br>";
echo "show_company_name: " . ($bus['show_company_name'] ? 'ON' : 'OFF') . "<br>";

// Check if update_bus_feature.php exists
$updateFile = __DIR__ . '/update_bus_feature.php';
if (file_exists($updateFile)) {
    $content = file_get_contents($updateFile);
    echo "<h2>update_bus_feature.php Contents</h2>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
} else {
    echo "<div class='alert alert-danger'>update_bus_feature.php is missing!</div>";
}

echo "<p><a href='buses.php'>Back to Buses</a></p>";
?>
