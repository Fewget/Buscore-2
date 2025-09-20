<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Get payment details from PayHere
$order_id = $_GET['order_id'] ?? '';
$payment_id = $_GET['payment_id'] ?? '';
$payhere_amount = $_GET['payhere_amount'] ?? 0;
$payhere_currency = $_GET['payhere_currency'] ?? '';
$method = $_GET['method'] ?? '';
$status_code = $_GET['status_code'] ?? 0;
$md5sig = $_GET['md5sig'] ?? '';

// Get stored payment details from session
$payment_details = $_SESSION['payment_details'] ?? null;

// Verify the payment
$merchant_secret = '8mM5XJ4kQZg6vW9y'; // Replace with your PayHere Merchant Secret for sandbox
$local_md5sig = strtoupper(md5(
    $merchant_id .
    $order_id .
    $payhere_amount .
    $payhere_currency .
    strtoupper(md5($merchant_secret))
));

$is_valid = ($md5sig === $local_md5sig) && ($status_code == 2);

if ($is_valid && $payment_details) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Calculate subscription end date
        $start_date = date('Y-m-d H:i:s');
        $end_date = null;
        
        if ($payment_details['duration_days'] > 0) {
            $end_date = date('Y-m-d H:i:s', strtotime("+{$payment_details['duration_days']} days"));
        }
        
        // Handle multiple bus subscriptions
        $bus_ids = $payment_details['is_multiple'] ? $payment_details['bus_ids'] : [$payment_details['bus_id']];
        $subscription_ids = [];
        
        foreach ($bus_ids as $bus_id) {
            // Deactivate any existing subscription for this bus
            $stmt = $pdo->prepare("
                UPDATE bus_subscriptions 
                SET is_active = 0 
                WHERE bus_id = ? AND is_active = 1
            ").
            $stmt->execute([$bus_id]);
            
            // Create new subscription
            $stmt = $pdo->prepare("
                INSERT INTO bus_subscriptions 
                (bus_id, package_id, start_date, end_date, payment_amount, payment_id, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $bus_id,
                $payment_details['package_id'],
                $start_date,
                $end_date,
                $payment_details['unit_price'], // Use unit price instead of total amount
                $payment_id . '-' . $bus_id // Append bus ID to make payment ID unique per bus
            ]);
            
            $subscription_ids[] = $pdo->lastInsertId();
        }
        
        // Record payment
        $stmt = $pdo->prepare("
            INSERT INTO payments 
            (user_id, order_id, payment_id, amount, currency, method, status, subscription_ids, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'success', ?, NOW())
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $order_id,
            $payment_id,
            $payhere_amount,
            $payhere_currency,
            $method,
            json_encode($subscription_ids) // Store subscription IDs for reference
        ]);
        
        // Get bus details for success message
        $bus_count = count($bus_ids);
        $bus_message = $bus_count === 1 ? 
            'Bus #' . $bus_ids[0] : 
            "$bus_count buses";
        
        // Commit transaction
        $pdo->commit();
        
        // Clear payment details from session
        unset($_SESSION['payment_details']);
        
        // Redirect to success page with appropriate message
        $message = urlencode("Payment successful! Premium features have been activated for $bus_message.");
        header("Location: premium_subscriptions.php?success=$message");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("Payment processing error: " . $e->getMessage());
        header('Location: premium_subscriptions.php?error=Error processing payment. Please contact support.');
        exit();
    }
} else {
    // Invalid or failed payment
    header('Location: premium_subscriptions.php?error=Payment verification failed. Please contact support if the amount was deducted.');
    exit();
}
