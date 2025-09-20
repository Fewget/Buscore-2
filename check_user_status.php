<?php
require_once 'includes/config.php';

$username = 'wqw';

try {
    // 1. Check user in users table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    echo "<h2>User Account Status</h2>";
    
    if (!$user) {
        die("<div class='alert alert-danger'>User '$username' not found in the database.</div>");
    }
    
    echo "<h3>User Details:</h3>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    // 2. Check is_active status
    if ($user['is_active'] != 1) {
        echo "<div class='alert alert-warning'>⚠️ Account is not active. Status: " . 
             htmlspecialchars($user['is_active']) . "</div>";
    } else {
        echo "<div class='alert alert-success'>✅ Account is active</div>";
    }
    
    // 3. Check password hash
    echo "<h3>Password Verification</h3>";
    if (empty($user['password'])) {
        echo "<div class='alert alert-danger'>❌ No password hash found</div>";
    } else {
        echo "<p>Password hash: " . substr($user['password'], 0, 20) . "...</p>";
    }
    
    // 4. Check bus_owners table
    $stmt = $pdo->prepare("SELECT * FROM bus_owners WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $bus_owner = $stmt->fetch();
    
    echo "<h3>Bus Owner Status</h3>";
    if ($bus_owner) {
        echo "<div class='alert alert-success'>✅ Registered as bus owner</div>";
        echo "<pre>";
        print_r($bus_owner);
        echo "</pre>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ Not registered in bus_owners table</div>";
    }
    
    // 5. Test login directly
    echo "<h3>Test Login</h3>";
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
        if (password_verify($_POST['password'], $user['password'])) {
            echo "<div class='alert alert-success'>✅ Password matches!</div>";
            
            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            echo "<div class='alert alert-info'>Session variables set. <a href='check_owner.php'>Check login status</a></div>";
            
        } else {
            echo "<div class='alert alert-danger'>❌ Incorrect password</div>";
        }
    }
    
    // Password test form
    echo "<form method='post' class='mt-4'>
        <div class='mb-3'>
            <label for='password' class='form-label'>Test Password:</label>
            <input type='password' class='form-control' id='password' name='password' required>
        </div>
        <button type='submit' class='btn btn-primary'>Test Login</button>
    </form>
    
    <div class='mt-4'>
        <a href='login.php' class='btn btn-secondary'>Back to Login</a>
        <a href='check_owner.php' class='btn btn-info'>Check Owner Status</a>
    </div>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . 
         htmlspecialchars($e->getMessage()) . "</div>";
}
?>
