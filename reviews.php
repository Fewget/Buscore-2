<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if bus ID is provided
if (!isset($_GET['bus_id']) || !is_numeric($_GET['bus_id'])) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$busId = (int)$_GET['bus_id'];
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get bus information
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.registration_number,
            b.route_number,
            b.type,
            COALESCE(AVG((r.driver_rating + r.conductor_rating + r.condition_rating) / 3), 0) as avg_rating,
            COUNT(DISTINCT r.id) as rating_count
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
    
    // Get reviews count for pagination
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM reviews 
        WHERE bus_id = ? AND is_approved = 1
    ");
    $stmt->execute([$busId]);
    $totalReviews = $stmt->fetchColumn();
    $totalPages = ceil($totalReviews / $perPage);
    
    // Get reviews with user info
    $stmt = $pdo->prepare("
        SELECT 
            r.*, 
            u.username, 
            u.full_name,
            (rt.driver_rating + rt.conductor_rating + rt.condition_rating) / 3 as avg_rating
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        JOIN ratings rt ON r.rating_id = rt.id
        WHERE r.bus_id = ? AND r.is_approved = 1
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $busId, PDO::PARAM_INT);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Reviews page error: " . $e->getMessage());
    $error = "An error occurred while loading reviews. Please try again later.";
}

// Set page title
$page_title = 'Reviews for Bus ' . htmlspecialchars($bus['registration_number']) . ' - ' . SITE_NAME;
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <!-- Back to Bus Link -->
    <div class="mb-4">
        <a href="bus.php?id=<?php echo $busId; ?>" class="text-decoration-none">
            <i class="fas fa-arrow-left me-2"></i> Back to Bus Details
        </a>
    </div>
    
    <!-- Bus Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-md-center">
                <div class="me-md-4 mb-3 mb-md-0 text-center">
                    <div class="bus-icon" style="font-size: 3rem; color: #6c757d;">
                        <i class="fas fa-bus"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <h1 class="h3 mb-1">
                        <?php echo htmlspecialchars($bus['registration_number']); ?>
                        <?php if (!empty($bus['route_number'])): ?>
                            <span class="badge bg-primary ms-2">
                                Route <?php echo htmlspecialchars($bus['route_number']); ?>
                            </span>
                        <?php endif; ?>
                        <span class="badge bg-<?php echo $bus['type'] === 'government' ? 'success' : 'info'; ?>">
                            <?php echo ucfirst($bus['type']); ?>
                        </span>
                    </h1>
                    
                    <div class="d-flex align-items-center mb-2">
                        <div class="rating-display me-3">
                            <?php if ($bus['rating_count'] > 0): ?>
                                <span class="h3 fw-bold text-warning">
                                    <?php echo number_format($bus['avg_rating'], 1); ?>
                                </span>
                                <span class="text-muted">/ 5.0</span>
                                <div class="stars mb-1">
                                    <?php 
                                        $fullStars = floor($bus['avg_rating']);
                                        $hasHalfStar = ($bus['avg_rating'] - $fullStars) >= 0.5;
                                        
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
                                    Based on <?php echo $bus['rating_count']; ?> rating<?php echo $bus['rating_count'] !== 1 ? 's' : ''; ?>
                                </small>
                            <?php else: ?>
                                <span class="text-muted">No ratings yet</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ms-auto">
                            <a href="bus.php?id=<?php echo $busId; ?>#rating-form" class="btn btn-primary">
                                <i class="fas fa-star me-1"></i> Write a Review
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reviews List -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">
                    <i class="far fa-comments me-2"></i>
                    All Reviews
                </h2>
                <span class="badge bg-primary rounded-pill">
                    <?php echo $totalReviews; ?>
                </span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($reviews)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="far fa-comment-alt text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="h5">No reviews yet</h3>
                    <p class="text-muted">
                        Be the first to review this bus!
                    </p>
                    <a href="bus.php?id=<?php echo $busId; ?>#rating-form" class="btn btn-primary mt-2">
                        <i class="fas fa-star me-1"></i> Write a Review
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="border-bottom pb-4 mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                <h3 class="h5 mb-1">
                                    <?php echo htmlspecialchars($review['title']); ?>
                                </h3>
                                <div class="text-warning mb-2">
                                    <?php 
                                        $avgReviewRating = round($review['avg_rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $avgReviewRating 
                                                ? '<i class="fas fa-star"></i>' 
                                                : '<i class="far fa-star"></i>';
                                            echo ' ';
                                        }
                                    ?>
                                    <span class="text-muted ms-1">
                                        <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary rounded-circle" type="button" 
                                        id="reviewDropdown<?php echo $review['id']; ?>" 
                                        data-bs-toggle="dropdown" aria-expanded="false"
                                        style="width: 32px; height: 32px; line-height: 1;">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" 
                                    aria-labelledby="reviewDropdown<?php echo $review['id']; ?>">
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id']): ?>
                                        <li>
                                            <a class="dropdown-item" href="#">
                                                <i class="far fa-edit me-2"></i> Edit Review
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#">
                                                <i class="far fa-trash-alt me-2"></i> Delete
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li>
                                            <a class="dropdown-item" href="#">
                                                <i class="far fa-flag me-2"></i> Report
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <?php echo nl2br(htmlspecialchars($review['content'])); ?>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 40px; height: 40px; font-size: 1.1rem;">
                                    <?php 
                                        $name = !empty($review['full_name']) ? $review['full_name'] : $review['username'];
                                        echo strtoupper(substr($name, 0, 1));
                                    ?>
                                </div>
                            </div>
                            <div>
                                <div class="fw-medium">
                                    <?php 
                                        echo !empty($review['full_name']) 
                                            ? htmlspecialchars($review['full_name'])
                                            : htmlspecialchars($review['username']);
                                    ?>
                                </div>
                                <small class="text-muted">
                                    <?php 
                                        $reviewCount = $pdo->query("
                                            SELECT COUNT(*) as count 
                                            FROM reviews 
                                            WHERE user_id = {$review['user_id']}
                                        ")->fetch()['count'];
                                        
                                        echo $reviewCount . ' review' . ($reviewCount !== 1 ? 's' : '');
                                    ?>
                                </small>
                            </div>
                            
                            <div class="ms-auto">
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="far fa-thumbs-up me-1"></i> Helpful
                                    <span class="badge bg-secondary rounded-pill ms-1">0</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" 
                                   href="?bus_id=<?php echo $busId; ?>&page=<?php echo $page - 1; ?>" 
                                   aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?bus_id=<?php echo $busId; ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" 
                                   href="?bus_id=<?php echo $busId; ?>&page=<?php echo $page + 1; ?>" 
                                   aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.avatar {
    background-color: #6c757d;
    color: white;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.rating-display .h3 {
    font-weight: 700;
}

.stars {
    color: #ffc107;
    font-size: 1.1rem;
    letter-spacing: 2px;
}

.dropdown-toggle::after {
    display: none;
}

.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.pagination .page-link {
    color: #0d6efd;
}

.pagination .page-item.disabled .page-link {
    color: #6c757d;
}

@media (max-width: 575.98px) {
    .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .pagination .page-link {
        padding: 0.25rem 0.5rem;
    }
}
</style>
