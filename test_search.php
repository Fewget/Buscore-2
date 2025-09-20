<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Test search term
$searchTerm = 'NC-7746';

try {
    echo "<h2>Searching for: " . htmlspecialchars($searchTerm) . "</h2>";
    
    // Test database connection
    echo "<h3>Testing database connection...</h3>";
    $pdo->query('SELECT 1');
    echo "<p style='color:green;'>✓ Database connection successful</p>";
    
    // Check if buses table exists
    echo "<h3>Checking buses table...</h3>";
    $tables = $pdo->query("SHOW TABLES LIKE 'buses'")->fetchAll();
    if (count($tables) === 0) {
        die("<p style='color:red;'>✗ Buses table does not exist</p>");
    }
    echo "<p style='color:green;'>✓ Buses table exists</p>";
    
    // Check table structure
    echo "<h3>Table structure:</h3>";
    $columns = $pdo->query("DESCRIBE buses")->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>" . print_r($columns, true) . "</pre>";
    
    // Try direct search
    echo "<h3>Direct search results:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE registration_number LIKE ?");
    $stmt->execute(["%$searchTerm%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) > 0) {
        echo "<p style='color:green;'>✓ Found " . count($results) . " result(s):</p>";
        echo "<pre>" . print_r($results, true) . "</pre>";
    } else {
        echo "<p style='color:orange;'>No results found. Testing with different cases...</p>";
        
        // Try different cases
        $terms = [
            strtoupper($searchTerm),
            strtolower($searchTerm),
            ucfirst(strtolower($searchTerm)),
            substr($searchTerm, 0, 2) . strtoupper(substr($searchTerm, 2)),
            str_replace('-', '', $searchTerm),
            str_replace('-', ' ', $searchTerm)
        ];
        
        $found = false;
        foreach ($terms as $term) {
            $stmt = $pdo->prepare("SELECT * FROM buses WHERE registration_number LIKE ?");
            $stmt->execute(["%$term%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) > 0) {
                echo "<p style='color:green;'>✓ Found " . count($results) . " result(s) for '$term':</p>";
                echo "<pre>" . print_r($results, true) . "</pre>";
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            // Show sample data to help debug
            echo "<h3>Sample bus data (first 5 records):</h3>";
            $sample = $pdo->query("SELECT id, registration_number FROM buses LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>" . print_r($sample, true) . "</pre>";
            
            echo "<h3>Searching for any bus with '7746' in registration number:</h3>";
            $stmt = $pdo->query("SELECT * FROM buses WHERE registration_number LIKE '%7746%'");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>" . print_r($results, true) . "</pre>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
