<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Log the incoming request for debugging
file_put_contents('payhere_notification.log', date('Y-m-d H:i:s') . " - " . json_encode($_POST) . "\n", FILE_APPEND);

// Get the POST data from PayHere
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// Verify the payment
$merchant_secret = '8mM5XJ4kQZg6vW9y'; // Replace with your PayHere Merchant Secret for sandbox
$local_md5sig = strtoupper(md5(
    $data['merchant_id'] .
    $data['order_id'] .
    $data['payhere_amount'] .
    $data['payhere_currency'] .
    strtoupper(md5($merchant_secret))
));

$is_valid = ($data['md5sig'] === $local_md5sig) && ($data['status_code'] == 2);

if ($is_valid) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get payment details from the database
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ?");
        $stmt->execute([$data['order_id']]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            // If payment doesn't exist, create it
            $paymentDetails = json_encode($data);
            $insertStmt = $pdo->prepare("
                INSERT INTO payments 
                (order_id, amount, status, payment_method, payment_details, created_at, updated_at)
                VALUES (?, ?, ?, 'payhere', ?, NOW(), NOW())
            ");
            
            $insertStmt->execute([
                $data['order_id'],
                $data['payhere_amount'],
                $data['payment_status'],
                $paymentDetails
            ]);
            
            $pdo->commit();
            http_response_code(200);
            echo 'Payment created and notification processed';
            exit();
        } else {
            // Update existing payment status
            $updateStmt = $pdo->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE order_id = ?");
            $updateStmt->execute([$data['payment_status'], $data['order_id']]);
            $pdo->commit();
            http_response_code(200);
            echo 'Payment status updated';
            exit();
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Log the error
        error_log("Payment Notification Error: " . $e->getMessage());
        
        http_response_code(500);
        echo 'Error processing notification';
        exit();
    }
} else {
    // Invalid request
    http_response_code(400);
    echo 'Invalid notification';
    exit();
}
