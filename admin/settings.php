<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define SITE_URL if not defined
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/BS');
}

// Include config and functions
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check admin access
checkAdminAccess();

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update settings based on form submission
        if (isset($_POST['update_general'])) {
            // Update general settings
            $site_name = $_POST['site_name'] ?? '';
            $site_description = $_POST['site_description'] ?? '';
            $admin_email = $_POST['admin_email'] ?? '';
            $timezone = $_POST['timezone'] ?? 'UTC';
            
            // Here you would typically save these to a database or config file
            // For now, we'll just show a success message
            $message = '<div class="alert alert-success">General settings updated successfully!</div>';
        }
        // Handle username change
        elseif (isset($_POST['update_username'])) {
            $new_username = trim($_POST['new_username'] ?? '');
            
            // Validate username
            if (empty($new_username)) {
                throw new Exception("Username cannot be empty");
            }
            
            if (strlen($new_username) < 3) {
                throw new Exception("Username must be at least 3 characters long");
            }
            
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
                throw new Exception("Username can only contain letters, numbers, and underscores");
            }
            
            // In a real application, you would:
            // 1. Check if username already exists
            // 2. Update the username in the database
            
            // For demonstration, we'll just update the session and show a success message
            $_SESSION['admin_username'] = $new_username;
            $message = '<div class="alert alert-success">Username updated successfully!</div>';
            
            /*
            // Example database update code:
            $stmt = $pdo->prepare("UPDATE admin_users SET username = ? WHERE id = ?");
            $stmt->execute([$new_username, $_SESSION['admin_id']]);
            
            // Update session
            $_SESSION['admin_username'] = $new_username;
            $message = '<div class="alert alert-success">Username updated successfully!</div>';
            */
        }
        // Handle password change
        elseif (isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate inputs
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception("All fields are required");
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            }
            
            if (strlen($new_password) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }
            
            // In a real application, you would:
            // 1. Verify current password against the database
            // 2. Hash the new password
            // 3. Update the password in the database
            
            // For demonstration, we'll just show a success message
            $message = '<div class="alert alert-success">Password updated successfully!</div>';
            
            // In a real application, you would do something like:
            /*
            $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $user = $stmt->fetch();
            
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }
            
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            $stmt->execute([$new_password_hash, $_SESSION['admin_id']]);
            
            $message = '<div class="alert alert-success">Password updated successfully!</div>';
            */
        }
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Get current settings (in a real app, these would come from a database)
$site_name = defined('SITE_NAME') ? SITE_NAME : 'Buscore';
$site_description = defined('SITE_DESCRIPTION') ? SITE_DESCRIPTION : '';
$admin_email = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '';
$current_timezone = date_default_timezone_get();

