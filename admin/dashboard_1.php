<?php
// Include admin config which will handle all required includes and authentication
require_once __DIR__ . '/includes/config.php';

// Set page title
$page_title = 'Admin Dashboard - ' . SITE_NAME;

// Get dashboard statistics
try {
    // Get total buses
    $stmt = $pdo->query("SELECT COUNT(*) as total_buses FROM buses");
    $totalBuses = $stmt->fetch()['total_buses'];
    
    // Get total users
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $totalUsers = $stmt->fetch()['total_users'];
    
    // Get total ratings
    $stmt = $pdo->query("SELECT COUNT(*) as total_ratings FROM ratings");
    $totalRatings = $stmt->fetch()['total_ratings'];
    
    // Get total bus owners
    $stmt = $pdo->query("SELECT COUNT(*) as total_owners FROM bus_owners");
    $totalOwners = $stmt->fetch()['total_owners'];
    
    // Recent activity
    $recentActivity = $pdo->query("
        SELECT al.*, u.username, u.full_name 
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Error loading dashboard data. Please try again later.";
}

// Include header after setting all variables
include __DIR__ . '/includes/header.php';
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid p-0">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse
            <?php echo isset($_COOKIE['sidebarCollapsed']) && $_COOKIE['sidebarCollapsed'] === 'true' ? 'show' : 'show'; ?>" 
             id="sidebarMenu">
            <div class="text-center mb-4 pt-3">
                <h5>Admin Panel</h5>
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                    </div>
                </div>
                <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></h6>
                <small class="text-muted">Administrator</small>
            </div>
            <hr>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/buses.php">
                        <i class="fas fa-bus me-2"></i> Buses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/users.php">
                        <i class="fas fa-users me-2"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/reviews.php">
                        <i class="fas fa-star me-2"></i> Reviews
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/settings.php">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main content -->
        <main class="main-content col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard Overview</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="btn-group me-2">
                        <a href="<?php echo SITE_URL; ?>/admin/reviews.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye me-1"></i> View All Reviews
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="row mb-4" style="margin-top: 50px;">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-uppercase small">Total Buses</h6>
                                    <h2 class="mb-0"><?php echo number_format($totalBuses); ?></h2>
                                </div>
                                <div class="icon-circle">
                                    <i class="fas fa-bus"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between bg-primary bg-opacity-25">
                            <a class="small text-white text-decoration-none" href="buses.php">
                                View all
                            </a>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Total Users</h6>
                                    <h2 class="mb-0"><?php echo number_format($totalUsers); ?></h2>
                                </div>
                                <i class="fas fa-users fa-3x opacity-25"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between bg-success bg-opacity-25">
                            <a class="small text-white text-decoration-none" href="users.php">
                                View all
                            </a>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-dark h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Total Ratings</h6>
                                    <h2 class="mb-0"><?php echo number_format($totalRatings); ?></h2>
                                </div>
                                <i class="fas fa-star fa-3x opacity-25"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between bg-warning bg-opacity-25">
                            <a class="small text-dark text-decoration-none" href="ratings.php">
                                View all
                            </a>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Pending Reviews</h6>
                                    <h2 class="mb-0"><?php echo number_format(count($pendingReviews)); ?></h2>
                                </div>
                                <i class="fas fa-comments fa-3x opacity-25"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between bg-info bg-opacity-25">
                            <a class="small text-white text-decoration-none" href="reviews.php?status=pending">
                                Review now
                            </a>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Pending Reviews -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Pending Reviews</h6>
                            <a href="reviews.php?status=pending" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($pendingReviews) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($pendingReviews as $review): ?>
                                        <a href="review-details.php?id=<?php echo $review['id']; ?>" 
                                           class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($review['title']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo time_elapsed_string($review['created_at']); ?>
                                                </small>
                                            </div>
                                            <p class="mb-1 text-truncate">
                                                <?php echo htmlspecialchars($review['content']); ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($review['username']); ?> •
                                                <i class="fas fa-bus ms-2 me-1"></i>
                                                <?php echo htmlspecialchars($review['registration_number']); ?>
                                            </small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="far fa-check-circle fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No pending reviews</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Recent Activity</h6>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($recentActivity) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentActivity as $activity): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <?php 
                                                            $action = '';
                                                            switch ($activity['action']) {
                                                                case 'user_login':
                                                                    $icon = 'sign-in-alt';
                                                                    $action = 'User logged in';
                                                                    break;
                                                                case 'user_registered':
                                                                    $icon = 'user-plus';
                                                                    $action = 'New user registered';
                                                                    break;
                                                                case 'bus_rated':
                                                                    $icon = 'star';
                                                                    $action = 'Bus rated';
                                                                    break;
                                                                default:
                                                                    $icon = 'info-circle';
                                                                    $action = ucwords(str_replace('_', ' ', $activity['action']));
                                                            }
                                                        ?>
                                                        <i class="fas fa-<?php echo $icon; ?> me-2"></i>
                                                        <?php echo $action; ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?php echo time_elapsed_string($activity['created_at']); ?>
                                                    </small>
                                                </div>
                                                <?php if (!empty($activity['details'])): ?>
                                                    <p class="mb-1 small text-muted">
                                                        <?php echo htmlspecialchars($activity['details']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if (!empty($activity['ip_address'])): ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-globe me-1"></i>
                                                        <?php echo htmlspecialchars($activity['ip_address']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No recent activity</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-end">
                            <a href="activity.php" class="btn btn-sm btn-outline-primary">View All Activity</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-history me-2"></i> Recent Activity</h6>
                            <a href="<?php echo SITE_URL; ?>/admin/activity-logs.php" class="btn btn-sm btn-outline-primary">
                                View All <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if (!empty($recentActivity)): ?>
                                    <?php foreach ($recentActivity as $activity): ?>
                                        <a href="#" class="list-group-item list-group-item-action border-0 border-bottom">
                                            <div class="d-flex w-100 align-items-center">
                                                <div class="me-3">
                                                    <div class="icon-circle bg-light text-primary">
                                                        <i class="fas fa-<?php 
                                                            echo match($activity['action_type'] ?? '') {
                                                                'login' => 'sign-in-alt',
                                                                'create' => 'plus',
                                                                'update' => 'edit',
                                                                'delete' => 'trash',
                                                                default => 'bell'
                                                            };
                                                        ?>"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1 fw-bold">
                                                            <?php echo htmlspecialchars(ucfirst($activity['action'] ?? 'Activity')); ?>
                                                        </h6>
                                                        <small class="text-muted">
                                                            <?php 
                                                                $timeAgo = new DateTime($activity['created_at']);
                                                                $now = new DateTime();
                                                                $interval = $timeAgo->diff($now);
                                                                
                                                                if ($interval->d > 0) {
                                                                    echo $interval->d . 'd ago';
                                                                } elseif ($interval->h > 0) {
                                                                    echo $interval->h . 'h ago';
                                                                } elseif ($interval->i > 0) {
                                                                    echo $interval->i . 'm ago';
                                                                } else {
                                                                    echo 'Just now';
                                                                }
                                                            ?>
                                                        </small>
                                                    </div>
                                                    <p class="mb-1 text-muted">
                                                        <?php echo htmlspecialchars($activity['details'] ?? 'No details available'); ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-user me-1"></i> 
                                                        <?php echo htmlspecialchars($activity['username'] ?? 'System'); ?>
                                                        <span class="mx-2">•</span>
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-4 text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                                        <p class="mb-0">No recent activity found.</p>
                                        <small>Activities will appear here as they happen.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-center">
                            <a href="<?php echo SITE_URL; ?>/admin/activity-logs.php" class="text-decoration-none">
                                View all activity <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
    /* Base styles */
    :root {
        --primary-color: #4e73df;
        --success-color: #1cc88a;
        --info-color: #36b9cc;
        --warning-color: #f6c23e;
        --danger-color: #e74a3b;
        --secondary-color: #858796;
        --light-color: #f8f9fc;
        --dark-color: #5a5c69;
    }
    
    /* Fix header and content positioning */
    body {
        padding-top: 70px; /* Add padding to prevent header overlap */
    }
    
    /* Admin header styles */
    .admin-header {
        left: 0;
        right: 0;
        z-index: 1030;
        transition: left 0.3s ease;
    }
    
    @media (min-width: 768px) {
        .admin-header {
            left: 300px;
            width: calc(100% - 300px);
        }
    }
    
    /* Adjust header position */
    .navbar {
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        z-index: 1030;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        transition: left 0.3s ease;
    }
    
    /* On small screens, ensure header is full width */
    @media (max-width: 991.98px) {
        .navbar {
            left: 0 !important;
            width: 100%;
        }
    }
    
    /* On medium and larger screens, move header 300px from left */
    @media (min-width: 992px) {
        .navbar {
            left: 300px;
            width: calc(100% - 300px);
        }
    }
    
    /* Main content area */
    .main-content {
        margin-left: 300px;
        padding: 6rem 1.5rem 1.5rem; /* Add top padding to account for fixed header */
        transition: margin 0.3s ease;
        min-height: 100vh;
    }
    
    /* Sidebar styles */
    .sidebar {
        position: fixed;
        top: 56px; /* Height of the navbar */
        bottom: 0;
        left: 0;
        z-index: 100;
        width: 300px;
        padding: 20px 0;
        overflow-x: hidden;
        overflow-y: auto;
        background-color: #ffffff;
        border-right: 1px solid #e3e6f0;
        transition: all 0.3s ease;
    }
    
    .sidebar .nav-link {
        color: #5a5c69;
        padding: 0.75rem 1.5rem;
        margin: 0.25rem 0.5rem;
        border-radius: 0.35rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .sidebar .nav-link:hover {
        color: #4e73df;
        background-color: rgba(78, 115, 223, 0.1);
    }
    
    .sidebar .nav-link.active {
        color: #4e73df;
        background-color: rgba(78, 115, 223, 0.1);
        border-left: 3px solid #4e73df;
    }
    
    .sidebar .nav-link i {
        width: 20px;
        text-align: center;
        margin-right: 0.5rem;
        color: #5a5c69;
    }
    
    /* Adjust content when sidebar is collapsed */
    .sidebar.collapse.show + .main-content,
    .sidebar.collapsing + .main-content {
        margin-left: 0;
    }
    
    body {
        background-color: #f8f9fc;
        color: #5a5c69;
    }
    
    /* Sidebar styles */
    .sidebar {
        background-color: #ffffff;
        min-height: 100vh;
        color: #5a5c69;
    }
    
    .sidebar .nav-link {
        color: #5a5c69;
        padding: 0.75rem 1.5rem;
        margin: 0.25rem 0.5rem;
        border-radius: 0.35rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    
        border-left: 3px solid #4e73df;
    }
    
    .sidebar .nav-link i {
        width: 20px;
        text-align: center;
        margin-right: 0.5rem;
    }
    
    /* Card styles */
    .card {
        border: none;
        border-radius: 0.35rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        margin-bottom: 1.5rem;
    }
    
    .card-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        padding: 1rem 1.25rem;
    }
    
    .card-header:first-child {
        border-radius: calc(0.35rem - 1px) calc(0.35rem - 1px) 0 0;
    }
    
    /* Stats cards */
    .icon-circle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 3.5rem;
        width: 3.5rem;
        border-radius: 100%;
        font-size: 1.5rem;
    }
    
    .text-xs {
        font-size: 0.7rem;
    }
    
    .text-primary { color: var(--primary-color) !important; }
    .bg-primary { background-color: var(--primary-color) !important; }
    .text-success { color: var(--success-color) !important; }
    .bg-success { background-color: var(--success-color) !important; }
    .text-warning { color: var(--warning-color) !important; }
    .bg-warning { background-color: var(--warning-color) !important; }
    .text-danger { color: var(--danger-color) !important; }
    .bg-danger { background-color: var(--danger-color) !important; }
    
    /* Activity feed */
    .activity-item {
        position: relative;
        padding-left: 2.5rem;
        padding-bottom: 1.5rem;
        border-left: 1px solid #e3e6f0;
    }
    
    .activity-item:last-child {
        border-left: 0;
        padding-bottom: 0;
    }
    
    .activity-item::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #e3e6f0;
    }
    
    .activity-item.primary::before { background-color: var(--primary-color); }
    .activity-item.success::before { background-color: var(--success-color); }
    .activity-item.warning::before { background-color: var(--warning-color); }
    .activity-item.danger::before { background-color: var(--danger-color); }
    
    /* Responsive adjustments */
    @media (max-width: 991.98px) {
    .main-content {
        margin-left: 250px;
        height: 100vh;
        overflow-y: auto;
        padding: 1.5rem;
        background-color: #f8f9fc;
    }
    
    /* Mobile responsiveness */
    @media (max-width: 767.98px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .main-content {
            margin-left: 0;
            width: 100%;
        }
    }
</style>

<style>
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    body {
        display: flex;
        flex-direction: column;
    }
    .main-content {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
    }
    .sidebar::-webkit-scrollbar {
        width: 5px;
    }
    .sidebar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .sidebar::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }
    .sidebar::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<!-- Include necessary JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebarMenu');
    const mainContent = document.querySelector('.main-content');
    
    // Function to handle sidebar toggle
    function toggleSidebarState() {
        sidebar.classList.toggle('show');
        
        // Update main content margin
        if (window.innerWidth >= 768) { // Only adjust margin on desktop
            if (sidebar.classList.contains('show')) {
                mainContent.style.marginLeft = '300px';
                document.cookie = 'sidebarCollapsed=false; path=/';
            } else {
                mainContent.style.marginLeft = '0';
                document.cookie = 'sidebarCollapsed=true; path=/';
            }
        }
    }
    
    // Initialize sidebar state from cookie
    function initSidebar() {
        const isCollapsed = document.cookie.split(';').some(item => item.trim().startsWith('sidebarCollapsed=true'));
        
        if (window.innerWidth >= 768) { // Desktop
            if (isCollapsed) {
                sidebar.classList.remove('show');
                mainContent.style.marginLeft = '0';
            } else {
                sidebar.classList.add('show');
                mainContent.style.marginLeft = '300px';
            }
        } else { // Mobile
            sidebar.classList.remove('show');
            mainContent.style.marginLeft = '0';
        }
    }
    
    // Handle window resize
    function handleResize() {
        if (window.innerWidth < 768) {
            // Mobile view - always hide sidebar
            sidebar.classList.remove('show');
            mainContent.style.marginLeft = '0';
        } else {
            // Desktop view - respect cookie state
            initSidebar();
        }
    }
    
    // Event listeners
    if (toggleSidebar) {
        toggleSidebar.addEventListener('click', toggleSidebarState);
    }
    
    window.addEventListener('resize', handleResize);
    
    // Initialize
    initSidebar();
    
    // Initialize charts if needed
    const ctx = document.getElementById('statsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Users',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Monthly Growth'
                    }
                }
            },
        });
    }
});
</script>

