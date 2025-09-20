<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Only allow admins to run this script
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . SITE_URL . "/login.php");
    exit();
}

$success = true;
$messages = [];

// Read the SQL file
$sqlFile = dirname(__DIR__) . '/database/add_service_records_table.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found: " . $sqlFile);
}

$sql = file_get_contents($sqlFile);

// Split into individual queries
$queries = array_filter(
    array_map('trim', 
        preg_split(
            "/;\s*(?=([^'\"]*['\"][^'\"]*['\"])*[^'\"]*$)/", 
            $sql
        )
    ),
    'strlen'
);

try {
    $pdo->beginTransaction();
    
    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            $messages[] = [
                'type' => 'success',
                'message' => 'Executed: ' . substr($query, 0, 100) . (strlen($query) > 100 ? '...' : '')
            ];
        } catch (PDOException $e) {
            // If it's a duplicate column error, we can ignore it
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e;
            }
            $messages[] = [
                'type' => 'info',
                'message' => 'Skipped (already exists): ' . substr($query, 0, 100) . (strlen($query) > 100 ? '...' : '')
            ];
        }
    }
    
    $pdo->commit();
    $overallMessage = 'Database update completed successfully!';
    
} catch (Exception $e) {
    $pdo->rollBack();
    $success = false;
    $overallMessage = 'Error updating database: ' . $e->getMessage();
    $messages[] = [
        'type' => 'danger',
        'message' => $e->getMessage()
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2>Database Update</h2>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                            <?php echo $overallMessage; ?>
                        </div>
                        
                        <h4>Execution Log:</h4>
                        <div class="log-container" style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                            <?php foreach ($messages as $msg): ?>
                                <div class="alert alert-<?php echo $msg['type']; ?> p-2 mb-2">
                                    <?php echo htmlspecialchars($msg['message']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html>
