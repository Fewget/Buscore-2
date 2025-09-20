<?php
// Log incoming webhook data (for debugging)
file_put_contents('payhere_webhook.log', date('Y-m-d H:i:s') . " - " . file_get_contents('php://input') . "\n\n", FILE_APPEND);

// Get the JSON data from the webhook
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    die('Invalid data');
}

// Verify the webhook signature (important for security)
$merchant_secret = '8mM5XJ4kQZg6vW9y'; // Replace with your PayHere Merchant Secret for sandbox
$local_md5sig = strtoupper(md5(
    $data['merchant_id'] .
    $data['order_id'] .
    $data['payhere_amount'] .
    $data['payhere_currency'] .
    strtoupper(md5($merchant_secret))
));

if ($local_md5sig !== $data['md5sig']) {
    http_response_code(403);
    die('Invalid signature');
}

// Process the webhook based on the status
require_once '../includes/config.php';

try {
    // Check if this is a payment success notification
    if ($data['status_code'] == 2) { // 2 = success
        // Check if we've already processed this payment
        $stmt = $pdo->prepare("SELECT id FROM payments WHERE payment_id = ?");
        $stmt->execute([$data['payment_id']]);
        
        if (!$stmt->fetch()) {
            // This is a new payment, process it
            // Note: In a real application, you would update your database here
            // to reflect the successful payment
            
            // Log the successful payment
            file_put_contents('payhere_payments.log', 
                date('Y-m-d H:i:s') . " - Payment successful: " . 
                "Order ID: {$data['order_id']}, " .
                "Payment ID: {$data['payment_id']}, " .
                "Amount: {$data['payhere_amount']} {$data['payhere_currency']}\n", 
                FILE_APPEND
            );
        }
    }
    
    // Always return a 200 OK response to acknowledge receipt
    http_response_code(200);
    echo 'Webhook received';
    
} catch (Exception $e) {
    // Log the error
    error_log("Webhook processing error: " . $e->getMessage());
    http_response_code(500);
    die('Error processing webhook');
}
