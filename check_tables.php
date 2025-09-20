<?php
require_once 'includes/config.php';

header('Content-Type: text/plain');

try {
    // Check connection
    $pdo->query('SELECT 1');
    echo "Database connection successful\n\n";
    
    // Check if tables exist
    $tables = ['buses', 'ratings', 'users'];
    
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
        echo "Table '$table' exists: " . ($result ? 'Yes' : 'No') . "\n";
        
        if ($result) {
            // Show table structure
            echo "Structure of '$table':\n";
            $stmt = $pdo->query("DESCRIBE $table");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "- {$row['Field']} ({$row['Type']})\n";
            }
            echo "\n";
        }
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

echo "\nCheck complete. If you see any missing tables, please run the database setup script.";
?>
