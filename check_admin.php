<?php
// Include configuration
try {
    require_once 'includes/config.php';
    
    echo "<h2>Database Connection Test</h2>";
    echo "<p>Connected to database successfully!</p>";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if($stmt->rowCount() > 0) {
        echo "<p>✅ Users table exists</p>";
        
        // Check admin user
        $stmt = $pdo->query("SELECT * FROM users WHERE username = 'admin'");
        $admin = $stmt->fetch();
        
        if($admin) {
            echo "<p>✅ Admin user found</p>";
            echo "<pre>";
            print_r($admin);
            echo "</pre>";
            
            // Verify password
            if(password_verify('admin123', $admin['password_hash'])) {
                echo "<p>✅ Password verification successful</p>";
            } else {
                echo "<p>❌ Password verification failed</p>";
                echo "<p>Hashed password in DB: " . $admin['password_hash'] . "</p>";
                echo "<p>Hash of 'admin123': " . password_hash('admin123', PASSWORD_DEFAULT) . "</p>";
            }
        } else {
            echo "<p>❌ Admin user not found</p>";
            
            // Create admin user if not exists
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (username, email, password_hash, role, is_active) 
                       VALUES ('admin', 'admin@buscore.com', '$hashedPassword', 'admin', 1)");
            
            echo "<p>✅ Created admin user with password 'admin123'</p>";
        }
    } else {
        echo "<p>❌ Users table does not exist. Running database setup...</p>";
        
        // Run database setup
        require_once 'database/setup.php';
        
        echo "<p>✅ Database setup complete. <a href='check_admin.php'>Check again</a></p>";
    }
    
} catch(PDOException $e) {
    die("<h2>Error</h2><p>" . $e->getMessage() . "</p>");
}
?>
