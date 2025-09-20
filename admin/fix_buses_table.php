<?php
require_once __DIR__ . '/includes/config.php';

try {
    // Add status column if it doesn't exist
    $pdo->exec("ALTER TABLE buses ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active'");
    
    // Ensure user_id column exists (for bus owner reference)
    $pdo->exec("ALTER TABLE buses ADD COLUMN IF NOT EXISTS user_id INT NOT NULL AFTER id");
    
    // If owner_id exists, copy its values to user_id
    $pdo->exec("UPDATE buses SET user_id = owner_id WHERE user_id = 0 AND owner_id IS NOT NULL");
    
    // Add foreign key constraint if it doesn't exist
    $pdo->exec("ALTER TABLE buses ADD CONSTRAINT fk_bus_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
    
    echo "<div class='alert alert-success'>Buses table updated successfully!</div>";
    
    // Show current structure
    $stmt = $pdo->query("SHOW CREATE TABLE buses");
    $table = $stmt->fetch();
    echo "<h3>Current Buses Table Structure:</h3>";
    echo "<pre>" . htmlspecialchars($table['Create Table']) . "</pre>";
    
    echo "<p><a href='buses.php' class='btn btn-primary'>Go to Buses Management</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}
?>
