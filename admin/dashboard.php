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

// Add custom CSS with version parameter to prevent caching
$custom_css = SITE_URL . '/admin/assets/css/dashboard.css?v=' . time();
?>

<style>
/* Test styles - will be visible if CSS is working */
body { 
    background-color: #f0f8ff !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

h1, h2, h3, h4, h5, h6 {
    color: #2c3e50 !important;
    font-weight: 600;
}

/* Stats Cards */
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0.15rem 0.5rem rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
}

.card-body {
    padding: 1.5rem;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2c3e50;
}

.stat-label {
    color: #6c757d;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Activity Feed */
.activity-item {
    padding: 1rem 0;
    border-bottom: 1px solid #eee;
}

.activity-item:last-child {
    border-bottom: none;
}

/* Sidebar */
.sidebar {
    background: #2c3e50;
    min-height: 100vh;
    padding: 1rem 0;
}

.sidebar .nav-link {
    color: rgba(255, 255, 255, 0.8);
    padding: 0.75rem 1.5rem;
    margin: 0.25rem 1rem;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

/* Main Content */
.main-content {
    padding: 2rem;
    background-color: #f8f9fa;
}

/* Dashboard Header */
.dashboard-header {
    background: #fff;
    border-radius: 10px;
    padding: 1.5rem 2rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    margin-bottom: 2rem;
    border-left: 4px solid var(--primary);
}

.dashboard-header h1 {
    color: var(--primary) !important;
    font-weight: 700;
    margin: 0;
}

.dashboard-header .btn {
    border-radius: 5px;
    font-weight: 500;
    padding: 0.4rem 1rem;
}

.dashboard-header .dropdown-menu {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    padding: 0.5rem 0;
    margin-top: 0.5rem;
}

.dashboard-header .dropdown-item {
    padding: 0.5rem 1.5rem;
    font-size: 0.9rem;
    color: var(--dark);
    transition: all 0.2s;
}

.dashboard-header .dropdown-item:hover,
.dashboard-header .dropdown-item.active {
    background-color: var(--primary);
    color: white;
}

.dashboard-header .badge {
    font-weight: 500;
    padding: 0.5rem 0.8rem;
    border-radius: 50px;
    font-size: 0.8rem;
}

.dashboard-header .badge i {
    margin-right: 5px;
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .dashboard-header {
        padding: 1.25rem;
    }
    
    .dashboard-header .d-flex {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .dashboard-header .btn-group {
        margin-top: 1rem;
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 1rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .dashboard-header .d-flex {
        flex-direction: column;
        align-items: stretch;
    }
    
    .dashboard-header .btn-group {
        margin-top: 1rem;
        width: 100%;
    }
    
    .dashboard-header .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<div class="container-fluid">
    <div class="row">
        <!-- Include Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Dashboard Header -->
            <div class="dashboard-header py-4 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">Dashboard Overview</h1>
                        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>! Here's what's happening today.</p>
                    </div>
                    <div class="d-flex align-items-center">
                        <a href="view-reports.php" class="btn btn-sm btn-info text-white me-3">
                            <i class="fas fa-flag me-1"></i> View Reports
                        </a>
                        <div class="btn-group me-3">
                            <button type="button" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Export as PDF</a></li>
                                <li><a class="dropdown-item" href="#">Export as CSV</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#">Print</a></li>
                            </ul>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="timeRangeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="far fa-calendar-alt me-1"></i> This Week
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="timeRangeDropdown">
                                <li><a class="dropdown-item active" href="#">This Week</a></li>
                                <li><a class="dropdown-item" href="#">This Month</a></li>
                                <li><a class="dropdown-item" href="#">Last 30 Days</a></li>
                                <li><a class="dropdown-item" href="#">This Year</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#">Custom Range</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex">
                    <div class="me-3">
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-clock me-1"></i> Last updated: <?php echo date('M j, Y g:i A'); ?>
                        </span>
                    </div>
                    <div>
                        <span class="badge bg-primary">
                            <i class="fas fa-server me-1"></i> System Status: Operational
                        </span>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Total Buses</h6>
                                    <h2 class="mb-0"><?php echo number_format($totalBuses); ?></h2>
                                </div>
                                <div class="bg-white bg-opacity-25 p-3 rounded-circle">
                                    <i class="fas fa-bus fa-2x"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="buses.php" class="text-white text-decoration-none">
                                    View all buses <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Total Users</h6>
                                    <h2 class="mb-0"><?php echo number_format($totalUsers); ?></h2>
                                </div>
                                <div class="bg-white bg-opacity-25 p-3 rounded-circle">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="users.php" class="text-white text-decoration-none">
                                    View all users <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-dark h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Total Ratings</h6>
                                    <h2 class="mb-0"><?php echo number_format($totalRatings); ?></h2>
                                </div>
                                <div class="bg-dark bg-opacity-10 p-3 rounded-circle">
                                    <i class="fas fa-star fa-2x"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="reviews.php" class="text-dark text-decoration-none">
                                    View all ratings <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase mb-1">Bus Owners</h6>
                                    <h2 class="mb-0"><?php echo number_format($totalOwners); ?></h2>
                                </div>
                                <div class="bg-white bg-opacity-25 p-3 rounded-circle">
                                    <i class="fas fa-user-tie fa-2x"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="bus-owners.php" class="text-white text-decoration-none">
                                    View all owners <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
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
                            <a href="activity-logs.php" class="btn btn-sm btn-outline-primary">
                                View All <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if (!empty($recentActivity)): ?>
                                    <?php foreach ($recentActivity as $activity): ?>
                                        <div class="list-group-item border-0 border-bottom">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    <?php echo ucfirst(htmlspecialchars($activity['action'])); ?>
                                                    <?php if (!empty($activity['item_type'])): ?>
                                                        <span class="text-muted">(<?php echo htmlspecialchars($activity['item_type']); ?>)</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php 
                                                        $date = new DateTime($activity['created_at']);
                                                        echo $date->format('M j, Y h:i A');
                                                    ?>
                                                </small>
                                            </div>
                                            <p class="mb-1">
                                                <?php echo htmlspecialchars($activity['description'] ?? 'No description available'); ?>
                                            </p>
                                            <small class="text-muted">
                                                <?php if (!empty($activity['username'])): ?>
                                                    By <?php echo htmlspecialchars($activity['full_name'] ?? $activity['username']); ?>
                                                <?php else: ?>
                                                    System
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">No recent activity found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Include footer -->
<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggleSidebar && sidebar) {
        toggleSidebar.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('sidebar-collapsed');
            
            // Save state to cookie
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.cookie = `sidebarCollapsed=${isCollapsed}; path=/; max-age=${60 * 60 * 24 * 30}`; // 30 days
        });
        
        // Initialize sidebar state from cookie
        const isCollapsed = document.cookie.includes('sidebarCollapsed=true');
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            document.body.classList.add('sidebar-collapsed');
        }
    }
});
</script>
