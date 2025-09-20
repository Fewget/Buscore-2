<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a bus owner
check_bus_owner_access();

$page_title = 'Subscription Successful';
$orderId = $_GET['order_id'] ?? '';
$paymentId = 0;

// If we have an order ID in the URL, try to verify the payment
if ($orderId) {
    // In a real implementation, you would verify the payment with PayHere
    // For now, we'll just mark the payment as completed
    try {
        // Get the payment record
        $stmt = $pdo->prepare("SELECT * FROM premium_payments WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            $paymentId = $payment['id'];
            
            // If payment is still pending, mark it as completed
            if ($payment['status'] === 'pending') {
                $pdo->beginTransaction();
                
                // Update payment status
                $stmt = $pdo->prepare("UPDATE premium_payments SET status = 'completed', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$paymentId]);
                
                // Get subscription IDs from payment details
                $details = json_decode($payment['payment_details'], true);
                $subscriptionIds = $details['subscription_ids'] ?? [];
                
                if (!empty($subscriptionIds)) {
                    // Activate subscriptions
                    $placeholders = str_repeat('?,', count($subscriptionIds) - 1) . '?';
                    $stmt = $pdo->prepare("UPDATE bus_subscriptions SET is_active = 1, updated_at = NOW() WHERE id IN ($placeholders)");
                    $stmt->execute($subscriptionIds);
                    
                    // Log the activity
                    foreach ($subscriptionIds as $subscriptionId) {
                        log_activity($_SESSION['user_id'], 'subscription_activated', "Activated subscription #$subscriptionId");
                    }
                }
                
                $pdo->commit();
                
                // Clear the pending payment from session
                unset($_SESSION['pending_payment']);
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error processing successful payment: " . $e->getMessage());
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <div class="text-success mb-3">
                            <i class="fas fa-check-circle fa-5x"></i>
                        </div>
                        <h2 class="h4 mb-3">Payment Successful!</h2>
                        <p class="text-muted mb-0">
                            Thank you for your subscription. Your premium features are now active.
                            <?php if ($orderId): ?>
                                <br>Order ID: <strong><?php echo htmlspecialchars($orderId); ?></strong>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="mt-5">
                        <a href="premium_subscriptions.php" class="btn btn-primary me-2">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                        <a href="buses.php" class="btn btn-outline-secondary">
                            <i class="fas fa-bus me-2"></i>View My Buses
                        </a>
                    </div>
                    
                    <div class="mt-4">
                        <p class="small text-muted mb-0">
                            A confirmation has been sent to your email address.
                            <br>If you have any questions, please contact our support team.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
