<?php
require_once '../includes/config.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Columns in users table:\n";
    print_r($columns);
    
    // Get primary key info
    $stmt = $pdo->query("SHOW KEYS FROM users WHERE Key_name = 'PRIMARY'");
    $primaryKey = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nPrimary Key:\n";
    print_r($primaryKey);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
