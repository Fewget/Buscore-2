<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config and functions
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check admin access
checkAdminAccess();

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid user ID';
    header('Location: users.php');
    exit();
}

$user_id = (int)$_GET['id'];
$user = null;
$buses = [];
$message = '';

try {
    // Get user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = 'User not found';
        header('Location: users.php');
        exit();
    }
    
    // Get user's buses if they are a bus owner
    if ($user['role'] === 'bus_owner') {
        $stmt = $pdo->prepare("
            SELECT b.*, 
                   (SELECT COUNT(*) FROM ratings WHERE bus_id = b.id) as rating_count,
                   (SELECT AVG((driver_rating + conductor_rating + condition_rating) / 3) FROM ratings WHERE bus_id = b.id) as avg_rating
            FROM buses b 
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $buses = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log("Error fetching user details: " . $e->getMessage());
    $message = 'An error occurred while fetching user details';
}

// Include header after setting up all required variables
require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Back button -->
    <div class="mb-4">
        <a href="users.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Back to Users
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-danger"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-lg mx-auto mb-3">
                            <i class="fas fa-user-circle fa-5x text-secondary"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h4>
                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Account Details</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Username:</th>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Role:</th>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Joined:</th>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="user-edit.php?id=<?php echo $user_id; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> Edit User
                        </a>
                        <?php if ($user['status'] === 'active'): ?>
                            <a href="#" class="btn btn-outline-danger" 
                               onclick="return confirmAction('suspend', '<?php echo $user_id; ?>', 'suspend')">
                                <i class="fas fa-ban me-1"></i> Suspend
                            </a>
                        <?php else: ?>
                            <a href="#" class="btn btn-outline-success" 
                               onclick="return confirmAction('activate', '<?php echo $user_id; ?>', 'activate')">
                                <i class="fas fa-check me-1"></i> Activate
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if ($user['role'] === 'bus_owner'): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Managed Buses</h5>
                        <a href="add-bus.php?user_id=<?php echo $user_id; ?>" class="btn btn-sm btn-light">
                            <i class="fas fa-plus me-1"></i> Add Bus
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($buses) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Bus Number</th>
                                            <th>Route</th>
                                            <th>Rating</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($buses as $bus): ?>
                                            <tr>
                                                <td>
                                                    <a href="bus-details.php?id=<?php echo $bus['id']; ?>">
                                                        <?php echo htmlspecialchars($bus['registration_number']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($bus['route_description'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if ($bus['rating_count'] > 0): ?>
                                                        <span class="text-warning">
                                                            <?php echo number_format($bus['avg_rating'], 1); ?>/5.0
                                                        </span>
                                                        <small class="text-muted">(<?php echo $bus['rating_count']; ?>)</small>
                                                    <?php else: ?>
                                                        <span class="text-muted">No ratings</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $bus['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($bus['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="bus-details.php?id=<?php echo $bus['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit-bus.php?id=<?php echo $bus['id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary" 
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-4">
                                <div class="mb-3">
                                    <i class="fas fa-bus fa-3x text-muted"></i>
                                </div>
                                <p class="text-muted mb-0">No buses found for this user.</p>
                                <a href="add-bus.php?user_id=<?php echo $user_id; ?>" class="btn btn-sm btn-primary mt-3">
                                    <i class="fas fa-plus me-1"></i> Add First Bus
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Activity Logs (to be implemented) -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center py-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Activity logging coming soon
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmAction(action, userId, actionText) {
    if (confirm(`Are you sure you want to ${actionText} this user?`)) {
        // In a real implementation, this would be an AJAX call or form submission
        window.location.href = `user-actions.php?action=${action}&id=${userId}`;
    }
    return false;
}
</script>

<?php require_once 'includes/footer.php'; ?>
