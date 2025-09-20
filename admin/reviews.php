<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

$message = '';

// Handle review approval/rejection
if (isset($_POST['update_review'])) {
    try {
        $review_id = $_POST['review_id'];
        $status = $_POST['status'];
        $admin_comment = $_POST['admin_comment'] ?? null;
        
        $stmt = $pdo->prepare("UPDATE reviews 
                              SET is_approved = ?,
                                  updated_at = NOW()
                              WHERE id = ?");
        $stmt->execute([$status === 'approved' ? 1 : 0, $review_id]);
        
        // Also update the associated rating if it exists
        $stmt = $pdo->prepare("UPDATE ratings 
                              SET is_approved = ?,
                                  admin_comment = ?,
                                  updated_at = NOW()
                              WHERE id = (SELECT rating_id FROM reviews WHERE id = ?)");
        $stmt->execute([$status === 'approved' ? 1 : 0, $admin_comment, $review_id]);
        
        $message = '<div class="alert alert-success">Review updated successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error updating review: ' . $e->getMessage() . '</div>';
    }
}

// Handle review deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // First get the rating_id to delete the associated rating
        $stmt = $pdo->prepare("SELECT rating_id FROM reviews WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the review
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        
        // Delete the associated rating if it exists
        if (!empty($result['rating_id'])) {
            $stmt = $pdo->prepare("DELETE FROM ratings WHERE id = ?");
            $stmt->execute([$result['rating_id']]);
        }
        
        $message = '<div class="alert alert-success">Review deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting review: ' . $e->getMessage() . '</div>';
    }
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$bus_id = $_GET['bus_id'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$search = $_GET['search'] ?? '';

// Build query to get reviews with ratings
try {
    $query = "SELECT r.*, b.registration_number, b.bus_name, 
                     u.username, u.email, u.full_name,
                     (r.driver_rating + r.conductor_rating + r.condition_rating) / 3 as average_rating
              FROM ratings r 
              LEFT JOIN buses b ON r.bus_id = b.id 
              LEFT JOIN users u ON r.user_id = u.id
              WHERE 1=1";
    
    $params = [];
    
    // Add status filter (since we don't have is_approved in ratings, we'll use a placeholder)
    if ($status === 'pending') {
        // Since we don't have an approval system in ratings, we'll show all for now
        $query .= " AND 1=1";
    }
    
    // Add bus filter
    if (!empty($bus_id)) {
        $query .= " AND r.bus_id = ?";
        $params[] = $bus_id;
    }
    
    // Add user filter
    if (!empty($user_id)) {
        $query .= " AND r.user_id = ?";
        $params[] = $user_id;
    }
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (b.registration_number LIKE ? OR b.bus_name LIKE ? OR u.username LIKE ? OR u.full_name LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Add sorting
    $query .= " ORDER BY r.created_at DESC";
    
    // Prepare and execute the query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug output
    error_log("Reviews query: " . $query);
    error_log("Reviews found: " . count($reviews));
    
    // Get all buses for filter
    $buses = $pdo->query("SELECT id, registration_number, bus_name FROM buses ORDER BY registration_number")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error in reviews.php: " . $e->getMessage());
    die("<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>");
}

// Get all users who have left ratings
$users = $pdo->query("SELECT DISTINCT u.id, u.username, u.full_name 
                      FROM users u 
                      JOIN ratings r ON u.id = r.user_id 
                      ORDER BY u.username")->fetchAll(PDO::FETCH_ASSOC);

// Get total ratings count
$ratings_count = $pdo->query("SELECT COUNT(*) as count FROM ratings")->fetch(PDO::FETCH_ASSOC)['count'];

// Set page title
$page_title = 'Manage Ratings & Reviews';
include __DIR__ . '/includes/header.php';

// Debug output can be added here if needed
?>

<div class="container-fluid py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Bus Ratings</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="?status=all" class="btn btn-sm btn-outline-secondary">All (<?php echo $ratings_count; ?>)</a>
                    </div>
                </div>
            </div>

            <?php echo $message; ?>

            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="bus_id">
                                <option value="">All Buses</option>
                                <?php 
                                foreach ($buses as $bus): 
                                    // Extract registration number from bus_name if it's in format "name (XX-####)
                                    $regNumber = '';
                                    if (preg_match('/\(([A-Z0-9-]+)\)/', $bus['bus_name'], $matches)) {
                                        $regNumber = $matches[1];
                                    } elseif (!empty($bus['registration_number'])) {
                                        $regNumber = $bus['registration_number'];
                                    }
                                    ?>
                                    <option value="<?php echo $bus['id']; ?>" 
                                        <?php echo $bus_id == $bus['id'] ? 'selected' : ''; ?>>
                                        <?php 
                                        if (!empty($regNumber)) {
                                            echo htmlspecialchars(format_registration_number($regNumber));
                                        } else {
                                            echo 'Bus #' . $bus['id'];
                                        }
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="user_id">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                        <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="card">
            <div class="card-body">
                <?php if (count($reviews) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Bus</th>
                                    <th>User</th>
                                    <th>Driver</th>
                                    <th>Conductor</th>
                                    <th>Condition</th>
                                    <th>Avg</th>
                                    <th>Comment</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reviews as $review): ?>
                                    <tr>
                                        <td><?php echo $review['id']; ?></td>
                                        <td>
                                            <a href="../bus_info.php?id=<?php echo $review['bus_id']; ?>">
                                                <?php 
                                                    echo !empty($review['registration_number']) ? 
                                                        htmlspecialchars($review['registration_number']) : 
                                                        (!empty($review['bus_name']) ? 
                                                            htmlspecialchars($review['bus_name']) : 
                                                            'Bus #' . $review['bus_id']); 
                                                ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if (!empty($review['user_id'])): ?>
                                                <a href="user_details.php?id=<?php echo $review['user_id']; ?>">
                                                    <?php echo !empty($review['full_name']) ? htmlspecialchars($review['full_name']) : 
                                                        (!empty($review['username']) ? htmlspecialchars($review['username']) : 
                                                        (!empty($review['email']) ? htmlspecialchars($review['email']) : 'Guest')); ?>
                                                </a>
                                            <?php else: ?>
                                                Guest
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                                $driver_rating = $review['driver_rating'] ?? 0;
                                                echo $driver_rating > 0 ? $driver_rating . ' ' . str_repeat('★', $driver_rating) : '-';
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                                $conductor_rating = $review['conductor_rating'] ?? 0;
                                                echo $conductor_rating > 0 ? $conductor_rating . ' ' . str_repeat('★', $conductor_rating) : '-';
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                                $condition_rating = $review['condition_rating'] ?? 0;
                                                echo $condition_rating > 0 ? $condition_rating . ' ' . str_repeat('★', $condition_rating) : '-';
                                            ?>
                                        </td>
                                        <td class="text-center fw-bold">
                                            <?php echo $review['average_rating'] ?? '0.0'; ?>
                                        </td>
                                        <td><?php echo nl2br(htmlspecialchars($review['comment'] ?? 'No comment')); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($review['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <h4>No ratings found</h4>
                        <p>There are currently no ratings in the system. Ratings will appear here once users submit them.</p>
                        <p>You can visit the <a href="<?php echo SITE_URL; ?>" target="_blank">homepage</a> to test the rating submission process.</p>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Review Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reviewId">
                <div class="mb-3">
                    <p><strong>Comment:</strong></p>
                    <p id="reviewComment" class="border p-2 rounded"></p>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-danger" id="deleteReviewBtn">
                        <i class="fas fa-trash me-1"></i> Delete Review
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle review modal
var reviewModal = document.getElementById('reviewModal');
if (reviewModal) {
    reviewModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var comment = button.getAttribute('data-comment') || 'No comment';
        
        var modal = this;
        modal.querySelector('#reviewId').value = id;
        modal.querySelector('#reviewComment').textContent = comment;
    });
    
    // Handle delete button
    var deleteBtn = document.getElementById('deleteReviewBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
                window.location.href = '?delete=' + document.getElementById('reviewId').value;
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
