<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:\\xampp\\htdocs\\BS\\php_errors.log');

// Start output buffering
ob_start();

try {
    // Check if required files exist
    if (!file_exists('includes/config.php')) {
        throw new Exception('config.php not found');
    }
    if (!file_exists('includes/functions.php')) {
        throw new Exception('functions.php not found');
    }
    
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    
    // Check database connection
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Database connection not established');
    }
    
    // Get bus ID from URL
    $bus_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($bus_id <= 0) {
        throw new Exception('Invalid bus ID');
    }
    
    // Get bus data
    $stmt = $pdo->prepare("
        SELECT b.*, u.username as owner_username
        FROM buses b
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bus_id]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        throw new Exception('Bus not found');
    }
    
    // Get rating info
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(AVG((driver_rating + conductor_rating + condition_rating) / 3), 0) as avg_rating,
            COUNT(id) as rating_count
        FROM ratings 
        WHERE bus_id = ?
    ");
    $stmt->execute([$bus_id]);
    $rating_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $bus = array_merge($bus, $rating_info);
    
    // Service records are stored directly in the buses table
    
    // Start HTML output
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bus Details - <?php echo htmlspecialchars($bus['bus_name']); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body>
        <div class="container py-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h1 class="h4 mb-0"><?php echo htmlspecialchars($bus['bus_name']); ?></h1>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h3>Basic Information</h3>
                            <p><strong>Registration Number:</strong> <?php echo htmlspecialchars($bus['registration_number']); ?></p>
                            <p><strong>Route:</strong> <?php echo htmlspecialchars($bus['route_number'] . ' - ' . $bus['route_description']); ?></p>
                            <p><strong>Owner:</strong> <?php echo htmlspecialchars($bus['owner_username']); ?></p>
                            <p><strong>Company:</strong> <?php echo htmlspecialchars($bus['company_name']); ?></p>
                            
                            <?php if ($bus['avg_rating'] > 0): ?>
                                <div class="mt-3">
                                    <h4>Rating: <?php echo number_format($bus['avg_rating'], 1); ?>/5.0</h4>
                                    <p class="text-muted">Based on <?php echo $bus['rating_count']; ?> ratings</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <h3>Maintenance</h3>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Last Inspection</h5>
                                    <?php if (!empty($bus['last_inspection_date'])): ?>
                                        <p class="mb-1">Date: <?php echo date('M j, Y', strtotime($bus['last_inspection_date'])); ?></p>
                                        <p>Mileage: <?php echo !empty($bus['last_inspection_mileage']) ? number_format($bus['last_inspection_mileage']) . ' km' : 'N/A'; ?></p>
                                    <?php else: ?>
                                        <p class="text-muted">No inspection record</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Last Oil Change</h5>
                                    <?php if (!empty($bus['last_oil_change_date'])): ?>
                                        <p class="mb-1">Date: <?php echo date('M j, Y', strtotime($bus['last_oil_change_date'])); ?></p>
                                        <p>Mileage: <?php echo !empty($bus['last_oil_change_mileage']) ? number_format($bus['last_oil_change_mileage']) . ' km' : 'N/A'; ?></p>
                                    <?php else: ?>
                                        <p class="text-muted">No oil change record</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Last Brake Service</h5>
                                    <?php if (!empty($bus['last_brake_liner_change_date'])): ?>
                                        <p class="mb-1">Date: <?php echo date('M j, Y', strtotime($bus['last_brake_liner_change_date'])); ?></p>
                                        <p>Mileage: <?php echo !empty($bus['last_brake_liner_mileage']) ? number_format($bus['last_brake_liner_mileage']) . ' km' : 'N/A'; ?></p>
                                    <?php else: ?>
                                        <p class="text-muted">No brake service record</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Last Tyre Change</h5>
                                    <?php if (!empty($bus['last_tyre_change_date'])): ?>
                                        <p class="mb-1">Date: <?php echo date('M j, Y', strtotime($bus['last_tyre_change_date'])); ?></p>
                                        <p>Mileage: <?php echo !empty($bus['last_tyre_change_mileage']) ? number_format($bus['last_tyre_change_mileage']) . ' km' : 'N/A'; ?></p>
                                    <?php else: ?>
                                        <p class="text-muted">No tyre change record</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Insurance</h5>
                                    <?php if (!empty($bus['insurance_expiry_date'])): 
                                        $expiryDate = new DateTime($bus['insurance_expiry_date']);
                                        $today = new DateTime();
                                        $isExpired = $expiryDate < $today;
                                        $daysRemaining = $today->diff($expiryDate)->format('%a');
                                    ?>
                                        <p class="mb-1">Expiry Date: <?php echo date('M j, Y', strtotime($bus['insurance_expiry_date'])); ?></p>
                                        <p class="<?php echo $isExpired ? 'text-danger' : 'text-success'; ?>">
                                            <?php 
                                            if ($isExpired) {
                                                echo "Expired " . $daysRemaining . " days ago";
                                            } else {
                                                echo "Expires in " . $daysRemaining . " days";
                                            }
                                            ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted">No insurance information</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Service records are now displayed in the maintenance section -->
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    // Display error message
    echo "<h1>Error</h1>";
    echo "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    
    // Log the error
    error_log('Bus Details Error: ' . $e->getMessage());
}

// Flush output buffer
ob_end_flush();
?>
