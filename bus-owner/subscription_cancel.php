<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a bus owner
check_bus_owner_access();

$page_title = 'Payment Cancelled';

// Clear any pending payment from session
if (isset($_SESSION['pending_payment'])) {
    $pendingPayment = $_SESSION['pending_payment'];
    unset($_SESSION['pending_payment']);
    
    // Log the cancellation
    log_activity(
        $_SESSION['user_id'], 
        'payment_cancelled', 
        "User cancelled payment for order #" . ($pendingPayment['order_id'] ?? 'unknown')
    );
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <div class="text-warning mb-3">
                            <i class="fas fa-times-circle fa-5x"></i>
                        </div>
                        <h2 class="h4 mb-3">Payment Cancelled</h2>
                        <p class="text-muted">
                            Your payment was not completed. Your subscription has not been activated.
                        </p>
                    </div>
                    
                    <div class="mt-5">
                        <a href="premium_subscriptions.php" class="btn btn-primary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Back to Subscriptions
                        </a>
                        <a href="contact.php" class="btn btn-outline-secondary">
                            <i class="fas fa-question-circle me-2"></i>Need Help?
                        </a>
                    </div>
                    
                    <div class="mt-4">
                        <p class="small text-muted mb-0">
                            If this was a mistake, you can try the payment again from your subscriptions page.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
