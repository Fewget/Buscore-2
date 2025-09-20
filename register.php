<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$errors = [];
$success = false;

// Form data
$formData = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'company_name' => '',
    'role' => 'user' // Default role
];

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    error_log('Registration POST data: ' . print_r($_POST, true));
    // Sanitize and validate input
    $formData['username'] = trim($_POST['username'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['full_name'] = trim($_POST['full_name'] ?? '');
    $formData['company_name'] = trim($_POST['company_name'] ?? '');
    $formData['role'] = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($formData['username'])) {
        $errors['username'] = 'Username is required';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $formData['username'])) {
        $errors['username'] = 'Username must be 3-20 characters long and can only contain letters, numbers, and underscores';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$formData['username'], $formData['email']]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $errors['general'] = 'Username or email already exists';
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors['general'] = 'An error occurred. Please try again later.';
        }
    }
    
    // If no errors, create user
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            try {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Determine role
                $role = in_array($formData['role'], ['user', 'bus_owner']) ? $formData['role'] : 'user';
                
                // Debug: Log registration attempt
                error_log("Attempting to register user: " . $formData['username']);
                
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, company_name, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                $result = $stmt->execute([
                    $formData['username'],
                    $formData['email'],
                    $hashedPassword,
                    $formData['full_name'],
                    $formData['company_name'],
                    $role
                ]);
                
                if (!$result) {
                    throw new Exception("Failed to insert user record");
                }
                
                $user_id = $pdo->lastInsertId();
                error_log("User created with ID: " . $user_id);
                
                // If bus owner, create bus owner record
                if ($role === 'bus_owner') {
                    $companyName = !empty($formData['company_name']) ? $formData['company_name'] : ($formData['full_name'] . "'s Bus Service");
                    $stmt = $pdo->prepare("INSERT INTO bus_owners (user_id, company_name, created_at) VALUES (?, ?, NOW())");
                    $result = $stmt->execute([$user_id, $companyName]);
                    
                    if (!$result) {
                        throw new Exception("Failed to create bus owner record");
                    }
                    error_log("Bus owner record created for user ID: " . $user_id);
                }
                
                // Log the registration
                if (function_exists('log_activity')) {
                    log_activity($user_id, 'user_registered', 'User registered as ' . $role);
                }
                
                $pdo->commit();
                error_log("Registration successful for user ID: " . $user_id);
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Registration error: " . $e->getMessage());
                $errors['general'] = 'An error occurred during registration. Please try again. Error: ' . $e->getMessage();
                $success = false;
            }
            
            // Set session and redirect
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $formData['username'];
            $_SESSION['role'] = $role;
            
            // Redirect based on role
            $redirectUrl = $role === 'bus_owner' ? '/bus-owner/dashboard.php' : '/registration-success.php';
            header('Location: ' . SITE_URL . $redirectUrl);
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Registration error: " . $e->getMessage());
            $errors['general'] = 'An error occurred during registration. Please try again.';
        }
    }
}

// Set page title
$page_title = 'Register';
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card mt-5">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0 text-center">
                        <i class="fas fa-user-plus me-2"></i>Create Your Account
                    </h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                                       id="username" name="username" required
                                       value="<?php echo htmlspecialchars($formData['username']); ?>">
                                <?php if (isset($errors['username'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                       id="email" name="email" required
                                       value="<?php echo htmlspecialchars($formData['email']); ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                                       id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($formData['full_name']); ?>" required>
                                <?php if (isset($errors['full_name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['full_name']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control <?php echo isset($errors['company_name']) ? 'is-invalid' : ''; ?>" 
                                       id="company_name" name="company_name" 
                                       value="<?php echo htmlspecialchars($formData['company_name']); ?>"
                                       data-required-for="bus_owner">
                                <?php if (isset($errors['company_name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['company_name']; ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Required for bus owners</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Register As</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="role" id="role_user" value="user" <?php echo $formData['role'] == 'user' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="role_user">
                                    <i class="fas fa-user me-1"></i> Regular User
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="role" id="role_bus_owner" value="bus_owner" <?php echo $formData['role'] == 'bus_owner' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="role_bus_owner">
                                    <i class="fas fa-bus me-1"></i> Bus Owner/Operator
                                </label>
                            </div>
                            <?php if (isset($errors['role'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['role']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                       id="password" name="password" required>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                <?php endif; ?>
                                <div class="form-text">At least 8 characters</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                       id="confirm_password" name="confirm_password" required>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Create Account</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleUser = document.getElementById('role_user');
    const roleBusOwner = document.getElementById('role_bus_owner');
    const companyNameField = document.getElementById('company_name');
    const companyNameGroup = companyNameField.closest('.mb-3');
    
    // Show/hide company name field based on role selection
    function toggleCompanyNameField() {
        if (roleBusOwner.checked) {
            companyNameField.required = true;
            companyNameGroup.style.display = 'block';
        } else {
            companyNameField.required = false;
            companyNameGroup.style.display = 'none';
        }
    }
    
    // Initial state
    toggleCompanyNameField();
    
    // Add event listeners
    roleUser.addEventListener('change', toggleCompanyNameField);
    roleBusOwner.addEventListener('change', toggleCompanyNameField);
});
</script>

<?php include 'includes/footer.php'; ?>
