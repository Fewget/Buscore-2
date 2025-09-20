<?php
require_once '../includes/config.php';

try {
    // Add premium_expiry column if it doesn't exist
    $pdo->exec("ALTER TABLE buses 
                ADD COLUMN IF NOT EXISTS premium_expiry DATETIME DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS premium_status ENUM('active', 'expired', 'pending') DEFAULT 'pending',
                ADD COLUMN IF NOT EXISTS premium_requested_at DATETIME DEFAULT NULL");
    
    // Create admin log table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS premium_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bus_id INT NOT NULL,
        requested_by INT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        expiry_date DATETIME DEFAULT NULL,
        notes TEXT,
        processed_by INT DEFAULT NULL,
        processed_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
        FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    echo "Database updated successfully. Premium features are now ready for admin approval.";
    
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
