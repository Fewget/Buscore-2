<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get bus ID from URL
$busId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($busId <= 0) {
    header('Location: index.php');
    exit();
}

$bus = null;
$reviews = [];
$error = '';

// Get bus details with ratings
try {
    // Get bus information with average rating and count
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            COALESCE(ROUND(AVG((r.driver_rating + r.conductor_rating + r.condition_rating) / 3), 1), 0) as avg_rating,
            COUNT(r.id) as rating_count
        FROM buses b
        LEFT JOIN ratings r ON b.id = r.bus_id
        WHERE b.id = ?
        GROUP BY b.id
    ");
    $stmt->execute([$busId]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        $error = 'Bus not found';
    } else {
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
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Set page title
$page_title = 'Bus ' . (!empty($bus['registration_number']) ? htmlspecialchars($bus['registration_number']) : '#' . $busId) . ' - ' . SITE_NAME;

include 'includes/header.php';
?>

<div class="container py-5" style="margin-top: 100px;">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php elseif (!$bus): ?>
        <div class="alert alert-warning">Bus not found.</div>
    <?php else: ?>
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
                                <div class="d-flex flex-column" style="gap: 0.5rem;">
                                    <h1 class="mb-0" style="font-size: 2.8rem; font-weight: 800; line-height: 1.1;">
                                        <?php 
                                        // Show registration number by default
                                        echo !empty($bus['registration_number']) 
                                            ? htmlspecialchars($bus['registration_number'])
                                            : 'Bus #' . $busId;
                                        ?>
                                    </h1>
                                    <div class="d-flex flex-column gap-3">
                                        <?php if (!empty($bus['company_name'])): ?>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-secondary align-self-start" style="font-size: 1.5rem; padding: 0.6em 1em; border-radius: 0.5rem; line-height: 1.2;">
                                                    <i class="fas fa-building me-2"></i> <?php echo htmlspecialchars($bus['company_name']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($bus['bus_name'])): ?>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-primary align-self-start" style="font-size: 1.5rem; padding: 0.6em 1em; border-radius: 0.5rem; line-height: 1.2;">
                                                    <i class="fas fa-bus me-2"></i> <?php echo htmlspecialchars($bus['bus_name']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 mt-md-0">
                                <div class="d-flex flex-column align-items-end">
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="fw-bold" style="font-size: 2.5rem; line-height: 1;"><?php echo number_format($bus['avg_rating'], 1); ?></span>
                                        <span class="text-muted ms-1" style="font-size: 1.5rem; line-height: 1;">/ 5</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="d-flex">
                                            <?php 
                                            $ratingCount = (int)$bus['rating_count'];
                                            $avgRating = (float)$bus['avg_rating'];
                                            $fullStars = floor($avgRating);
                                            $hasHalfStar = ($avgRating - $fullStars) >= 0.5;
                                            $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                                            
                                            // Full stars
                                            for ($i = 0; $i < $fullStars; $i++) {
                                                echo '<i class="fas fa-star text-warning" style="font-size: 1.8rem; margin: 0 2px;"></i>';
                                            }
                                            
                                            // Half star
                                            if ($hasHalfStar) {
                                                echo '<i class="fas fa-star-half-alt text-warning" style="font-size: 1.8rem; margin: 0 2px;"></i>';
                                            }
                                            
                                            // Empty stars
                                            for ($i = 0; $i < $emptyStars; $i++) {
                                                echo '<i class="far fa-star text-warning" style="font-size: 1.8rem; margin: 0 2px;"></i>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <span class="text-muted ms-2">(<?php echo $ratingCount; ?> ratings)</span>
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
            <!-- Rating Breakdown -->
            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Rating Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get average ratings for each category
                        $stmt = $pdo->prepare("
                            SELECT 
                                ROUND(AVG(driver_rating), 1) as driver_rating,
                                ROUND(AVG(conductor_rating), 1) as conductor_rating,
                                ROUND(AVG(condition_rating), 1) as condition_rating,
                                COUNT(*) as total_ratings
                            FROM ratings 
                            WHERE bus_id = ?
                        ") or die(print_r($pdo->errorInfo(), true));
                        $stmt->execute([$busId]);
                        $avgRatings = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        $categories = [
                            'driver_rating' => 'Driver',
                            'conductor_rating' => 'Conductor',
                            'condition_rating' => 'Bus Condition'
                        ];
                        
                        foreach ($categories as $field => $label):
                            $rating = $avgRatings[$field] ?? 0;
                            $width = ($rating / 5) * 100;
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-medium"><?php echo htmlspecialchars($label); ?></span>
                                    <span class="text-muted"><?php echo number_format($rating, 1); ?>/5</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?php echo $width; ?>%" 
                                         aria-valuenow="<?php echo $width; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Rating Distribution</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get rating distribution for each category
                        $categories = [
                            'driver' => 'Driver',
                            'conductor' => 'Conductor',
                            'condition' => 'Bus Condition'
                        ];
                        
                        foreach ($categories as $category => $label):
                            $stmt = $pdo->prepare("
                                SELECT 
                                    {$category}_rating as rating,
                                    COUNT(*) as count,
                                    ROUND((COUNT(*) * 100.0) / (SELECT COUNT(*) FROM ratings WHERE bus_id = ?), 1) as percentage
                                FROM ratings 
                                WHERE bus_id = ?
                                GROUP BY {$category}_rating
                                ORDER BY rating DESC
                            ") or die(print_r($pdo->errorInfo(), true));
                            $stmt->execute([$busId, $busId]);
                            $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Initialize all possible ratings (1-5)
                            $ratings = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                            $percentages = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                            
                            // Fill in actual counts and percentages
                            foreach ($distribution as $row) {
                                $ratings[(int)$row['rating']] = (int)$row['count'];
                                $percentages[(int)$row['rating']] = (float)$row['percentage'];
                            }
                            ?>
                            <div class="mb-4">
                                <h6 class="mb-3"><?php echo htmlspecialchars($label); ?></h6>
                                <?php for ($i = 5; $i >= 1; $i--): 
                                    $count = $ratings[$i] ?? 0;
                                    $percentage = $percentages[$i] ?? 0;
                                    $width = $percentage > 0 ? $percentage : 2; // Minimum width for visibility
                                    ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="text-nowrap me-2" style="width: 30px;">
                                            <?php echo $i; ?> <i class="fas fa-star text-warning"></i>
                                        </div>
                                        <div class="progress flex-grow-1" style="height: 20px;">
                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                 style="width: <?php echo $width; ?>%" 
                                                 aria-valuenow="<?php echo $width; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <span class="visually-hidden"><?php echo $percentage; ?>%</span>
                                            </div>
                                        </div>
                                        <div class="ms-2 text-muted" style="min-width: 50px; text-align: right;">
                                            <?php echo $count; ?> (<?php echo $percentage; ?>%)
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <?php if ($category !== 'condition'): ?>
                                <hr class="my-4">
                            <?php endif; ?>
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
                                <?php foreach ($reviews as $review): 
                                    $comment = !empty($review['comment']) ? $review['comment'] : 
                                              (!empty($review['comments']) ? $review['comments'] : '');
                                    $reviewerName = !empty($review['full_name']) ? $review['full_name'] : 
                                                  (!empty($review['username']) ? $review['username'] : 'Guest');
                                    $avgRating = round(($review['driver_rating'] + $review['conductor_rating'] + $review['condition_rating']) / 3);
                                ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between mb-2">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($reviewerName); ?></h6>
                                                <div class="text-warning mb-1">
                                                    <?php 
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo $i <= $avgRating 
                                                            ? '<i class="fas fa-star"></i> ' 
                                                            : '<i class="far fa-star"></i> ';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                            </small>
                                        </div>
                                        <?php if (!empty($comment)): ?>
                                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($comment)); ?></p>
                                        <?php endif; ?>
                                        <div class="d-flex small text-muted">
                                            <div class="me-3">
                                                <i class="fas fa-user-tie me-1"></i>
                                                Driver: <?php echo $review['driver_rating']; ?>/5
                                            </div>
                                            <div class="me-3">
                                                <i class="fas fa-user-friends me-1"></i>
                                                Conductor: <?php echo $review['conductor_rating']; ?>/5
                                            </div>
                                            <div>
                                                <i class="fas fa-bus me-1"></i>
                                                Condition: <?php echo $review['condition_rating']; ?>/5
                                            </div>
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
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
