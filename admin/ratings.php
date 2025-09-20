<?php
// Include admin config which will handle all required includes and authentication
require_once __DIR__ . '/includes/config.php';

// Set page title
$page_title = 'Manage Ratings - ' . SITE_NAME;

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of ratings
$total_ratings = $pdo->query("SELECT COUNT(*) FROM ratings")->fetchColumn();
$total_pages = ceil($total_ratings / $records_per_page);

// Get ratings with user and bus info (using LEFT JOIN to include all ratings)
$stmt = $pdo->prepare("
    SELECT 
        r.*, 
        COALESCE(u.username, 'User Deleted') as username, 
        b.registration_number, 
        b.route_number,
        (r.driver_rating + r.conductor_rating + r.condition_rating) / 3 as avg_rating
    FROM ratings r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN buses b ON r.bus_id = b.id
    ORDER BY r.created_at DESC
    LIMIT :offset, :limit
");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid ratings-page">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Ratings</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">All Ratings</h6>
                <span class="badge bg-primary"><?php echo number_format($total_ratings); ?> total</span>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($ratings)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Bus</th>
                                <th>Rating</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ratings as $rating): ?>
                                <tr>
                                    <td>#<?php echo $rating['id']; ?></td>
                                    <td><?php echo htmlspecialchars($rating['username']); ?></td>
                                    <td>
                                        <?php 
                                        $busInfo = [];
                                        if (!empty($rating['registration_number'])) {
                                            $busInfo[] = htmlspecialchars($rating['registration_number']);
                                        }
                                        if (!empty($rating['route_number'])) {
                                            $busInfo[] = 'Route ' . htmlspecialchars($rating['route_number']);
                                        }
                                        echo implode(' - ', $busInfo);
                                        ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rating-stars">
                                                <?php
                                                $ratingValue = $rating['avg_rating'] ?? 0;
                                                $fullStars = floor((float)$ratingValue);
                                                $hasHalfStar = ($ratingValue - $fullStars) >= 0.5;
                                                $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                                                
                                                // Full stars
                                                for ($i = 0; $i < $fullStars; $i++) {
                                                    echo '<i class="fas fa-star text-warning"></i>';
                                                }
                                                
                                                // Half star
                                                if ($hasHalfStar) {
                                                    echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                                }
                                                
                                                // Empty stars
                                                for ($i = 0; $i < $emptyStars; $i++) {
                                                    echo '<i class="far fa-star text-warning"></i>';
                                                }
                                                ?>
                                            </div>
                                            <span class="ms-2"><?php echo number_format((float)$ratingValue, 1); ?>/5.0</span>
                                        </div>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($rating['created_at'])); ?></td>
                                    <td>
                                        <a href="view-rating.php?id=<?php echo $rating['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="delete-rating.php?id=<?php echo $rating['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this rating?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="px-3 py-2">
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1); ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1); ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="text-center p-5">
                    <div class="mb-3">
                        <i class="far fa-star fa-4x text-muted"></i>
                    </div>
                    <h5>No ratings found</h5>
                    <p class="text-muted">There are no ratings in the system yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.rating-stars {
    color: #ffc107;
    font-size: 0.9rem;
    white-space: nowrap;
}
</style>
