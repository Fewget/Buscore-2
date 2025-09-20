<?php
// Only define constants if they don't already exist
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');  // Default XAMPP username
if (!defined('DB_PASS')) define('DB_PASS', '');      // Default XAMPP password
if (!defined('DB_NAME')) define('DB_NAME', 'buscore_db'); // Use the correct database name

try {
    // Create PDO instance
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Log the error
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Show user-friendly error message
    die("We're experiencing technical difficulties. Please try again later.");
}
