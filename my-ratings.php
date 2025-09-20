<?php
// Start session and include configuration
session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirm password do not match';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($updateStmt->execute([$hashed_password, $user_id])) {
                    $success = 'Password updated successfully';
                } else {
                    $error = 'Failed to update password';
                }
            } else {
                $error = 'Current password is incorrect';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get user's ratings
try {
    $ratingsStmt = $pdo->prepare("
        SELECT r.*, b.bus_number, b.route_number, b.route_name
        FROM ratings r
        LEFT JOIN buses b ON r.bus_id = b.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $ratingsStmt->execute([$user_id]);
    $ratings = $ratingsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching ratings: ' . $e->getMessage();
    $ratings = [];
}

// Get user's reports
try {
    $reportsStmt = $pdo->prepare("
        SELECT * FROM bus_reports 
        WHERE contact_email = (SELECT email FROM users WHERE id = ?)
        ORDER BY created_at DESC
    ");
    $reportsStmt->execute([$user_id]);
    $reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching reports: ' . $e->getMessage();
    $reports = [];
}

// Set page title
$page_title = 'My Ratings & Reports';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- User Profile Card -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($_SESSION['username']); ?></h5>
                    <p class="text-muted">Member since <?php echo date('M Y', strtotime($_SESSION['created_at'] ?? 'now')); ?></p>
                    
                    <!-- Change Password Form -->
                    <button class="btn btn-outline-primary btn-sm w-100 mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#changePasswordForm">
                        Change Password
                    </button>
                    
                    <div class="collapse" id="changePasswordForm">
                        <form method="POST" class="mt-3">
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                            <?php endif; ?>
                            <div class="mb-2">
                                <input type="password" name="current_password" class="form-control form-control-sm" placeholder="Current Password" required>
                            </div>
                            <div class="mb-2">
                                <input type="password" name="new_password" class="form-control form-control-sm" placeholder="New Password" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" name="confirm_password" class="form-control form-control-sm" placeholder="Confirm New Password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary btn-sm w-100">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">My Activity</h6>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Ratings Given
                        <span class="badge bg-primary rounded-pill"><?php echo count($ratings); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Reports Submitted
                        <span class="badge bg-primary rounded-pill"><?php echo count($reports); ?></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="col-lg-8">
            <!-- Ratings Section -->
            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Ratings</h5>
                    <a href="search.php" class="btn btn-sm btn-outline-primary">Rate Another Bus</a>
                </div>
                <div class="card-body">
                    <?php if (empty($ratings)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-star fa-3x text-muted mb-3"></i>
                            <p class="text-muted">You haven't rated any buses yet.</p>
                            <a href="search.php" class="btn btn-primary">Rate a Bus</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Bus</th>
                                        <th>Rating</th>
                                        <th>Comment</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ratings as $rating): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($rating['bus_number'])): ?>
                                                    <strong><?php echo htmlspecialchars($rating['bus_number']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <?php echo !empty($rating['route_number']) ? 'Route ' . htmlspecialchars($rating['route_number']) : ''; ?>
                                                        <?php echo !empty($rating['route_name']) ? ' - ' . htmlspecialchars($rating['route_name']) : ''; ?>
                                                    </small>
                                                <?php else: ?>
                                                    <em>Bus not found</em>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="text-warning">
                                                    <?php 
                                                    $fullStars = floor($rating['rating']);
                                                    $halfStar = ($rating['rating'] - $fullStars) >= 0.5 ? 1 : 0;
                                                    $emptyStars = 5 - $fullStars - $halfStar;
                                                    
                                                    for ($i = 0; $i < $fullStars; $i++) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    }
                                                    if ($halfStar) {
                                                        echo '<i class="fas fa-star-half-alt"></i>';
                                                    }
                                                    for ($i = 0; $i < $emptyStars; $i++) {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                    ?>
                                                    <span class="text-dark ms-1"><?php echo number_format($rating['rating'], 1); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($rating['comment'])): ?>
                                                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($rating['comment'])); ?></div>
                                                <?php else: ?>
                                                    <span class="text-muted">No comment</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-nowrap">
                                                <?php echo date('M j, Y', strtotime($rating['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reports Section -->
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Reports</h5>
                    <a href="report-bus.php" class="btn btn-sm btn-outline-primary">Report an Issue</a>
                </div>
                <div class="card-body">
                    <?php if (empty($reports)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-flag fa-3x text-muted mb-3"></i>
                            <p class="text-muted">You haven't submitted any reports yet.</p>
                            <a href="report-bus.php" class="btn btn-primary">Report an Issue</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Bus Number</th>
                                        <th>Issue</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $statusBadges = [
                                        'pending' => 'warning',
                                        'reviewed' => 'info',
                                        'resolved' => 'success'
                                    ];
                                    
                                    foreach ($reports as $report): 
                                        $issueTypes = json_decode($report['issue_types'], true);
                                        $issueTypes = is_array($issueTypes) ? $issueTypes : [];
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($report['bus_number']); ?></strong>
                                            </td>
                                            <td>
                                                <div class="mb-1">
                                                    <?php foreach ($issueTypes as $type): ?>
                                                        <span class="badge bg-secondary me-1"><?php echo htmlspecialchars(ucfirst($type)); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php if (!empty($report['description'])): ?>
                                                    <div class="small text-muted">
                                                        <?php echo nl2br(htmlspecialchars(substr($report['description'], 0, 100)) . (strlen($report['description']) > 100 ? '...' : '')); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $statusBadges[strtolower($report['status'])] ?? 'secondary'; ?>">
                                                    <?php echo ucfirst($report['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <?php echo date('M j, Y', strtotime($report['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS for the page -->
<style>
.comment-text {
    max-width: 250px;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.table td {
    vertical-align: middle;
}
.fa-star, .fa-star-half-alt {
    color: #ffc107;
}
</style>

<?php include 'includes/footer.php'; ?>
