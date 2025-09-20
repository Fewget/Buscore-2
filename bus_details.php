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
        <link rel="stylesheet" href="/BS/assets/css/bus-details.css">
        <style>
            /* Additional inline styles for this page */
            body {
                padding-top: 20px;
                background-color: #f5f7fa;
            }
            .maintenance-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 15px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container py-4">
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h4 mb-0">
                            <i class="fas fa-bus me-2"></i><?php echo htmlspecialchars($bus['bus_name']); ?>
                        </h1>
                        <?php if ($bus['is_premium']): ?>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-crown me-1"></i>Premium
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="info-box mb-4">
                                <h3 class="section-header">
                                    <i class="fas fa-info-circle me-2"></i>Basic Information
                                </h3>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-id-card me-2 text-muted"></i>
                                            <span class="fw-bold">Registration:</span>
                                        </div>
                                        <div class="ps-4"><?php echo htmlspecialchars($bus['registration_number']); ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-route me-2 text-muted"></i>
                                            <span class="fw-bold">Route:</span>
                                        </div>
                                        <div class="ps-4"><?php echo htmlspecialchars($bus['route_number'] . ' - ' . $bus['route_description']); ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-user-tie me-2 text-muted"></i>
                                            <span class="fw-bold">Owner:</span>
                                        </div>
                                        <div class="ps-4"><?php echo htmlspecialchars($bus['owner_username']); ?></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-building me-2 text-muted"></i>
                                            <span class="fw-bold">Company:</span>
                                        </div>
                                        <div class="ps-4"><?php echo htmlspecialchars($bus['company_name']); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($bus['avg_rating'] > 0): ?>
                                <div class="info-box">
                                    <h3 class="section-header">
                                        <i class="fas fa-star me-2"></i>Ratings
                                    </h3>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="display-4 fw-bold me-3"><?php echo number_format($bus['avg_rating'], 1); ?>/5.0</div>
                                        <div class="rating">
                                            <?php 
                                            $fullStars = floor($bus['avg_rating']);
                                            $hasHalfStar = ($bus['avg_rating'] - $fullStars) >= 0.5;
                                            $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                                            
                                            for ($i = 0; $i < $fullStars; $i++): 
                                                echo '<i class="fas fa-star"></i>';
                                            endfor; 
                                            
                                            if ($hasHalfStar) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            }
                                            
                                            for ($i = 0; $i < $emptyStars; $i++): 
                                                echo '<i class="far fa-star"></i>';
                                            endfor; 
                                            ?>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-0">Based on <?php echo $bus['rating_count']; ?> rating<?php echo $bus['rating_count'] != 1 ? 's' : ''; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <h3 class="section-header">
                                <i class="fas fa-tools maintenance-icon"></i>Maintenance History
                            </h3>
                            <div class="maintenance-grid">
                                <div class="card maintenance-card">
                                    <div class="card-body">
                                        <h5 class="card-title d-flex align-items-center">
                                            <i class="fas fa-search me-2 text-primary"></i>Last Inspection
                                        </h5>
                                    <?php if (!empty($bus['last_inspection_date'])): ?>
                                        <p class="mb-1">Date: <?php echo date('M j, Y', strtotime($bus['last_inspection_date'])); ?></p>
                                        <p>Mileage: <?php echo !empty($bus['last_inspection_mileage']) ? number_format($bus['last_inspection_mileage']) . ' km' : 'N/A'; ?></p>
                                    <?php else: ?>
                                        <p class="text-muted">No inspection record</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                                </div> <!-- Close inspection card -->
                                
                                <div class="card maintenance-card">
                                    <div class="card-body">
                                        <h5 class="card-title d-flex align-items-center">
                                            <i class="fas fa-oil-can me-2 text-primary"></i>Last Oil Change
                                        </h5>
                                        <?php if (!empty($bus['last_oil_change_date'])): ?>
                                            <p class="mb-1"><strong>Date:</strong> <?php echo date('M j, Y', strtotime($bus['last_oil_change_date'])); ?></p>
                                            <p class="mb-0"><strong>Mileage:</strong> <?php echo !empty($bus['last_oil_change_mileage']) ? number_format($bus['last_oil_change_mileage']) . ' km' : 'N/A'; ?></p>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">No oil change record</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="card maintenance-card">
                                    <div class="card-body">
                                        <h5 class="card-title d-flex align-items-center">
                                            <i class="fas fa-brake-warning me-2 text-primary"></i>Last Brake Service
                                        </h5>
                                        <?php if (!empty($bus['last_brake_liner_change_date'])): ?>
                                            <p class="mb-1"><strong>Date:</strong> <?php echo date('M j, Y', strtotime($bus['last_brake_liner_change_date'])); ?></p>
                                            <p class="mb-0"><strong>Mileage:</strong> <?php echo !empty($bus['last_brake_liner_mileage']) ? number_format($bus['last_brake_liner_mileage']) . ' km' : 'N/A'; ?></p>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">No brake service record</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="card maintenance-card">
                                    <div class="card-body">
                                        <h5 class="card-title d-flex align-items-center">
                                            <i class="fas fa-tire me-2 text-primary"></i>Last Tyre Change
                                        </h5>
                                        <?php if (!empty($bus['last_tyre_change_date'])): ?>
                                            <p class="mb-1"><strong>Date:</strong> <?php echo date('M j, Y', strtotime($bus['last_tyre_change_date'])); ?></p>
                                            <p class="mb-0"><strong>Mileage:</strong> <?php echo !empty($bus['last_tyre_change_mileage']) ? number_format($bus['last_tyre_change_mileage']) . ' km' : 'N/A'; ?></p>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">No tyre change record</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="card maintenance-card">
                                    <div class="card-body">
                                        <h5 class="card-title d-flex align-items-center">
                                            <i class="fas fa-shield-alt me-2 text-primary"></i>Insurance
                                        </h5>
                                        <?php if (!empty($bus['insurance_expiry_date'])): 
                                            $expiryDate = new DateTime($bus['insurance_expiry_date']);
                                            $today = new DateTime();
                                            $isExpired = $expiryDate < $today;
                                            $daysRemaining = $today->diff($expiryDate)->format('%a');
                                        ?>
                                            <p class="mb-1"><strong>Expiry:</strong> <?php echo date('M j, Y', strtotime($bus['insurance_expiry_date'])); ?></p>
                                            <p class="mb-0 <?php echo $isExpired ? 'text-danger' : 'text-success'; ?>">
                                                <i class="fas <?php echo $isExpired ? 'fa-exclamation-circle' : 'fa-check-circle'; ?> me-1"></i>
                                                <?php 
                                                if ($isExpired) {
                                                    echo "Expired " . $daysRemaining . " days ago";
                                                } else {
                                                    echo "Expires in " . $daysRemaining . " days";
                                                }
                                                ?>
                                            </p>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">No insurance information</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div> <!-- Close maintenance-grid -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Service records are now displayed in the maintenance section -->
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <a href="search.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Search
                    </a>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <p class="text-muted mb-0">
                        <small>Last updated: <?php echo date('M j, Y H:i'); ?></small>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <style>
        /* Smooth scrolling for anchor links */
        html {
            scroll-behavior: smooth;
        }
        
        /* Footer styling */
        footer {
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        
        /* Animation for cards on load */
        .maintenance-card {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease-out forwards;
        }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Add delay to each card */
        .maintenance-card:nth-child(1) { animation-delay: 0.1s; }
        .maintenance-card:nth-child(2) { animation-delay: 0.2s; }
        .maintenance-card:nth-child(3) { animation-delay: 0.3s; }
        .maintenance-card:nth-child(4) { animation-delay: 0.4s; }
        .maintenance-card:nth-child(5) { animation-delay: 0.5s; }
        
        /* Print styles */
        @media print {
            .no-print, footer, .btn {
                display: none !important;
            }
            
            body {
                padding: 0;
                font-size: 12pt;
            }
            
            .card {
                border: 1px solid #dee2e6 !important;
                box-shadow: none !important;
                page-break-inside: avoid;
            }
        }
    </style>
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
