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
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    
    // Create tables
    $tables = [
        "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `full_name` VARCHAR(100),
            `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
            `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS `buses` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `registration_number` VARCHAR(20) NOT NULL UNIQUE,
            `type` ENUM('government', 'private') NOT NULL,
            `route_number` VARCHAR(20),
            `route_description` TEXT,
            `user_id` INT NULL,
            `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_registration` (`registration_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS `ratings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `bus_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `driver_rating` TINYINT NOT NULL CHECK (driver_rating BETWEEN 1 AND 5),
            `conductor_rating` TINYINT NOT NULL CHECK (conductor_rating BETWEEN 1 AND 5),
            `condition_rating` TINYINT NOT NULL CHECK (condition_rating BETWEEN 1 AND 5),
            `comment` TEXT,
            `is_approved` BOOLEAN NOT NULL DEFAULT FALSE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`bus_id`) REFERENCES `buses`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_bus_id` (`bus_id`),
            INDEX `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS `reviews` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `bus_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `content` TEXT NOT NULL,
            `rating_id` INT,
            `is_approved` BOOLEAN NOT NULL DEFAULT FALSE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`bus_id`) REFERENCES `buses`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`rating_id`) REFERENCES `ratings`(`id`) ON DELETE SET NULL,
            INDEX `idx_bus_id` (`bus_id`),
            INDEX `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT,
            `action` VARCHAR(255) NOT NULL,
            `details` TEXT,
            `ip_address` VARCHAR(45),
            `user_agent` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    // Execute table creation queries
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    
    // Create default admin user if not exists
    $stmt = $pdo->query("SELECT id FROM users WHERE username = 'admin'");
    if ($stmt->rowCount() === 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, email, password_hash, full_name, role, is_active) 
                   VALUES ('admin', 'admin@buscore.com', '$hashedPassword', 'Administrator', 'admin', 1)");
    }
    
    echo "<h2>Database setup completed successfully!</h2>";
    echo "<p>Database: $dbname has been created with all necessary tables.</p>";
    echo "<p>Default admin credentials:</p>";
    echo "<ul>";
    echo "<li>Username: admin</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    echo "<p>Please change the default password after first login.</p>";
    
} catch (PDOException $e) {
    die("<h2>Error setting up database:</h2><p>" . $e->getMessage() . "</p>");
}
?>
