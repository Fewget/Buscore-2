<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get bus ID from URL
$busId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$busId) {
    die('No bus ID provided');
}

// Query the database
try {
    // Get all columns from buses table
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
    $stmt->execute([$busId]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        die('Bus not found');
    }
    
    // Display the raw data
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Bus Data Debug</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="p-4">
        <div class="container">
            <h1>Bus Data Debug</h1>
            <div class="card mt-4">
                <div class="card-header">
                    <h2>Raw Bus Data (ID: ' . $busId . ')</h2>
                </div>
                <div class="card-body">
                    <pre>' . htmlspecialchars(print_r($bus, true)) . '</pre>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h2>Registration Number Test</h2>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>Field</th>
                            <th>Value</th>
                            <th>Formatted</th>
                        </tr>
                        <tr>
                            <td>registration_number</td>
                            <td>' . htmlspecialchars($bus['registration_number'] ?? 'NULL') . '</td>
                            <td>' . htmlspecialchars(format_registration_number($bus['registration_number'] ?? '')) . '</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="bus.php?id=' . $busId . '" class="btn btn-primary">Back to Bus Page</a>
            </div>
        </div>
    </body>
    </html>';
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>
