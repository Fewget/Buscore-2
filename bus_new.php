<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/premium_functions.php';

// No login required to view bus details
// Only require login for posting ratings and reviews

$busId = (int)$_GET['id'];
$bus = null;
$reviews = [];
$userRating = null;
$successMessage = '';
$error = '';

// Get bus details
try {
    // Get bus information
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            COALESCE(AVG((r.driver_rating + r.conductor_rating + r.condition_rating) / 3), 0) as avg_rating,
            COUNT(r.id) as rating_count
        FROM buses b
        LEFT JOIN ratings r ON b.id = r.bus_id
        WHERE b.id = ?
        GROUP BY b.id
    ");
    $stmt->execute([$busId]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
    
    // Check premium features
    $showBusName = shouldShowBusName($pdo, $busId);
    $showCompanyName = shouldShowCompanyName($pdo, $busId);
    
    // Get premium feature details if any
    $premiumFeature = [];
    if ($showBusName || $showCompanyName) {
        $featureName = $showBusName ? 'show_bus_name' : 'show_company_name';
        $premiumFeature = getPremiumFeatureStatus($pdo, $busId, $featureName);
    }
    
    // Get reviews for this bus
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, u.full_name,
               (r.driver_rating + r.conductor_rating + r.condition_rating) / 3 as avg_rating
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.bus_id = ? AND r.is_approved = 1
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$busId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if user has already rated this bus (only if logged in)
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM ratings WHERE bus_id = ? AND user_id = ?");
        $stmt->execute([$busId, $_SESSION['user_id']]);
        $userRating = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $error = 'Error loading bus details: ' . $e->getMessage();
    error_log($error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    if (isset($_POST['submit_rating'])) {
        $driverRating = isset($_POST['driver_rating']) ? (int)$_POST['driver_rating'] : 0;
        $conductorRating = isset($_POST['conductor_rating']) ? (int)$_POST['conductor_rating'] : 0;
        $conditionRating = isset($_POST['condition_rating']) ? (int)$_POST['condition_rating'] : 0;
        $comment = trim($_POST['comment'] ?? '');
        
        // Validate ratings (1-5)
        $ratings = [$driverRating, $conductorRating, $conditionRating];
        $isValid = true;
        
        foreach ($ratings as $rating) {
            if ($rating < 1 || $rating > 5) {
                $isValid = false;
                break;
            }
        }
        
        if ($isValid) {
            try {
                $pdo->beginTransaction();
                
                // Insert or update rating
                if ($userRating) {
                    $stmt = $pdo->prepare("
                        UPDATE ratings 
                        SET driver_rating = ?, conductor_rating = ?, condition_rating = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$driverRating, $conductorRating, $conditionRating, $userRating['id']]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO ratings (bus_id, user_id, driver_rating, conductor_rating, condition_rating)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$busId, $_SESSION['user_id'], $driverRating, $conductorRating, $conditionRating]);
                }
                
                // Add review if comment exists
                if (!empty($comment)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO reviews (bus_id, user_id, driver_rating, conductor_rating, condition_rating, comment, is_approved)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $busId, 
                        $_SESSION['user_id'], 
                        $driverRating, 
                        $conductorRating, 
                        $conditionRating, 
                        $comment,
                        1 // Auto-approve for now
                    ]);
                }
                
                $pdo->commit();
                $successMessage = 'Your rating has been submitted successfully!';
                
                // Refresh the page to show updated data
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=$busId");
                exit();
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Error submitting your rating: ' . $e->getMessage();
                error_log($error);
            }
        } else {
            $error = 'Please provide valid ratings between 1 and 5 for all categories.';
        }
    }
}

// Set page title
$pageTitle = 'Bus Details';
if (isset($bus['bus_name']) && $showBusName) {
    $pageTitle = $bus['bus_name'] . ' - ' . $pageTitle;
}

// Include header
include 'includes/header.php';
?>

