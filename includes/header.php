<?php
// Prevent any output before headers
if (ob_get_level() == 0) {
    ob_start();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define site URL if not defined
if (!defined('SITE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = dirname($_SERVER['SCRIPT_NAME']);
    $base_path = rtrim(str_replace('\\', '/', $script_name), '/');
    define('SITE_URL', $protocol . $host . $base_path);
}

// Set default page title if not set
$page_title = $page_title ?? 'BuScore - Rate and Review Buses';

// Get current page for active class
$current_page = basename($_SERVER['PHP_SELF']);

// Clear any previous output
if (ob_get_level() > 0) {
    ob_end_clean();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/BS/assets/css/style.css">
    <link rel="stylesheet" href="/BS/assets/css/rating.css">
    <link rel="stylesheet" href="/BS/assets/css/header.css">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Initialize Bootstrap components -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all dropdowns
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl, {
                offset: [0, 5],
                boundary: 'clippingParents',
                reference: 'toggle',
                display: 'dynamic'
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.matches('.dropdown-toggle') && !event.target.closest('.dropdown-menu')) {
                var dropdowns = document.getElementsByClassName('dropdown-menu');
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        var dropdownInstance = bootstrap.Dropdown.getInstance(openDropdown.previousElementSibling);
                        if (dropdownInstance) {
                            dropdownInstance.hide();
                        }
                    }
                }
            }
        });
    });
    </script>
    
    <!-- Custom JS -->
    <script src="/BS/assets/js/main.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <header class="header">
        <div class="top-bar">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <!-- Brand/Logo -->
                    <a href="<?php echo SITE_URL; ?>" class="brand">
                        <i class="fas fa-bus"></i>
                        <span>BuScore</span>
                    </a>
                    
                    <!-- Desktop Navigation -->
                    <nav class="main-nav d-none d-lg-block">
                        <ul class="nav">
                            <li class="nav-item">
                                <a href="<?php echo SITE_URL; ?>" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-home me-1"></i>Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SITE_URL; ?>/about.php" class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-info-circle me-1"></i>About
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SITE_URL; ?>/contact.php" class="nav-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-envelope me-1"></i>Contact
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo SITE_URL; ?>/report-bus.php" class="nav-link <?php echo $current_page === 'report-bus.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Report Bus
                                </a>
                            </li>
                            <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'bus_owner'): ?>
                            <li class="nav-item">
                                <a href="<?php echo SITE_URL; ?>/add-bus.php" class="nav-link <?php echo $current_page === 'add-bus.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-plus-circle me-1"></i>Add Bus
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    
                    <!-- User Dropdown -->
                    <div class="d-flex align-items-center">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="dropdown">
                                <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i>
                                    <span class="d-none d-lg-inline"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/<?php echo ($_SESSION['role'] === 'bus_owner') ? 'bus-owner/dashboard.php' : 'profile.php'; ?>">
                                            <i class="fas fa-user me-2"></i> <?php echo ($_SESSION['role'] === 'bus_owner') ? 'Dashboard' : 'My Profile'; ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/my-ratings.php">
                                            <i class="fas fa-star me-2"></i> My Ratings
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php">
                                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="d-flex">
                                <?php if (basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
                                    <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-light me-2">
                                        <i class="fas fa-sign-in-alt me-1"></i> Login
                                    </a>
                                <?php endif; ?>
                                <?php if (basename($_SERVER['PHP_SELF']) !== 'register.php'): ?>
                                    <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-light text-primary">
                                        <i class="fas fa-user-plus me-1"></i> Register
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Mobile menu button -->
                        <button class="btn mobile-menu-btn d-lg-none ms-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search Bar -->
        <div class="search-bar py-3" style="background-color: rgba(0,0,0,0.1);">
            <div class="container-fluid px-4">
                <form action="<?php echo SITE_URL; ?>/search.php" method="get" class="search-form" id="headerSearchForm">
                    <div class="input-group">
                        <input 
                            type="text" 
                            name="q" 
                            id="headerSearchInput"
                            class="form-control form-control-lg" 
                            placeholder="Search by bus number or route..." 
                            value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"
                            required
                            autocomplete="off"
                        >
                        <button class="btn btn-warning" type="submit" id="headerSearchButton">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const searchForm = document.getElementById('headerSearchForm');
                    const searchInput = document.getElementById('headerSearchInput');
                    
                    if (searchForm && searchInput) {
                        searchForm.addEventListener('submit', function(e) {
                            if (!searchInput.value.trim()) {
                                e.preventDefault();
                                searchInput.focus();
                            }
                        });
                        
                        // Focus the search input when the search page loads
                        if (window.location.pathname.endsWith('search.php')) {
                            searchInput.focus();
                            searchInput.select();
                        }
                    }
                });
                </script>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div class="collapse d-md-none bg-dark" id="mobileMenu">
            <div class="container py-3">
                <div class="list-group">
                    <a href="<?php echo SITE_URL; ?>" class="list-group-item list-group-item-action bg-dark text-white border-0 <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home me-2"></i> Home
                    </a>
                    <a href="<?php echo SITE_URL; ?>/about.php" class="list-group-item list-group-item-action bg-dark text-white border-0 <?php echo $current_page === 'about.php' ? 'active' : ''; ?>">
                        <i class="fas fa-info-circle me-2"></i> About
                    </a>
                    <a href="<?php echo SITE_URL; ?>/contact.php" class="list-group-item list-group-item-action bg-dark text-white border-0 <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope me-2"></i> Contact
                    </a>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo SITE_URL; ?>/profile.php" class="list-group-item list-group-item-action bg-dark text-white border-0 <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user me-2"></i> My Profile
                        </a>
                        <a href="<?php echo SITE_URL; ?>/my-ratings.php" class="list-group-item list-group-item-action bg-dark text-white border-0 <?php echo $current_page === 'my-ratings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-star me-2"></i> My Ratings
                        </a>
                        <a href="<?php echo SITE_URL; ?>/logout.php" class="list-group-item list-group-item-action bg-dark text-danger border-0">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="list-group-item list-group-item-action bg-dark text-white border-0 <?php echo $current_page === 'login.php' ? 'active' : ''; ?>">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </a>
                        <a href="<?php echo SITE_URL; ?>/register.php" class="list-group-item list-group-item-action bg-dark text-white border-0 <?php echo $current_page === 'register.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-plus me-2"></i> Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Offcanvas Menu -->
    <div class="offcanvas offcanvas-end d-lg-none" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="mobileMenuLabel">Menu</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home me-2"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/about.php" class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>">
                        <i class="fas fa-info-circle me-2"></i> About
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/contact.php" class="nav-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope me-2"></i> Contact
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/report-bus.php" class="nav-link <?php echo $current_page === 'report-bus.php' ? 'active' : ''; ?>">
                        <i class="fas fa-exclamation-triangle me-2"></i> Report Bus
                    </a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'bus_owner'): ?>
                    <li class="nav-item">
                        <a href="<?php echo SITE_URL; ?>/add-bus.php" class="nav-link <?php echo $current_page === 'add-bus.php' ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle me-2"></i> Add Bus
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="<?php echo SITE_URL; ?>/my-ratings.php" class="nav-link <?php echo $current_page === 'my-ratings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-star me-2"></i> My Ratings
                        </a>
                    </li>
                    <li class="nav-item mt-auto">
                        <a href="<?php echo SITE_URL; ?>/logout.php" class="nav-link text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item mt-3">
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Initialize Bootstrap components -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Enable all tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Enable all popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    });
    </script>
    
    <main class="container flex-grow-1">
