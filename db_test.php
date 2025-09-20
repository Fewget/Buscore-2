<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = '127.0.0.1';
$dbname = 'buscore_db';
$username = 'root';
$password = '';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to MySQL server successfully<br>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Database '$dbname' exists<br>";
        
        // Connect to the database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if tables exist
        $tables = ['users', 'buses', 'ratings'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            echo $stmt->rowCount() > 0 
                ? "✓ Table '$table' exists<br>" 
                : "✗ Table '$table' does not exist<br>";
        }
    } else {
        echo "✗ Database '$dbname' does not exist. Please run the setup script.<br>";
    }
    
} catch (PDOException $e) {
    die("<div style='color:red;'>Error: " . $e->getMessage() . "</div>");
}
?>
