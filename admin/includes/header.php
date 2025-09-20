<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config and functions
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check admin access
checkAdminAccess();

// Set default page title if not set
if (!isset($page_title)) {
    $page_title = 'Admin Panel';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Panel'; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/admin-styles.css" rel="stylesheet">
    
    <!-- Dashboard CSS -->
    <link href="<?php echo SITE_URL; ?>/admin/assets/css/dashboard.css" rel="stylesheet">
    
    <!-- Test styles - remove after verification -->
    <style>
        body { background-color: #f0f8ff !important; }
        h1, h2, h3, h4, h5, h6 { color: #2c3e50 !important; }
    </style>
    
    <!-- Custom CSS is now in admin-styles.css -->
</head>
<body class="admin-panel">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-white sidebar py-3">
                <div class="text-center mb-4">
                    <h4>Admin Panel</h4>
                </div>
                <hr>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link sidebar-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sidebar-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'buses.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/buses.php">
                            <i class="fas fa-bus"></i> Buses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sidebar-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/reviews.php">
                            <i class="fas fa-star"></i> Reviews
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sidebar-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'premium-features.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/admin/premium-features.php">
                            <i class="fas fa-crown"></i> Premium
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link sidebar-link text-white" href="<?php echo SITE_URL; ?>">
                            <i class="fas fa-arrow-left"></i> Back to Site
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo SITE_URL; ?>/logout.php" class="nav-link sidebar-link text-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
