<?php
require_once __DIR__ . '/includes/config.php';

// Get the table structure
echo "Checking buses table structure...\n";
$stmt = $pdo->query("SHOW CREATE TABLE buses");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Current table structure:\n";
echo $result['Create Table'] . "\n\n";

// Check if user_id is nullable
echo "Checking user_id column...\n";
$stmt = $pdo->query("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'buses' AND COLUMN_NAME = 'user_id'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "user_id is_nullable: " . ($result['IS_NULLABLE'] ?? 'column not found') . "\n";

// Try to modify the table if needed
if (($result['IS_NULLABLE'] ?? '') === 'NO') {
    echo "\nAttempting to modify user_id to be nullable...\n";
    try {
        $pdo->exec("ALTER TABLE buses MODIFY COLUMN user_id INT NULL");
        echo "Successfully modified user_id to be nullable.\n";
    } catch (PDOException $e) {
        echo "Error modifying column: " . $e->getMessage() . "\n";
    }
}

echo "\nCurrent buses table data (first 5 rows):\n";
$stmt = $pdo->query("SELECT id, registration_number, user_id, created_at FROM buses LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
