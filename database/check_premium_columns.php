<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: text/plain');

try {
    // Check if premium feature columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM buses LIKE 'premium_features'");
    if (!$stmt->fetch()) {
        echo "Adding premium_features column...\n";
        $pdo->exec("ALTER TABLE buses ADD COLUMN premium_features TEXT NULL COMMENT 'JSON object of enabled premium features'");
        echo "Added premium_features column.\n";
    } else {
        echo "premium_features column already exists.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM buses LIKE 'premium_expires_at'");
    if (!$stmt->fetch()) {
        echo "Adding premium_expires_at column...\n";
        $pdo->exec("ALTER TABLE buses ADD COLUMN premium_expires_at DATETIME NULL DEFAULT NULL");
        echo "Added premium_expires_at column.\n";
    } else {
        echo "premium_expires_at column already exists.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM buses LIKE 'is_premium_active'");
    if (!$stmt->fetch()) {
        echo "Adding is_premium_active column...\n";
        $pdo->exec("ALTER TABLE buses ADD COLUMN is_premium_active TINYINT(1) NOT NULL DEFAULT '0'");
        echo "Added is_premium_active column.\n";
    } else {
        echo "is_premium_active column already exists.\n";
    }

    echo "\nAll premium feature columns are now available in the buses table.\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
