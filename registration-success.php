<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in, if not redirect to home
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Set page title
$page_title = 'Registration Successful';
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card mt-5">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    
                    <h1 class="mb-3">Registration Successful!</h1>
                    <p class="lead mb-4">
                        Thank you for registering, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                        Your account has been created successfully.
                    </p>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-center">
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary px-4">
                            <i class="fas fa-home me-2"></i> Go to Homepage
                        </a>
                        <a href="profile.php" class="btn btn-outline-primary px-4">
                            <i class="fas fa-user me-2"></i> View Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">What's next?</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Complete your profile information
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Rate and review your recent bus journeys
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Get personalized bus recommendations
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.success-icon {
    font-size: 5rem;
    color: #28a745;
    margin-bottom: 1.5rem;
}

.success-icon i {
    animation: bounceIn 1s ease-in-out;
}

@keyframes bounceIn {
    0% {
        transform: scale(0.1);
        opacity: 0;
    }
    60% {
        transform: scale(1.2);
        opacity: 1;
    }
    100% {
        transform: scale(1);
    }
}

.btn {
    min-width: 160px;
}
</style>

<?php include 'includes/footer.php'; ?>