// Set page title
$page_title = 'Site Settings';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-cog me-2"></i>Site Settings</h1>
    </div>

    <?php if ($message): ?>
        <div class="mb-4"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3 mb-4">
            <div class="settings-sidebar">
                <div class="nav flex-column nav-pills" id="settingsTabs" role="tablist" aria-orientation="vertical">
                    <a class="nav-link active" id="general-tab" data-bs-toggle="pill" href="#general" role="tab" aria-controls="general" aria-selected="true">
                        <i class="fas fa-sliders-h me-2"></i> General
                    </a>
                    <a class="nav-link" id="appearance-tab" data-bs-toggle="pill" href="#appearance" role="tab" aria-controls="appearance">
                        <i class="fas fa-palette me-2"></i> Appearance
                    </a>
                    <a class="nav-link" id="email-tab" data-bs-toggle="pill" href="#email" role="tab" aria-controls="email">
                        <i class="fas fa-envelope me-2"></i> Email Settings
                    </a>
                    <a class="nav-link" id="seo-tab" data-bs-toggle="pill" href="#seo" role="tab" aria-controls="seo">
                        <i class="fas fa-search me-2"></i> SEO
                    </a>
                    <a class="nav-link" id="maintenance-tab" data-bs-toggle="pill" href="#maintenance" role="tab" aria-controls="maintenance">
                        <i class="fas fa-tools me-2"></i> Maintenance
                    </a>
                    <a class="nav-link" id="security-tab" data-bs-toggle="pill" href="#security" role="tab" aria-controls="security">
                        <i class="fas fa-shield-alt me-2"></i> Security
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="tab-content" id="settingsTabContent">
                <!-- General Settings Tab -->
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <div class="card settings-card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>General Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="site_name" class="form-label">Site Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                            <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>">
                                        </div>
                                        <div class="form-text">The name of your website</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="site_description" class="form-label">Site Description</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                            <input type="text" class="form-control" id="site_description" name="site_description" value="<?php echo htmlspecialchars($site_description); ?>">
                                        </div>
                                        <div class="form-text">A brief description of your website</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="admin_email" class="form-label">Admin Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($admin_email); ?>">
                                        </div>
                                        <div class="form-text">Administrator contact email</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="timezone" class="form-label">Timezone</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                            <select class="form-select" id="timezone" name="timezone">
                                                <option value="Asia/Colombo" <?php echo ($current_timezone === 'Asia/Colombo') ? 'selected' : ''; ?>>Colombo (UTC +5:30)</option>
                                                <option value="UTC" <?php echo ($current_timezone === 'UTC') ? 'selected' : ''; ?>>UTC</option>
                                                <!-- Add more timezones as needed -->
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" name="update_general" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Appearance Tab -->
                <div class="tab-pane fade" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-palette me-2"></i>Appearance Settings</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Customize the look and feel of your website.</p>
                            <!-- Add appearance settings here -->
                        </div>
                    </div>
                </div>
                
                <!-- Email Tab -->
                <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Email Settings</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Configure your email settings.</p>
                            <!-- Add email settings here -->
                        </div>
                    </div>
                </div>
                
                <!-- SEO Tab -->
                <div class="tab-pane fade" id="seo" role="tabpanel" aria-labelledby="seo-tab">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-search me-2"></i>SEO Settings</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Optimize your website for search engines.</p>
                            <!-- Add SEO settings here -->
                        </div>
                    </div>
                </div>
                
                <!-- Maintenance Tab -->
                <div class="tab-pane fade" id="maintenance" role="tabpanel" aria-labelledby="maintenance-tab">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Maintenance Mode</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Take your site offline for maintenance.</p>
                            <!-- Add maintenance settings here -->
                        </div>
                    </div>
                </div>
                
                <!-- Security Tab -->
                <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Settings</h5>
                        </div>
                        <div class="card-body">
                            <!-- Change Username Section -->
                            <div class="mb-5">
                                <h6 class="mb-4">Change Username</h6>
                                <form method="POST" action="" class="mb-5">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="current_username" class="form-label">Current Username</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                    <input type="text" class="form-control" id="current_username" name="current_username" value="<?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>" disabled>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="new_username" class="form-label">New Username</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-user-edit"></i></span>
                                                    <input type="text" class="form-control" id="new_username" name="new_username" required minlength="3">
                                                </div>
                                                <div class="form-text">Minimum 3 characters, letters and numbers only</div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-end">
                                                <button type="submit" name="update_username" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Update Username
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Change Password Section -->
                            <div>
                                <h6 class="mb-4">Change Password</h6>
                                <form method="POST" action="" id="changePasswordForm">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                            </div>
                                            <div class="form-text">Minimum 8 characters, include numbers and special characters</div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-end">
                                            <button type="submit" name="change_password" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Password
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Add custom styles
$custom_css = "
    <style>
        /* Custom styles for settings page */
        .settings-card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .settings-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }
        
        .settings-card .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1.25rem 1.5rem;
        }
        
        .settings-card .card-header h5 {
            color: #4e73df;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .settings-card .card-body {
            padding: 2rem;
        }
        
        .settings-card .form-label {
            font-weight: 600;
            color: #5a5c69;
            margin-bottom: 0.5rem;
        }
        
        .settings-card .form-control, 
        .settings-card .form-select {
            border: 1px solid #d1d3e2;
            border-radius: 0.35rem;
            padding: 0.75rem 1rem;
            transition: all 0.2s;
        }
        
        .settings-card .form-control:focus, 
        .settings-card .form-select:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .settings-card .btn-primary {
            background-color: #4e73df;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 0.35rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
        }
        
        .settings-card .btn-primary:hover {
            background-color: #2e59d9;
            transform: translateY(-1px);
        }
        
        /* Sidebar styles */
        .settings-sidebar {
            position: sticky;
            top: 1rem;
        }
        
        .settings-sidebar .nav-pills .nav-link {
            color: #5a5c69;
            padding: 0.75rem 1rem;
            border-radius: 0.35rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }
        
        .settings-sidebar .nav-pills .nav-link i {
            width: 1.5rem;
            text-align: center;
            margin-right: 0.5rem;
        }
        
        .settings-sidebar .nav-pills .nav-link:hover,
        .settings-sidebar .nav-pills .nav-link.active {
            background-color: #f8f9fc;
            color: #4e73df;
        }
        
        .settings-sidebar .nav-pills .nav-link.active {
            font-weight: 600;
        }
        
        /* Form styles */
        .form-text {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .input-group-text {
            background-color: #f8f9fc;
            border: 1px solid #d1d3e2;
        }
        
        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .settings-sidebar {
                position: static;
                margin-bottom: 2rem;
            }
            
            .settings-card .card-body {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .settings-card .card-body {
                padding: 1.25rem;
            }
            
            .settings-card .btn-primary {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Animation for tab transitions */
        .tab-pane.fade.show {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Custom scrollbar for settings content */
        .tab-content {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            padding-right: 0.5rem;
        }
        
        .tab-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .tab-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .tab-content::-webkit-scrollbar-thumb {
            background: #d1d3e2;
            border-radius: 3px;
        }
        
        .tab-content::-webkit-scrollbar-thumb:hover {
            background: #b7b9cc;
        }
    </style>";

// Output the custom CSS
echo $custom_css;
?>

<!-- Include settings page JavaScript -->
<script src="/BS/assets/js/settings.js"></script>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
