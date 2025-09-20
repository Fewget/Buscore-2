<?php
// Include the main config file
require_once __DIR__ . '/includes/config.php';

// Test database connection
try {
    // Test PDO connection
    $stmt = $pdo->query('SELECT 1');
    $dbTest = $stmt->fetch(PDO::FETCH_ASSOC);
    $dbStatus = $dbTest ? '✅ Connected successfully' : '❌ Connection failed';
    
    // Test session
    $sessionStatus = isset($_SESSION) ? '✅ Session is active' : '❌ Session not started';
    
    // Test includes
    $includes = [
        'config.php' => file_exists(__DIR__ . '/includes/config.php'),
        'functions.php' => file_exists(__DIR__ . '/includes/functions.php'),
        'header.php' => file_exists(__DIR__ . '/includes/header.php'),
        'footer.php' => file_exists(__DIR__ . '/includes/footer.php')
    ];
    
    // Get PHP version
    $phpVersion = phpversion();
    
    // Get database info
    $dbInfo = [];
    try {
        $dbInfo['driver'] = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $dbInfo['version'] = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    } catch (Exception $e) {
        $dbInfo['error'] = $e->getMessage();
    }
    
} catch (PDOException $e) {
    $dbError = '❌ Database connection failed: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connection Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Connection Test</h4>
                    </div>
                    <div class="card-body">
                        <h5>Environment</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>PHP Version</th>
                                <td><?php echo $phpVersion; ?></td>
                            </tr>
                            <tr>
                                <th>Server Software</th>
                                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td>
                            </tr>
                        </table>
                        
                        <h5 class="mt-4">Database Connection</h5>
                        <?php if (isset($dbError)): ?>
                            <div class="alert alert-danger"><?php echo $dbError; ?></div>
                        <?php else: ?>
                            <div class="alert alert-success"><?php echo $dbStatus; ?></div>
                            <table class="table table-bordered">
                                <?php foreach ($dbInfo as $key => $value): ?>
                                    <tr>
                                        <th><?php echo ucfirst($key); ?></th>
                                        <td><?php echo $value; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php endif; ?>
                        
                        <h5 class="mt-4">Session Status</h5>
                        <div class="alert alert-<?php echo strpos($sessionStatus, '✅') !== false ? 'success' : 'danger'; ?>">
                            <?php echo $sessionStatus; ?>
                        </div>
                        
                        <h5 class="mt-4">File Includes</h5>
                        <table class="table table-bordered">
                            <?php foreach ($includes as $file => $exists): ?>
                                <tr>
                                    <td>includes/<?php echo $file; ?></td>
                                    <td>
                                        <?php if ($exists): ?>
                                            <span class="text-success">✅ Found</span>
                                        <?php else: ?>
                                            <span class="text-danger">❌ Not found</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                            <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-secondary">Visit Site</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
