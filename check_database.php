<?php
// Database connection
try {
    $pdo = new PDO("mysql:host=127.0.0.1", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'buscore_db'");
    if ($stmt->rowCount() == 0) {
        echo "Database 'buscore_db' does not exist. Creating...<br>";
        $pdo->exec("CREATE DATABASE buscore_db");
        echo "Database created successfully<br>";
    }
    
    // Connect to the database
    $pdo->exec("USE buscore_db");
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "Users table does not exist. Creating...<br>";
        $sql = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('user', 'bus_owner', 'admin') NOT NULL DEFAULT 'user',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql);
        echo "Users table created successfully<br>";
    } else {
        echo "Users table exists. Checking structure...<br>";
        $stmt = $pdo->query("DESCRIBE users");
        echo "<pre>Users table structure:";
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
        echo "</pre>";
    }
    
    echo "<br>Database check complete. <a href='register.php'>Try registering again</a>";
    
} catch (PDOException $e) {
    die("<h2>Database Error</h2><p>" . $e->getMessage() . "</p>");
}
?>
