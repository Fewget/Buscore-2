<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Clear any existing session data
                session_unset();
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // For backward compatibility (remove if not needed)
                if ($user['role'] === 'admin') {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_role'] = $user['role'];
                }
                
                // Log the login activity
                if (function_exists('log_activity')) {
                    log_activity($user['id'], 'user_login', 'User logged in');
                }
                
                // Update last login time
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                
                // Redirect to the intended page or dashboard
                if (isset($_SESSION['redirect_url'])) {
                    $redirect_url = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                    header('Location: ' . $redirect_url);
                    exit();
                }
                
                // Redirect to appropriate dashboard
                if ($user['role'] === 'admin') {
                    $redirect = '/admin/dashboard.php';
                } elseif ($user['role'] === 'bus_owner') {
                    $redirect = '/bus-owner/dashboard.php';
                } else {
                    $redirect = '/';
                }
                header('Location: ' . SITE_URL . $redirect);
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}

// Set page title
$page_title = 'Login';
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card mt-5">
                <div class="card-header">
                    <h2 class="text-center">Login to Your Account</h2>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">Login</button>
                        </div>
                        
                        <div class="text-center mb-3">
                            <a href="forgot-password.php" class="text-decoration-none">Forgot your password?</a>
                        </div>
                        
                        <div class="position-relative my-4">
                            <hr>
                            <div class="position-absolute top-50 start-50 translate-middle bg-white px-3">
                                <span class="text-muted">OR</span>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="register.php" class="btn btn-outline-primary btn-lg">Create New Account</a>
                        </div>
                    </form>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
