<?php
session_start();
require_once 'includes/config.php';

header('Content-Type: text/plain');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please log in first");
}

echo "=== Debugging Bus Owner Dashboard ===\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User Role: " . ($_SESSION['role'] ?? 'not set') . "\n\n";

try {
    // Check if buses table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'buses'")->fetchAll();
    if (empty($tables)) {
        die("Error: 'buses' table does not exist");
    }

    // Check buses table structure
    echo "=== Buses Table Structure ===\n";
    $stmt = $pdo->query("DESCRIBE buses");
    echo str_pad("Field", 20) . str_pad("Type", 20) . "Null\tKey\tDefault\n";
    echo str_repeat("-", 60) . "\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo str_pad($row['Field'], 20) . 
             str_pad($row['Type'], 20) . 
             ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\t" . 
             ($row['Key'] ?: '') . "\t" . 
             ($row['Default'] ?? 'NULL') . "\n";
    }

    // Check buses for this owner
    echo "\n=== Buses for Current User ===\n";
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($buses)) {
        echo "No buses found for this user.\n";
        
        // Show all buses for debugging
        echo "\n=== All Buses in Database ===\n";
        $allBuses = $pdo->query("SELECT id, registration_number, user_id, created_at FROM buses")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($allBuses)) {
            echo "No buses found in the database at all.\n";
        } else {
            echo str_pad("ID", 6) . str_pad("Reg Number", 20) . str_pad("User ID", 10) . "Created At\n";
            echo str_repeat("-", 60) . "\n";
            foreach ($allBuses as $bus) {
                echo str_pad($bus['id'], 6) . 
                     str_pad($bus['registration_number'], 20) . 
                     str_pad($bus['user_id'], 10) . 
                     $bus['created_at'] . "\n";
            }
        }
    } else {
        echo "Found " . count($buses) . " buses for this user.\n";
        print_r($buses);
    }
    
} catch (PDOException $e) {
    echo "\nDatabase Error: " . $e->getMessage() . "\n";
    echo "SQL Error Code: " . $e->getCode() . "\n";
}
