<?php
require_once __DIR__ . '/includes/config.php';

// Set page title
$page_title = 'Update Premium Features';

// Include header
require_once __DIR__ . '/includes/header.php';

try {
    // Add premium feature columns to buses table if they don't exist
    $pdo->exec("ALTER TABLE buses 
                ADD COLUMN IF NOT EXISTS show_company_name TINYINT(1) DEFAULT 1,
                ADD COLUMN IF NOT EXISTS show_bus_name TINYINT(1) DEFAULT 1");
    
    echo "<div class='alert alert-success'>Premium feature columns added to buses table successfully!</div>";
    
    // Show the updated table structure
    $stmt = $pdo->query("SHOW CREATE TABLE buses");
    $table = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Updated Buses Table Structure:</h3>";
    echo "<pre>" . htmlspecialchars($table['Create Table']) . "</pre>";
    
    echo "<p><a href='buses.php' class='btn btn-primary'>Go to Buses Management</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}

// Include footer
require_once __DIR__ . '/includes/footer.php';
?>
