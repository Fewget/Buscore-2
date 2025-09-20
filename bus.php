<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Debug output
if (isset($_GET['debug_reviews'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo '<pre>';
    echo "<h3>Debug Information - Reviews</h3>";
    
    // Check if we have a bus ID
    $busId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    echo "Bus ID: " . $busId . "\n\n";
    
    // Check database connection
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Database connection successful\n\n";
        
        // Check if ratings table exists
        $tables = $pdo->query("SHOW TABLES LIKE 'ratings'")->rowCount();
        echo "Ratings table exists: " . ($tables > 0 ? 'Yes' : 'No') . "\n\n";
        
        if ($busId > 0) {
            // Check if there are reviews for this bus
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM ratings WHERE bus_id = ?");
            $stmt->execute([$busId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "Number of reviews for bus $busId: " . $count . "\n\n";
            
            if ($count > 0) {
                // Show sample reviews
                $stmt = $pdo->prepare("SELECT * FROM ratings WHERE bus_id = ? ORDER BY created_at DESC LIMIT 3");
                $stmt->execute([$busId]);
                $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "Sample reviews:\n";
                print_r($reviews);
                
                // Show the query being used
                echo "\nReviews query being used:\n";
                echo "SELECT r.*, COALESCE(u.username, CONCAT('Guest-', r.guest_name)) as username, " . 
                     "COALESCE(u.full_name, r.guest_name) as full_name, " .
                     "(r.driver_rating + r.conductor_rating + r.condition_rating) / 3 as avg_rating, " .
                     "COALESCE(NULLIF(r.comments, ''), r.comment, '') as comment, " .
                     "r.created_at " .
                     "FROM ratings r " .
                     "LEFT JOIN users u ON r.user_id = u.id " .
                     "WHERE r.bus_id = $busId " .
                     "ORDER BY r.created_at DESC " .
                     "LIMIT 10\n\n";
            }
        }
        
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage() . "\n";
    }
    
    echo '</pre>';
    exit();
}

// Debug output
if (isset($_GET['debug'])) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    echo '<pre>';
    echo 'SITE_URL: ' . (defined('SITE_URL') ? SITE_URL : 'Not defined') . "\n";
    echo 'Current file: ' . __FILE__ . "\n";
    echo 'Included files: ' . print_r(get_included_files(), true) . "\n";
    echo '</pre>';
}

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
    // Get bus information with maintenance data and premium status
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            COALESCE(ROUND(AVG((r.driver_rating + r.conductor_rating + r.condition_rating) / 3), 1), 0) as avg_rating,
            COUNT(DISTINCT r.id) as rating_count,
            (
                SELECT GROUP_CONCAT(DISTINCT feature_name) 
                FROM premium_features 
                WHERE bus_id = b.id AND is_active = 1 
                AND (end_date IS NULL OR end_date >= NOW())
            ) as premium_features
        FROM buses b
        LEFT JOIN ratings r ON b.id = r.bus_id
        WHERE b.id = ?
        GROUP BY b.id
    ");
    $stmt->execute([$busId]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug output for bus ID 7
    if ($busId === 7) {
        error_log("Bus ID 7 data: " . print_r($bus, true));
        if (isset($bus['premium_features'])) {
            error_log("Premium features for bus 7: " . $bus['premium_features']);
        } else {
            error_log("No premium features found for bus 7");
        }
    }
    
    // Force display the registration number for debugging
    if ($bus && !empty($bus['registration_number'])) {
        $bus['registration_number'] = trim($bus['registration_number']);
    }
    
    // Debug output
    if (isset($_GET['debug'])) {
        echo '<div class="container mt-4">';
        echo '<div class="card">';
        echo '<div class="card-header bg-warning text-dark">Debug Information</div>';
        echo '<div class="card-body">';
        
        // Show raw bus data
        echo '<h5>Bus Data:</h5>';
        echo '<pre>' . htmlspecialchars(print_r($bus, true)) . '</pre>';
        
        // Test format_registration_number function
        if (!empty($bus['registration_number'])) {
            echo '<h5 class="mt-4">Registration Number Test:</h5>';
            echo '<p>Raw: ' . htmlspecialchars($bus['registration_number']) . '</p>';
            $formatted = format_registration_number($bus['registration_number']);
            echo '<p>After format_registration_number(): ' . htmlspecialchars($formatted) . '</p>';
        }
        
        echo '</div></div></div>';
    }
    
    // Process premium features
    if ($bus) {
        $bus['premium_features'] = [];
        if (!empty($bus['premium_features'])) {
            // Flatten features from all active subscriptions
            $features = [];
            $premiumFeatures = explode(',', $bus['premium_features']);
            foreach ($premiumFeatures as $featureSet) {
                $decoded = json_decode($featureSet, true);
                if (is_array($decoded)) {
                    $features = array_merge($features, $decoded);
                }
            }
            $bus['premium_features'] = array_unique($features);
        }
    }
    
    if (!$bus) {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
    
    // Get reviews for this bus
    $stmt = $pdo->prepare("
        SELECT 
            r.*, 
            COALESCE(u.username, CONCAT('Guest-', r.guest_name)) as username,
            COALESCE(u.full_name, r.guest_name) as full_name,
            (r.driver_rating + r.conductor_rating + r.condition_rating) / 3 as avg_rating,
            COALESCE(NULLIF(r.comments, ''), r.comment, '') as comment,
            r.created_at
        FROM ratings r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.bus_id = ?
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$busId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Output the reviews array
    if (isset($_GET['debug_reviews'])) {
        echo "<h3>Reviews Array:</h3>";
        echo "<pre>";
        print_r($reviews);
        echo "</pre>";
        
        // Check if reviews are being passed to the template
        echo "<h3>Review Count: " . count($reviews) . "</h3>";
        
        // Check the first review's structure
        if (!empty($reviews)) {
            echo "<h3>First Review Structure:</h3>";
            echo "<pre>";
            print_r($reviews[0]);
            echo "</pre>";
            
            // Check if comment field exists
            echo "<h3>Comment Field Check:</h3>";
            echo "Comment field exists: " . (isset($reviews[0]['comment']) ? 'Yes' : 'No') . "<br>";
            echo "Comments field exists: " . (isset($reviews[0]['comments']) ? 'Yes' : 'No') . "<br>";
            
            // Show all fields in the first review
            echo "<h3>All Fields in First Review:</h3>";
            echo "<pre>";
            foreach ($reviews[0] as $key => $value) {
                echo "$key: " . (is_null($value) ? 'NULL' : "'$value'") . "<br>";
            }
            echo "</pre>";
        }
    }
    
    // Check if user has already rated this bus (only if logged in)
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("
            SELECT * FROM ratings 
            WHERE bus_id = ? AND user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$busId, $_SESSION['user_id']]);
        $userRating = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("Bus details error: " . $e->getMessage());
    $error = "An error occurred while loading bus details. Please try again later.";
}

// Process rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    if (isset($_POST['submit_rating'])) {
        $driverRating = isset($_POST['driver_rating']) ? (int)$_POST['driver_rating'] : 0;
        $conductorRating = isset($_POST['conductor_rating']) ? (int)$_POST['conductor_rating'] : 0;
        $conditionRating = isset($_POST['condition_rating']) ? (int)$_POST['condition_rating'] : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        
        // Validate ratings
        if ($driverRating < 1 || $driverRating > 5 || 
            $conductorRating < 1 || $conductorRating > 5 || 
            $conditionRating < 1 || $conditionRating > 5) {
            $error = "Please provide a rating between 1 and 5 for all categories.";
        } else {
            try {
                $pdo->beginTransaction();
                
                if ($userRating) {
                    // Update existing rating
                    $stmt = $pdo->prepare("
                        UPDATE ratings 
                        SET driver_rating = ?, 
                            conductor_rating = ?, 
                            condition_rating = ?,
                            comment = ?,
                            created_at = NOW()
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([
                        $driverRating, 
                        $conductorRating, 
                        $conditionRating,
                        $comment,
                        $userRating['id'],
                        $_SESSION['user_id']
                    ]);
                    
                    $successMessage = "Your rating has been updated successfully!";
                } else {
                    // Create new rating
                    $stmt = $pdo->prepare("
                        INSERT INTO ratings 
                        (bus_id, user_id, driver_rating, conductor_rating, condition_rating, comment, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $busId, 
                        $_SESSION['user_id'], 
                        $driverRating, 
                        $conductorRating, 
                        $conditionRating,
                        $comment
                    ]);
                    
                    $ratingId = $pdo->lastInsertId();
                    $successMessage = "Thank you for your rating!";
                    
                    // If there's a review, create it as well
                    $reviewTitle = isset($_POST['review_title']) ? trim($_POST['review_title']) : '';
                    $reviewContent = isset($_POST['review_content']) ? trim($_POST['review_content']) : '';
                    
                    if (!empty($reviewTitle) && !empty($reviewContent)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO reviews 
                            (bus_id, user_id, title, content, rating_id, is_approved, created_at)
                            VALUES (?, ?, ?, ?, ?, 0, NOW())
                        ");
                        $stmt->execute([
                            $busId,
                            $_SESSION['user_id'],
                            $reviewTitle,
                            $reviewContent,
                            $ratingId
                        ]);
                        
                        $successMessage .= " Your review is pending approval.";
                    }
                }
                
                // Log the activity
                log_activity($_SESSION['user_id'], 'bus_rated', "Rated bus #$busId");
                
                $pdo->commit();
                
                // Refresh the page to show updated data
                header("Location: " . SITE_URL . "/bus.php?id=$busId&success=1");
                exit();
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Rating submission error: " . $e->getMessage());
                $error = "An error occurred while submitting your rating. Please try again.";
            }
        }
    }
}

// Set page title with registration number
if (!empty($bus['registration_number'])) {
    $page_title = 'Bus ' . htmlspecialchars(trim($bus['registration_number'])) . ' - ' . SITE_NAME;
} else {
    $page_title = 'Bus #' . $busId . ' - ' . SITE_NAME;
}
?>

<?php include 'includes/header.php'; ?>

<style>
/* Bus Details Page Specific Styles */
.bus-details-container {
    padding-top: 30px;
    padding-bottom: 50px;
}

.bus-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #e9ecef;
}

.bus-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.rating-display {
    font-size: 2rem;
    font-weight: 700;
    color: #f1c40f;
    margin: 10px 0;
}

.rating-count {
    color: #6c757d;
    font-size: 0.9rem;
}

.bus-info {
    padding: 1.5rem;
}

.bus-info h1 {
    font-size: 1.75rem;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.bus-meta {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
}

.meta-item i {
    color: #3498db;
}

.rating-section {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.rating-input {
    direction: rtl;
    unicode-bidi: bidi-override;
    text-align: left;
    margin: 1rem 0;
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input label {
    color: #ddd;
    font-size: 2rem;
    padding: 0 5px;
    cursor: pointer;
    transition: color 0.2s;
}

.rating-input label:hover,
.rating-input label:hover ~ label,
.rating-input input[type="radio"]:checked ~ label {
    color: #ffc107;
}

.review-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    align-items: center;
}

.reviewer {
    font-weight: 600;
    color: #2c3e50;
}

.review-date {
    color: #6c757d;
    font-size: 0.875rem;
}

.review-rating {
    color: #f1c40f;
    margin-bottom: 0.5rem;
}

.review-text {
    color: #495057;
    line-height: 1.6;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .bus-meta {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .bus-info h1 {
        font-size: 1.5rem;
    }
    
    .rating-input label {
        font-size: 1.75rem;
    }
}
</style>

<main class="bus-details-container">
<div class="container">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['from_search']) && isset($_SESSION['last_search'])): ?>
        <div class="alert alert-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-search me-2"></i>
                    Showing results for: <strong><?php echo htmlspecialchars($_SESSION['last_search']); ?></strong>
                </div>
                <a href="search.php?q=<?php echo urlencode($_SESSION['last_search']); ?>" class="btn btn-sm btn-outline-info">
                    Back to search results
                </a>
            </div>
        </div>
        <?php unset($_SESSION['last_search']); ?>
    <?php endif; ?>
    
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success">
            <?php echo $successMessage ?: 'Thank you for your rating!' ?>
        </div>
    <?php endif; ?>
    
    <!-- Bus Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-md-center">
                <div class="me-md-4 mb-3 mb-md-0 text-center">
                    <div class="bus-icon" style="font-size: 4rem; color: #6c757d;">
                        <i class="fas fa-bus"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <h1 class="mb-0 me-2">
                                <?php 
                                // Start with registration number (always shown if available)
                                $displayParts = [];
                                
                                if (!empty($bus['registration_number'])) {
                                    $displayParts[] = htmlspecialchars(trim($bus['registration_number']));
                                } else {
                                    $displayParts[] = 'Bus #' . $busId;
                                }
                                
                                // Add bus name if premium feature is enabled
                                if (in_array('display_bus_name', $bus['premium_features'] ?? []) && !empty($bus['bus_name'])) {
                                    $displayParts[] = htmlspecialchars($bus['bus_name']);
                                }
                                
                                // Add company name if premium feature is enabled
                                if (in_array('display_company_name', $bus['premium_features'] ?? []) && !empty($bus['company_name'])) {
                                    $displayParts[] = htmlspecialchars($bus['company_name']);
                                }
                                
                                // Join all parts with ' - ' separator
                                echo implode(' - ', $displayParts);
                                ?>
                            </h1>
                            
                            <?php if (!empty($bus['route_number'])): ?>
                                <span class="badge bg-primary">
                                    <i class="fas fa-route me-1"></i>Route <?php echo htmlspecialchars($bus['route_number']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($bus['type'])): ?>
                                <span class="badge bg-<?php echo $bus['type'] === 'government' ? 'success' : 'info'; ?>">
                                    <i class="fas fa-<?php echo $bus['type'] === 'government' ? 'university' : 'bus'; ?> me-1"></i>
                                    <?php echo ucfirst($bus['type']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($bus['premium_features'])): ?>
                                <span class="badge bg-warning text-dark" data-bs-toggle="tooltip" title="Premium Features Active">
                                    <i class="fas fa-crown me-1"></i>Premium
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <div class="rating-display">
                                <?php 
                                $ratingCount = isset($bus['rating_count']) ? (int)$bus['rating_count'] : 0;
                                $avgRating = isset($bus['avg_rating']) ? (float)$bus['avg_rating'] : 0.0;
                                
                                if ($ratingCount > 0 && $avgRating > 0): ?>
                                    <span class="display-4 fw-bold text-warning">
                                        <?php echo number_format($avgRating, 1); ?>
                                    </span>
                                    <span class="text-muted">/ 5.0</span>
                                    <div class="stars mb-1">
                                        <?php 
                                            $fullStars = floor($avgRating);
                                            $hasHalfStar = ($avgRating - $fullStars) >= 0.5;
                                            
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $fullStars) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } elseif ($i == $fullStars + 1 && $hasHalfStar) {
                                                    echo '<i class="fas fa-star-half-alt"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                        ?>
                                    </div>
                                    <small class="text-muted">
                                        Based on <?php echo $ratingCount; ?> rating<?php echo $ratingCount !== 1 ? 's' : ''; ?>
                                    </small>
                                <?php else: ?>
                                    <div class="text-muted">No ratings yet</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($bus['route_description'])): ?>
                        <p class="lead mb-0">
                            <i class="fas fa-route text-primary me-2"></i>
                            <?php echo htmlspecialchars($bus['route_description']); ?>
                        </p>
                    <?php endif; ?>
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
                            <p class="mb-3">Rate this bus as a guest or log in to save your rating</p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="rate-bus.php?bus_id=<?php echo $busId; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-star me-2"></i> Rate as Guest
                                </a>
                                <a href="login.php?redirect=<?php echo urlencode("/bus.php?id=$busId"); ?>" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i> Log In
                                </a>
                            </div>
                            <p class="mt-3 small text-muted">
                                Don't have an account? 
                                <a href="register.php" class="text-primary">Sign up</a>
                            </p>
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
                                        <label for="driver_<?php echo $i; ?>" title="<?php echo $i; ?> stars">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                
                                <h6>Conductor</h6>
                                <div class="rating-input mb-3" data-target="conductor_rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="conductor_<?php echo $i; ?>" name="conductor_rating" 
                                               value="<?php echo $i; ?>"
                                               <?php echo ($userRating && $userRating['conductor_rating'] == $i) ? 'checked' : ''; ?>>
                                        <label for="conductor_<?php echo $i; ?>" title="<?php echo $i; ?> stars">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                
                                <h6>Bus Condition</h6>
                                <div class="rating-input mb-3" data-target="condition_rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="condition_<?php echo $i; ?>" name="condition_rating" 
                                               value="<?php echo $i; ?>"
                                               <?php echo ($userRating && $userRating['condition_rating'] == $i) ? 'checked' : ''; ?>>
                                        <label for="condition_<?php echo $i; ?>" title="<?php echo $i; ?> stars">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="comment" class="form-label">Comments (Optional)</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" 
                                          placeholder="Share your experience..."><?php 
                                    echo $userRating ? htmlspecialchars($userRating['comment']) : ''; 
                                ?></textarea>
                            </div>
                            
                            <div id="review-section" class="mb-3" style="display: none;">
                                <div class="mb-3">
                                    <label for="review_title" class="form-label">Review Title</label>
                                    <input type="text" class="form-control" id="review_title" name="review_title" 
                                           placeholder="Summarize your experience">
                                </div>
                                <div class="mb-3">
                                    <label for="review_content" class="form-label">Your Review</label>
                                    <textarea class="form-control" id="review_content" name="review_content" 
                                              rows="4" placeholder="Tell us about your experience..."></textarea>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" name="submit_rating" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    <?php echo $userRating ? 'Update Rating' : 'Submit Rating'; ?>
                                </button>
                                
                                <button type="button" id="toggle-review" class="btn btn-outline-secondary btn-sm">
                                    <i class="far fa-edit me-1"></i>
                                    <span>Write a Review</span>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Maintenance Section -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Maintenance Records
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Current Mileage -->
                        <?php if (!empty($bus['current_mileage'])): ?>
                        <div class="col-12 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-tachometer-alt text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Current Mileage</h6>
                                    <p class="mb-0">
                                        <?php echo number_format($bus['current_mileage']); ?> km
                                        <?php if (!empty($bus['mileage_recorded_date'])): ?>
                                            <br><small class="text-muted">Recorded on <?php echo date('M j, Y', strtotime($bus['mileage_recorded_date'])); ?></small>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Inspection -->
                        <?php if (!empty($bus['last_inspection_date'])): ?>
                        <div class="col-12 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-clipboard-check text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Inspection</h6>
                                    <p class="mb-0">
                                        Last: <?php echo date('M j, Y', strtotime($bus['last_inspection_date'])); ?>
                                        <?php if (!empty($bus['last_inspection_mileage'])): ?>
                                            <br>Mileage: <?php echo number_format($bus['last_inspection_mileage']); ?> km
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Tyres -->
                        <?php if (!empty($bus['last_tyre_change_date'])): ?>
                        <div class="col-12 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-tire text-info"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Tyres</h6>
                                    <p class="mb-0">
                                        Last changed: <?php echo date('M j, Y', strtotime($bus['last_tyre_change_date'])); ?>
                                        <?php if (!empty($bus['last_tyre_change_mileage'])): ?>
                                            <br>Mileage: <?php echo number_format($bus['last_tyre_change_mileage']); ?> km
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Brake Liners -->
                        <?php if (!empty($bus['last_brake_liner_change_date'])): ?>
                        <div class="col-12 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-danger bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-stop-circle text-danger"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Brake Liners</h6>
                                    <p class="mb-0">
                                        Last changed: <?php echo date('M j, Y', strtotime($bus['last_brake_liner_change_date'])); ?>
                                        <?php if (!empty($bus['last_brake_liner_mileage'])): ?>
                                            <br>Mileage: <?php echo number_format($bus['last_brake_liner_mileage']); ?> km
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Battery -->
                        <?php if (!empty($bus['last_battery_change_date'])): ?>
                        <div class="col-12 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-battery-three-quarters text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Battery</h6>
                                    <p class="mb-0">
                                        Last changed: <?php echo date('M j, Y', strtotime($bus['last_battery_change_date'])); ?>
                                        <?php if (!empty($bus['last_battery_change_mileage'])): ?>
                                            <br>Mileage: <?php echo number_format($bus['last_battery_change_mileage']); ?> km
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Oil Change -->
                        <?php if (!empty($bus['last_oil_change_date'])): ?>
                        <div class="col-12 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-oil-can text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Oil Change</h6>
                                    <p class="mb-0">
                                        Last: <?php echo date('M j, Y', strtotime($bus['last_oil_change_date'])); ?>
                                        <?php if (!empty($bus['last_oil_change_mileage'])): ?>
                                            <br>Mileage: <?php echo number_format($bus['last_oil_change_mileage']); ?> km
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($bus) && isset($_SESSION['user_id'], $bus['user_id']) && $_SESSION['user_id'] == $bus['user_id']): ?>
                        <div class="mt-3 text-end">
                            <a href="/BS/bus-owner/update_maintenance.php?bus_id=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit me-1"></i> Update Maintenance
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rating Breakdown -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Rating Breakdown</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $ratingCategories = [
                        'driver_rating' => 'Driver',
                        'conductor_rating' => 'Conductor',
                        'condition_rating' => 'Bus Condition'
                    ];
                    
                    foreach ($ratingCategories as $field => $label): 
                        $avg = (!empty($bus) && !empty($bus['rating_count'])) 
                            ? $pdo->query("SELECT AVG($field) as avg FROM ratings WHERE bus_id = " . (int)$busId)->fetch()['avg'] 
                            : 0;
                    ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?php echo $label; ?></span>
                                <span class="text-muted"><?php echo number_format($avg, 1); ?>/5.0</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: <?php echo ($avg / 5) * 100; ?>%" 
                                     aria-valuenow="<?php echo $avg; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="5">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Reviews -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="far fa-comments me-2"></i>
                        Recent Reviews
                    </h5>
                    <span class="badge bg-primary rounded-pill">
                        <?php echo count($reviews); ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="far fa-comment-alt text-muted" style="font-size: 2.5rem;"></i>
                            </div>
                            <p class="text-muted mb-0">No reviews yet. Be the first to review this bus!</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($reviews as $review): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <?php 
                                                    if (!empty($review['full_name'])) {
                                                        echo htmlspecialchars($review['full_name']);
                                                    } elseif (!empty($review['username'])) {
                                                        echo htmlspecialchars($review['username']);
                                                    } else {
                                                        echo 'Guest';
                                                    }
                                                ?>
                                            </h6>
                                            <div class="text-warning mb-1">
                                                <?php 
                                                    $avgReviewRating = round($review['avg_rating']);
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo $i <= $avgReviewRating 
                                                            ? '<i class="fas fa-star"></i> ' 
                                                            : '<i class="far fa-star"></i> ';
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                        <?php if (isset($_SESSION['user_id']) && (isset($review['user_id']) && $_SESSION['user_id'] == $review['user_id'])): ?>
                                            <a href="#" class="text-muted" data-bs-toggle="tooltip" title="Edit review">
                                                <i class="far fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php 
                                        // Get the comment from either 'comment' or 'comments' field
                                        $comment = !empty($review['comment']) ? $review['comment'] : 
                                                 (!empty($review['comments']) ? $review['comments'] : '');
                                    ?>
                                    <?php if (!empty($comment)): ?>
                                        <p class="mb-2 mt-2"><?php echo nl2br(htmlspecialchars($comment)); ?></p>
                                    <?php endif; ?>
                                    <div class="text-muted small">
                                        <i class="far fa-clock"></i> <?php echo date('M j, Y g:i a', strtotime($review['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($reviews) >= 10): ?>
                            <div class="text-center mt-3">
                                <a href="reviews.php?bus_id=<?php echo $busId; ?>" class="btn btn-outline-primary btn-sm">
                                    View All Reviews
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

    border-left: none;
    border-right: none;
    padding: 1.25rem 1.5rem;
}

.list-group-item:first-child {
    border-top: none;
    padding-top: 0;
}

.list-group-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

@media (max-width: 991.98px) {
    .card {
        margin-bottom: 1.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle review form
    const toggleReviewBtn = document.getElementById('toggle-review');
    const reviewSection = document.getElementById('review-section');
    
    if (toggleReviewBtn && reviewSection) {
        toggleReviewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const isHidden = reviewSection.style.display === 'none';
            reviewSection.style.display = isHidden ? 'block' : 'none';
            this.querySelector('span').textContent = isHidden ? 'Cancel' : 'Write a Review';
            this.classList.toggle('btn-outline-secondary', !isHidden);
            this.classList.toggle('btn-outline-danger', isHidden);
        });
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
