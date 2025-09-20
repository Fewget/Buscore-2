<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get all buses with owner and rating information
$buses = [];
try {
    $query = "
        SELECT 
            b.*, 
            u.username as owner_username,
            COALESCE(AVG((r.driver_rating + r.conductor_rating + r.condition_rating) / 3), 0) as avg_rating,
            COUNT(r.id) as rating_count
        FROM buses b
        LEFT JOIN users u ON b.owner_id = u.id
        LEFT JOIN ratings r ON b.id = r.bus_id
        GROUP BY b.id
        ORDER BY b.created_at DESC
    ";
    
    $stmt = $pdo->query($query);
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Set page title
$page_title = 'All Buses - ' . SITE_NAME;
?>

<?php include 'includes/header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>All Buses</h1>
        <a href="add-bus.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Bus
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (empty($buses)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No buses found in the database.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Registration</th>
                        <th>Route</th>
                        <th>Type</th>
                        <th>Owner</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($buses as $bus): ?>
                        <tr>
                            <td><?php echo $bus['id']; ?></td>
                            <td>
                                <a href="bus.php?id=<?php echo $bus['id']; ?>">
                                    <?php echo htmlspecialchars(format_registration_number($bus['registration_number'])); ?>
                                </a>
                            </td>
                            <td>
                                <?php if (!empty($bus['route_number'])): ?>
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($bus['route_number']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($bus['route_description'])): ?>
                                    <div class="small text-muted">
                                        <?php echo htmlspecialchars($bus['route_description']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $bus['type'] === 'government' ? 'success' : 'info'; ?>">
                                    <?php echo ucfirst($bus['type']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($bus['owner_username'])): ?>
                                    <?php echo htmlspecialchars($bus['owner_username']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bus['rating_count'] > 0): ?>
                                    <div class="d-flex align-items-center">
                                        <span class="text-warning me-1">
                                            <?php echo number_format($bus['avg_rating'], 1); ?>
                                        </span>
                                        <small class="text-muted">(<?php echo $bus['rating_count']; ?>)</small>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">No ratings</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bus['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="bus.php?id=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $bus['owner_id'] || $_SESSION['role'] === 'admin')): ?>
                                    <a href="edit-bus.php?id=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <h4>Database Information</h4>
        <div class="card">
            <div class="card-body">
                <?php
                // Get database information
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                echo "<p><strong>Database Name:</strong> " . DB_NAME . "</p>";
                echo "<p><strong>Tables:</strong> " . implode(', ', $tables) . "</p>";
                
                // Check buses table structure
                if (in_array('buses', $tables)) {
                    $columns = $pdo->query("DESCRIBE buses")->fetchAll(PDO::FETCH_COLUMN);
                    echo "<p><strong>Buses table columns:</strong> " . implode(', ', $columns) . "</p>";
                    
                    // Count buses
                    $count = $pdo->query("SELECT COUNT(*) as count FROM buses")->fetch()['count'];
                    echo "<p><strong>Total buses in database:</strong> " . $count . "</p>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