<div class="container py-5">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>
    
    <!-- Bus Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-md-center">
                <div class="me-md-4 mb-3 mb-md-0 text-center">
                    <div class="bus-icon" style="font-size: 4rem; color: #6c757d;">
                        <i class="fas fa-bus"></i>
                    </div>
                    
                    <?php if (!empty($premiumFeature)): ?>
                        <div class="mt-2">
                            <span class="badge bg-success">
                                <i class="fas fa-crown me-1"></i> Premium
                            </span>
                            <div class="small text-muted mt-1">
                                <?php echo getRemainingTime($premiumFeature['end_date']); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2">
                        <h1 class="mb-0">
                            <?php echo htmlspecialchars($bus['registration_number']); ?>
                            <?php if (!empty($bus['route_number'])): ?>
                                <span class="badge bg-primary ms-2">
                                    Route <?php echo htmlspecialchars($bus['route_number']); ?>
                                </span>
                            <?php endif; ?>
                        </h1>
                        
                        <div class="mt-2 mt-md-0">
                            <div class="rating-display">
                                <?php if ($bus['rating_count'] > 0): ?>
                                    <span class="display-4 fw-bold text-warning">
                                        <?php echo number_format($bus['avg_rating'], 1); ?>
                                    </span>
                                    <span class="text-muted">/ 5.0</span>
                                    <div class="small text-muted">
                                        <?php echo $bus['rating_count']; ?> rating<?php echo $bus['rating_count'] != 1 ? 's' : ''; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">No ratings yet</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bus-details">
                        <?php if ($showBusName): ?>
                            <h2 class="h3 mb-2"><?php echo htmlspecialchars($bus['bus_name']); ?></h2>
                        <?php else: ?>
                            <h2 class="h3 mb-2">Bus #<?php echo $bus['id']; ?></h2>
                            <div class="alert alert-info small py-1 px-2 d-inline-block mb-2">
                                <i class="fas fa-info-circle me-1"></i> Bus name hidden (premium feature)
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($showCompanyName): ?>
                            <p class="text-muted mb-2">
                                <i class="fas fa-building me-1"></i> 
                                <?php echo htmlspecialchars($bus['company_name']); ?>
                            </p>
                        <?php else: ?>
                            <p class="text-muted mb-2">
                                <i class="fas fa-building me-1"></i> 
                                <span class="text-muted fst-italic">Company name hidden (premium feature)</span>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($bus['route_description'])): ?>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($bus['route_description'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Rating Form -->
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2"></i>
                        <?php echo $userRating ? 'Update Your Rating' : 'Rate This Bus'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="text-center py-4">
                            <p class="mb-3">Please log in to rate this bus</p>
                            <a href="login.php?redirect=<?php echo urlencode("/bus.php?id=$busId"); ?>" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i> Login to Rate
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="post" action="">
                            <div class="mb-4">
                                <h6>Driver</h6>
                                <div class="rating-input mb-3" data-target="driver_rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="driver_<?php echo $i; ?>" name="driver_rating" 
                                               value="<?php echo $i; ?>" 
                                               <?php echo ($userRating && $userRating['driver_rating'] == $i) ? 'checked' : ''; ?>>
                                        <label for="driver_<?php echo $i; ?>"><?php echo $i; ?></label>
                                    <?php endfor; ?>
                                    <div class="clear"></div>
                                </div>
                                
                                <h6>Conductor</h6>
                                <div class="rating-input mb-3" data-target="conductor_rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="conductor_<?php echo $i; ?>" name="conductor_rating" 
                                               value="<?php echo $i; ?>" 
                                               <?php echo ($userRating && $userRating['conductor_rating'] == $i) ? 'checked' : ''; ?>>
                                        <label for="conductor_<?php echo $i; ?>"><?php echo $i; ?></label>
                                    <?php endfor; ?>
                                    <div class="clear"></div>
                                </div>
                                
                                <h6>Bus Condition</h6>
                                <div class="rating-input mb-3" data-target="condition_rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="condition_<?php echo $i; ?>" name="condition_rating" 
                                               value="<?php echo $i; ?>" 
                                               <?php echo ($userRating && $userRating['condition_rating'] == $i) ? 'checked' : ''; ?>>
                                        <label for="condition_<?php echo $i; ?>"><?php echo $i; ?></label>
                                    <?php endfor; ?>
                                    <div class="clear"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="comment" class="form-label">Leave a review (optional)</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" 
                                          placeholder="Share your experience..."></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" name="submit_rating" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    <?php echo $userRating ? 'Update Rating' : 'Submit Rating'; ?>
                                </button>
                                
                                <?php if ($userRating): ?>
                                    <div class="text-muted small">
                                        Last updated: <?php echo date('M j, Y', strtotime($userRating['updated_at'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Rating Breakdown -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Rating Breakdown</h5>
                </div>
                <div class="card-body">
                    <?php if ($bus['rating_count'] > 0): ?>
                        <?php 
                        // Get rating distribution
                        $stmt = $pdo->prepare("
                            SELECT 
                                COUNT(*) as count,
                                FLOOR((driver_rating + conductor_rating + condition_rating) / 3) as rating
                            FROM ratings 
                            WHERE bus_id = ?
                            GROUP BY FLOOR((driver_rating + conductor_rating + condition_rating) / 3)
                            ORDER BY rating DESC
                        
                        ");
                        $stmt->execute([$busId]);
                        $ratingDistribution = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                        
                        // Fill in missing ratings with 0
                        $distribution = [];
                        for ($i = 5; $i >= 1; $i--) {
                            $distribution[$i] = $ratingDistribution[$i] ?? 0;
                        }
                        ?>
                        
                        <?php foreach ($distribution as $stars => $count): ?>
                            <div class="mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="text-nowrap me-2" style="width: 80px;">
                                        <?php echo $stars; ?> <i class="fas fa-star text-warning"></i>
                                    </div>
                                    <div class="progress flex-grow-1" style="height: 20px;">
                                        <?php 
                                        $percentage = $bus['rating_count'] > 0 
                                            ? round(($count / $bus['rating_count']) * 100) 
                                            : 0;
                                        ?>
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%" 
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <div class="ms-2 text-muted small" style="width: 40px;">
                                        <?php echo $count; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No ratings available yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Reviews -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-comments me-2"></i>
                        Reviews
                        <?php if ($bus['rating_count'] > 0): ?>
                            <span class="badge bg-primary"><?php echo $bus['rating_count']; ?></span>
                        <?php endif; ?>
                    </h5>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="#rating-form" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> Write a Review
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($reviews)): ?>
                        <div class="review-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($review['username'], 0, 1)); ?>
                                            </div>
                                            <div class="ms-2">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($review['full_name'] ?? $review['username']); ?></h6>
                                                <div class="text-muted small">
                                                    <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-warning">
                                            <?php 
                                            $avgRating = round($review['avg_rating']);
                                            for ($i = 1; $i <= 5; $i++): 
                                            ?>
                                                <i class="fas fa-star<?php echo $i <= $avgRating ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($review['comment'])): ?>
                                        <div class="review-comment mt-2">
                                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="review-ratings mt-2 small text-muted">
                                        <span class="me-3">
                                            <i class="fas fa-user-tie me-1"></i> 
                                            Driver: <?php echo $review['driver_rating']; ?>/5
                                        </span>
                                        <span class="me-3">
                                            <i class="fas fa-user-friends me-1"></i> 
                                            Conductor: <?php echo $review['conductor_rating']; ?>/5
                                        </span>
                                        <span>
                                            <i class="fas fa-bus me-1"></i> 
                                            Condition: <?php echo $review['condition_rating']; ?>/5
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($bus['rating_count'] > count($reviews)): ?>
                            <div class="text-center mt-3">
                                <a href="reviews.php?bus_id=<?php echo $busId; ?>" class="btn btn-outline-primary">
                                    View All Reviews (<?php echo $bus['rating_count']; ?>)
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="far fa-comment-dots fa-3x text-muted"></i>
                            </div>
                            <p class="text-muted mb-0">No reviews yet. Be the first to review this bus!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.rating-input {
    direction: rtl;
    unicode-bidi: bidi-override;
    text-align: left;
}
.rating-input input {
    display: none;
}
.rating-input label {
    display: inline-block;
    width: 30px;
    position: relative;
    cursor: pointer;
    color: #ddd;
    font-size: 1.2rem;
}
.rating-input label:before {
    content: "★";
    position: absolute;
    left: 0;
    color: #ffc107;
}
.rating-input input:checked ~ label:before {
    color: #ddd;
}
.rating-input label:hover:before,
.rating-input label:hover ~ label:before {
    color: #ffc107;
}
.rating-input input:checked + label:hover:before,
.rating-input input:checked ~ label:hover:before {
    color: #ffc107;
}
.clear {
    clear: both;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize rating inputs
    document.querySelectorAll('.rating-input').forEach(function(ratingInput) {
        const targetInput = ratingInput.getAttribute('data-target');
        const inputs = ratingInput.querySelectorAll('input[type="radio"]');
        
        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                // Update the visual state
                const labels = ratingInput.querySelectorAll('label');
                const value = parseInt(this.value);
                
                labels.forEach(function(label, index) {
                    if (5 - index <= value) {
                        label.style.color = '#ffc107';
                    } else {
                        label.style.color = '#ddd';
                    }
                });
            });
            
            // Initialize the visual state
            if (input.checked) {
                input.dispatchEvent(new Event('change'));
            }
        });
    });
});
</script>
