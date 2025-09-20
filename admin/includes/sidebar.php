<?php 
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/config.php';

// Check if user is logged in and is admin
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $isAdmin = ($user && $user['role'] === 'admin');
}

if (isset($isAdmin) && $isAdmin): 
?>
<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="buses.php">
                    <i class="fas fa-bus me-2"></i>
                    Buses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="reviews.php">
                    <i class="fas fa-comments me-2"></i>
                    Reviews
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users me-2"></i>
                    Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-cog me-2"></i>
                    Settings
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="/BS/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</div>
<?php endif; ?>
