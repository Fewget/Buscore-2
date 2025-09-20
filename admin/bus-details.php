<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

// Check if bus ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid bus ID';
    header('Location: ' . SITE_URL . '/admin/buses.php');
    exit();
}

$bus_id = (int)$_GET['id'];
$bus = null;
$owner = null;
$ratings = [];
$average_rating = 0;

// Get bus details
try {
    // Get bus information
    $stmt = $pdo->prepare("
        SELECT b.*, 
               u.username as owner_username,
               u.email as owner_email,
               bo.company_name as company_name
        FROM buses b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN bus_owners bo ON b.user_id = bo.user_id
        WHERE b.id = ?
    ");
    $stmt->execute([$bus_id]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        $_SESSION['error'] = 'Bus not found';
        header('Location: ' . SITE_URL . '/admin/buses.php');
        exit();
    }
    
    // Get bus owner information
    if ($bus['user_id']) {
        $stmt = $pdo->prepare("
            SELECT u.*, bo.* 
            FROM users u 
            LEFT JOIN bus_owners bo ON u.id = bo.user_id 
            WHERE u.id = ?
        ");
        $stmt->execute([$bus['user_id']]);
        $owner = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get ratings for this bus
    $stmt = $pdo->prepare("
        SELECT r.*, u.username as user_name 
        FROM ratings r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.bus_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$bus_id]);
    $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all ratings for this bus
    $stmt = $pdo->prepare("SELECT driver_rating, conductor_rating, condition_rating FROM ratings WHERE bus_id = ?");
    $stmt->execute([$bus_id]);
    $all_ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate average rating using the same formula as the main site
    // (driver_rating + conductor_rating + condition_rating) / 3
    $stmt = $pdo->prepare("SELECT 
        COALESCE(ROUND(AVG((driver_rating + conductor_rating + condition_rating) / 3), 1), 0) as avg_rating,
        COUNT(*) as rating_count
        FROM ratings 
        WHERE bus_id = ?");
    $stmt->execute([$bus_id]);
    $avg = $stmt->fetch(PDO::FETCH_ASSOC);
    $average_rating = (float)$avg['avg_rating'];
    
    // Debug output
    error_log("Calculated average: $average_rating from {$avg['rating_count']} ratings");
    error_log("Raw ratings data: " . print_r($all_ratings, true));
    
} catch (PDOException $e) {
    error_log("Error fetching bus details: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while fetching bus details';
    header('Location: ' . SITE_URL . '/admin/buses.php');
    exit();
}

// Set page title
$page_title = 'Bus Details - ' . ($bus['registration_number'] ?? '') . ' - ' . SITE_NAME;

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Back button -->
    <div class="mb-4">
        <a href="<?php echo SITE_URL; ?>/admin/buses.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Back to Buses
        </a>
    </div>
    
    <!-- Error/Success Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    
    <!-- Bus Details Card -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2 class="h4 mb-0">
                <i class="fas fa-bus me-2"></i> Bus Details
            </h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4>Basic Information</h4>
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 40%;">Registration Number</th>
                            <td><?php echo htmlspecialchars((string)($bus['registration_number'] ?? 'N/A')); ?></td>
                        </tr>
                        <tr>
                            <th>Bus Name</th>
                            <td><?php echo !empty($bus['bus_name']) ? htmlspecialchars((string)$bus['bus_name']) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th>Route Number</th>
                            <td><?php echo !empty($bus['route_number']) ? htmlspecialchars((string)$bus['route_number']) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th>Company Name</th>
                            <td><?php echo !empty($bus['company_name']) ? htmlspecialchars((string)$bus['company_name']) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-<?php echo ($bus['status'] ?? '') === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($bus['status'] ?? 'inactive'); ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h4>Route Information</h4>
                    <?php if (!empty($bus['route_description'])): ?>
                        <div class="mb-3">
                            <p><?php echo nl2br(htmlspecialchars($bus['route_description'])); ?></p>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No route description available.</p>
                    <?php endif; ?>
                    
                    <h4 class="mt-4">Ratings</h4>
                    <div class="d-flex align-items-center mb-3">
                        <div class="display-4 fw-bold me-3">
                            <?php echo $average_rating > 0 ? $average_rating : 'N/A'; ?>
                        </div>
                        <div>
                            <div class="text-warning">
                                <?php 
                                $fullStars = floor($average_rating);
                                $hasHalfStar = ($average_rating - $fullStars) >= 0.3 && ($average_rating - $fullStars) <= 0.7;
                                $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                                
                                // Full stars
                                for ($i = 0; $i < $fullStars; $i++) {
                                    echo '<i class="fas fa-star"></i>';
                                }
                                
                                // Half star
                                if ($hasHalfStar) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                    $emptyStars--;
                                }
                                
                                // Empty stars
                                for ($i = 0; $i < $emptyStars; $i++) {
                                    echo '<i class="far fa-star"></i>';
                                }
                                ?>
                            </div>
                            <div class="text-muted small">
                                Based on <?php echo count($ratings); ?> rating(s)
                                <?php if (count($all_ratings) > 0): ?>
                                    <div class="mt-2 text-muted small">
                                        <strong>Debug Info:</strong><br>
                                        <?php 
                                        $rating_texts = [];
                                        foreach ($all_ratings as $rating) {
                                            $rating_texts[] = sprintf("Driver: %s, Conductor: %s, Condition: %s", 
                                                $rating['driver_rating'], 
                                                $rating['conductor_rating'], 
                                                $rating['condition_rating']
                                            );
                                        }
                                        ?>
                                        Raw Ratings: <?php echo htmlspecialchars(implode(' | ', $rating_texts)); ?><br>
                                        Calculated Avg: <?php echo $average_rating; ?><br>
                                        Rating Count: <?php echo count($all_ratings); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="mt-4 pt-3 border-top">
                <a href="<?php echo SITE_URL; ?>/admin/edit-bus.php?id=<?php echo $bus_id; ?>" class="btn btn-primary me-2">
                    <i class="fas fa-edit me-1"></i> Edit Bus
                </a>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteBusModal">
                    <i class="fas fa-trash-alt me-1"></i> Delete Bus
                </button>
            </div>
        </div>
    </div>
    
    <!-- Owner Information Card -->
    <?php if ($owner): ?>
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h3 class="h5 mb-0">
                <i class="fas fa-user-tie me-2"></i> Owner Information
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 40%;">Username</th>
                            <td><?php echo htmlspecialchars($owner['username']); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($owner['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Company</th>
                            <td><?php echo !empty($owner['company_name']) ? htmlspecialchars($owner['company_name']) : 'N/A'; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end">
                        <a href="<?php echo SITE_URL; ?>/admin/user-details.php?id=<?php echo $owner['id']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-user me-1"></i> View Full Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Ratings Section -->
    <div class="card">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="h5 mb-0">
                    <i class="fas fa-star me-2"></i> Ratings & Reviews
                </h3>
                <span class="badge bg-primary rounded-pill"><?php echo count($ratings); ?></span>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($ratings)): ?>
                <div class="list-group">
                    <?php foreach ($ratings as $rating): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">
                                    <?php echo htmlspecialchars((string)($rating['user_name'] ?? '')); ?>
                                    <small class="text-muted">
                                        <?php 
                                        $fullStars = floor($rating['driver_rating']);
                                        for ($i = 0; $i < 5; $i++) {
                                            if ($i < $fullStars) {
                                                echo '<i class="fas fa-star text-warning"></i>';
                                            } else {
                                                echo '<i class="far fa-star text-warning"></i>';
                                            }
                                        }
                                        ?>
                                    </small>
                                </h5>
                                <small class="text-muted">
                                    <?php echo date('M j, Y', strtotime($rating['created_at'])); ?>
                                </small>
                            </div>
                            <?php if (!empty($rating['comments'])): ?>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($rating['comments'])); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="far fa-star fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No ratings yet for this bus.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBusModal" tabindex="-1" aria-labelledby="deleteBusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteBusModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this bus? This action cannot be undone and will also remove all associated ratings and reviews.</p>
                <p class="fw-bold">Bus: <?php echo htmlspecialchars($bus['registration_number']); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="<?php echo SITE_URL; ?>/admin/delete-bus.php" method="POST" style="display: inline;">
                    <input type="hidden" name="bus_id" value="<?php echo $bus_id; ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Delete Bus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