<style>
/* Sidebar styling */
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar-sticky {
    position: relative;
    top: 0;
    height: calc(100vh - 48px);
    padding-top: .5rem;
    overflow-x: hidden;
    overflow-y: auto;
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
    padding: 0.5rem 1rem;
    margin: 0.2rem 0.5rem;
    border-radius: 0.25rem;
}

.sidebar .nav-link:hover {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
}

.sidebar .nav-link.active {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
}

.sidebar .nav-link i {
    margin-right: 4px;
    width: 20px;
    text-align: center;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
    .sidebar {
        position: static;
        height: auto;
        padding-top: 0;
    }
    
    .sidebar-sticky {
        height: auto;
        padding-top: 0;
    }
    
    .main-content {
        padding-top: 1.5rem;
    }
}

/* Card hover effect */
.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Avatar */
.avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: 600;
}
</style>

<!-- JavaScript for Dashboard Functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle functionality
        const toggleSidebar = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebarMenu');
        const mainContent = document.querySelector('.main-content');
        
        // Check for saved sidebar state in cookies
        const isSidebarCollapsed = document.cookie.split(';').some((item) => item.trim().startsWith('sidebarCollapsed='));
        
        // Initialize sidebar state
        if (isSidebarCollapsed) {
            sidebar.classList.remove('show');
            mainContent.style.marginLeft = '0';
        }
        
        // Toggle sidebar on button click
        if (toggleSidebar) {
            toggleSidebar.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                
                // Toggle margin for main content
                if (sidebar.classList.contains('show')) {
                    mainContent.style.marginLeft = '25%';
                    document.cookie = 'sidebarCollapsed=false; path=/';
                } else {
                    mainContent.style.marginLeft = '0';
                    document.cookie = 'sidebarCollapsed=true; path=/';
                }
                
                // Dispatch a resize event in case any charts need to be redrawn
                window.dispatchEvent(new Event('resize'));
            });
        }
        
        // Auto-hide sidebar on small screens
        function handleResize() {
            if (window.innerWidth < 992) {
                sidebar.classList.remove('show');
                mainContent.style.marginLeft = '0';
            } else {
                if (!document.cookie.split(';').some((item) => item.trim().startsWith('sidebarCollapsed=true'))) {
                    sidebar.classList.add('show');
                    mainContent.style.marginLeft = '25%';
                }
            }
        }
        
        // Initial call
        handleResize();
        
        // Add event listener for window resize
        window.addEventListener('resize', handleResize);
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Add animation to stats cards on scroll
        const animateOnScroll = function() {
            const cards = document.querySelectorAll('.card[data-animation="true"]');
            cards.forEach(card => {
                const cardPosition = card.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.3;
                
                if (cardPosition < screenPosition) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }
            });
        };
        
        // Add animation classes to stats cards
        document.querySelectorAll('.stats-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = `opacity 0.5s ease-out ${index * 0.1}s, transform 0.5s ease-out ${index * 0.1}s`;
            card.setAttribute('data-animation', 'true');
        });
        
        // Initial animation check
        animateOnScroll();
        
        // Add scroll event listener for animations
        window.addEventListener('scroll', animateOnScroll);
        
        // Handle active state for sidebar links
        const currentLocation = location.href;
        const menuItems = document.querySelectorAll('.sidebar .nav-link');
        
        menuItems.forEach(item => {
            if (item.href === currentLocation) {
                item.classList.add('active');
            } else if (currentLocation.includes('buses') && item.href.includes('buses')) {
                item.classList.add('active');
            } else if (currentLocation.includes('users') && item.href.includes('users')) {
                item.classList.add('active');
            } else if (currentLocation.includes('reviews') && item.href.includes('reviews')) {
                item.classList.add('active');
            } else if (currentLocation.includes('settings') && item.href.includes('settings')) {
                item.classList.add('active');
            }
        });
    });
    
    // Helper function to format numbers with commas
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Function to update dashboard stats (can be called via AJAX)
    function updateDashboardStats() {
        // This would be an AJAX call in a real implementation
        // fetch('/admin/api/dashboard-stats.php')
        //     .then(response => response.json())
        //     .then(data => {
        //         document.getElementById('totalBuses').textContent = numberWithCommas(data.totalBuses);
        //         document.getElementById('totalUsers').textContent = numberWithCommas(data.totalUsers);
        //         document.getElementById('totalReviews').textContent = numberWithCommas(data.totalReviews);
        //     });
    }
    
    // Update stats every 5 minutes
    // setInterval(updateDashboardStats, 300000);
</script>

<style>
    /* Dashboard-specific footer styles */
    .main-footer {
        margin-left: 300px !important;
        width: calc(100% - 300px) !important;
    }
    
    @media (max-width: 767.98px) {
        .main-footer {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
