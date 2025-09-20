<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Debug: Log session data and request info
error_log("=== Profile Access Log ===");
error_log("Time: " . date('Y-m-d H:i:s'));
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));
error_log("Session cookie: " . print_r($_COOKIE[session_name()] ?? 'No session cookie', true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user is a bus owner
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'bus_owner') {
    // If not a bus owner, redirect to appropriate page based on role
    $redirect = isset($_SESSION['role']) ? 
        ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php') : 
        'index.php';
    header('Location: ' . $redirect);
    exit();
}

$pageTitle = "Bus Owner Dashboard | " . SITE_NAME;
$success = isset($_GET['success']) ? $_GET['success'] : null;
$error = isset($_GET['error']) ? $_GET['error'] : null;

// Get bus owner's data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Get owner's buses
    $busesStmt = $pdo->prepare("
        SELECT b.*, 
               (SELECT AVG(rating) FROM ratings WHERE bus_id = b.id) as average_rating,
               (SELECT COUNT(*) FROM ratings WHERE bus_id = b.id) as rating_count
        FROM buses b 
        WHERE b.owner_id = ?
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $busesStmt->execute([$_SESSION['user_id']]);
    $recentBuses = $busesStmt->fetchAll();
    
    // Get stats
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_buses,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_buses,
            (SELECT COUNT(*) FROM ratings r WHERE r.bus_id IN (SELECT id FROM buses WHERE owner_id = ?)) as total_ratings
        FROM buses 
        WHERE owner_id = ?
    ");
    $statsStmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $stats = $statsStmt->fetch();
    
} catch (PDOException $e) {
    error_log("Bus owner dashboard error: " . $e->getMessage());
    $error = "An error occurred while loading your dashboard.";
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Bus Owner Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
        </div>
        <a href="add-bus.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add New Bus
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Total Buses</h6>
                            <h2 class="mb-0"><?php echo $stats['total_buses'] ?? 0; ?></h2>
                        </div>
                        <div class="icon-shape bg-white bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-bus fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Active Buses</h6>
                            <h2 class="mb-0"><?php echo $stats['active_buses'] ?? 0; ?></h2>
                        </div>
                        <div class="icon-shape bg-white bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Total Ratings</h6>
                            <h2 class="mb-0"><?php echo $stats['total_ratings'] ?? 0; ?></h2>
                        </div>
                        <div class="icon-shape bg-white bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-star fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Buses -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">My Buses</h5>
            <a href="my-buses.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentBuses)): ?>
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-bus fa-4x text-muted mb-3"></i>
                        <h4>No Buses Found</h4>
                        <p class="text-muted">You haven't added any buses yet.</p>
                    </div>
                    <a href="add-bus.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add Your First Bus
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Bus Number</th>
                                <th>Route</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Rating</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBuses as $bus): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bus['bus_number']); ?></td>
                                    <td><?php echo htmlspecialchars($bus['route']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $bus['bus_type'] === 'AC' ? 'info' : 'primary'; ?>">
                                            <?php echo htmlspecialchars($bus['bus_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $bus['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $bus['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($bus['average_rating']): ?>
                                            <span class="text-warning">
                                                <?php echo number_format($bus['average_rating'], 1); ?>
                                                <i class="fas fa-star"></i>
                                                <small class="text-muted">(<?php echo $bus['rating_count']; ?>)</small>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No ratings</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view-bus.php?id=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-bus.php?id=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Recent Ratings</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $ratingsStmt = $pdo->prepare("
                            SELECT r.*, b.bus_number, u.username 
                            FROM ratings r
                            JOIN buses b ON r.bus_id = b.id
                            JOIN users u ON r.user_id = u.id
                            WHERE b.owner_id = ?
                            ORDER BY r.created_at DESC
                            LIMIT 5
                        ");
                        $ratingsStmt->execute([$_SESSION['user_id']]);
                        $recentRatings = $ratingsStmt->fetchAll();
                        
                        if (!empty($recentRatings)) {
                            echo '<div class="list-group list-group-flush">';
                            foreach ($recentRatings as $rating) {
                                echo '<div class="list-group-item">';
                                echo '<div class="d-flex justify-content-between align-items-center mb-1">';
                                echo '<strong>Bus #' . htmlspecialchars($rating['bus_number']) . '</strong>';
                                echo '<span class="badge bg-warning text-dark">' . $rating['rating'] . ' <i class="fas fa-star"></i></span>';
                                echo '</div>';
                                echo '<div class="d-flex justify-content-between align-items-center">';
                                echo '<small class="text-muted">By ' . htmlspecialchars($rating['username']) . '</small>';
                                echo '<small class="text-muted">' . date('M j, Y', strtotime($rating['created_at'])) . '</small>';
                                echo '</div>';
                                if (!empty($rating['comment'])) {
                                    echo '<div class="mt-2"><small>' . nl2br(htmlspecialchars($rating['comment'])) . '</small></div>';
                                }
                                echo '</div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<p class="text-muted mb-0">No recent ratings found.</p>';
                        }
                    } catch (Exception $e) {
                        echo '<p class="text-muted mb-0">Unable to load recent ratings.</p>';
                        error_log("Recent ratings error: " . $e->getMessage());
                    }
                    ?>
                </div>
                <div class="card-footer text-end">
                    <a href="bus-ratings.php" class="btn btn-sm btn-outline-primary">View All Ratings</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="add-bus.php" class="btn btn-outline-primary w-100 h-100 py-3">
                                <i class="fas fa-plus-circle fa-2x mb-2 d-block"></i>
                                Add New Bus
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="my-buses.php" class="btn btn-outline-success w-100 h-100 py-3">
                                <i class="fas fa-bus fa-2x mb-2 d-block"></i>
                                Manage Buses
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="reports.php" class="btn btn-outline-info w-100 h-100 py-3">
                                <i class="fas fa-chart-bar fa-2x mb-2 d-block"></i>
                                View Reports
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="edit-profile.php" class="btn btn-outline-secondary w-100 h-100 py-3">
                                <i class="fas fa-user-edit fa-2x mb-2 d-block"></i>
                                Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
