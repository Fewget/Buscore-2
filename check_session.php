<?php
session_start();
require_once 'includes/config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2>Session Information</h2>
            </div>
            <div class="card-body">
                <h3>Session Status</h3>
                <pre><?php 
                    echo "Session ID: " . session_id() . "\n";
                    echo "Session Status: " . session_status() . "\n";
                    echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
                ?></pre>

                <h3>Session Data</h3>
                <pre><?php print_r($_SESSION); ?></pre>

                <h3>Login Status</h3>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="alert alert-success">
                        ✅ You are logged in as: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Unknown'); ?>
                    </div>
                    <p>User ID: <?php echo $_SESSION['user_id']; ?></p>
                    <p>Role: <?php echo $_SESSION['role'] ?? 'Not set'; ?></p>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                <?php else: ?>
                    <div class="alert alert-warning">
                        ❌ You are not logged in.
                    </div>
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                <?php endif; ?>

                <h3 class="mt-4">Debug Information</h3>
                <h4>Cookies</h4>
                <pre><?php print_r($_COOKIE); ?></pre>

                <h4>Server Information</h4>
                <pre>PHP Version: <?php echo phpversion(); ?>
Session Save Path: <?php echo session_save_path(); ?>
Session Cookie Parameters: <?php print_r(session_get_cookie_params()); ?></pre>
            </div>
        </div>
    </div>
</body>
</html>
