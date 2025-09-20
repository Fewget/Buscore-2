<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Log the raw POST data for debugging
$rawPostData = file_get_contents('php://input');
$postData = json_decode($rawPostData, true) ?: $_POST;

// Log the incoming webhook data
error_log("PayHere Webhook Received: " . print_r($postData, true));

// Verify the webhook is from PayHere
function verifyPayHereWebhook($postData) {
    // Get the signature from the headers
    $signature = $_SERVER['HTTP_X_PAYHERE_SIGNATURE'] ?? '';
    
    if (empty($signature)) {
        error_log("PayHere Webhook Error: No signature found");
        return false;
    }
    
    // Reconstruct the data to sign
    $dataToSign = '';
    $fields = [
        'merchant_id', 'order_id', 'payhere_amount', 'payhere_currency', 'status_code',
        'md5sig', 'custom_1', 'custom_2', 'custom_3', 'custom_4', 'custom_5'
    ];
    
    foreach ($fields as $field) {
        if (isset($postData[$field])) {
            $dataToSign .= $postData[$field];
        }
        $dataToSign .= '|';
    }
    
    // Add the secret key
    $dataToSign .= PAYHERE_MERCHANT_SECRET;
    
    // Generate the signature
    $generatedSignature = strtoupper(md5($dataToSign));
    
    // Compare signatures
    return hash_equals($generatedSignature, $signature);
}

// Process the webhook
function processWebhook($data) {
    global $pdo;
    
    $orderId = $data['order_id'] ?? '';
    $statusCode = (int)($data['status_code'] ?? 0);
    $paymentId = (int)($data['custom_1'] ?? 0);
    
    if (empty($orderId)) {
        error_log("PayHere Webhook Error: No order ID provided");
        return false;
    }
    
    try {
        // Get the payment record
        $stmt = $pdo->prepare("SELECT * FROM premium_payments WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            error_log("PayHere Webhook Error: Payment not found for order ID: $orderId");
            return false;
        }
        
        // Skip if already processed
        if ($payment['status'] !== 'pending') {
            error_log("PayHere Webhook: Payment {$payment['id']} already processed with status: {$payment['status']}");
            return true;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update payment status based on PayHere status
        $newStatus = 'failed';
        $isSuccess = false;
        
        switch ($statusCode) {
            case 2: // Success
                $newStatus = 'completed';
                $isSuccess = true;
                break;
                
            case 0: // Pending
                $newStatus = 'pending';
                break;
                
            case -1: // Canceled
                $newStatus = 'cancelled';
                break;
                
            case -2: // Failed
            case -3: // Chargeback
                $newStatus = 'failed';
                break;
                
            default:
                $newStatus = 'failed';
                break;
        }
        
        // Update payment record
        $stmt = $pdo->prepare("
            UPDATE premium_payments 
            SET status = ?, 
                payment_details = JSON_MERGE_PATCH(COALESCE(payment_details, '{}'), ?),
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $paymentDetails = array_merge(
            json_decode($payment['payment_details'] ?? '{}', true) ?: [],
            [
                'payhere_response' => $data,
                'processed_at' => date('Y-m-d H:i:s')
            ]
        );
        
        $stmt->execute([
            $newStatus,
            json_encode($paymentDetails),
            $payment['id']
        ]);
        
        // If payment was successful, activate subscriptions
        if ($isSuccess) {
            $details = json_decode($payment['payment_details'], true);
            $subscriptionIds = $details['subscription_ids'] ?? [];
            
            if (!empty($subscriptionIds)) {
                $placeholders = str_repeat('?,', count($subscriptionIds) - 1) . '?';
                
                // Activate subscriptions
                $stmt = $pdo->prepare("
                    UPDATE bus_subscriptions 
                    SET is_active = 1, 
                        start_date = NOW(),
                        end_date = CASE 
                            WHEN p.duration_days > 0 
                            THEN DATE_ADD(NOW(), INTERVAL p.duration_days DAY) 
                            ELSE NULL 
                        END,
                        updated_at = NOW()
                    FROM premium_packages p
                    WHERE bus_subscriptions.id IN ($placeholders)
                    AND bus_subscriptions.package_id = p.id
                ");
                $stmt->execute($subscriptionIds);
                
                // Log the activity for each subscription
                foreach ($subscriptionIds as $subscriptionId) {
                    log_activity(
                        $payment['user_id'] ?? 0,
                        'subscription_activated',
                        "Subscription #$subscriptionId activated via PayHere webhook"
                    );
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        error_log("PayHere Webhook: Successfully processed payment {$payment['id']} with status: $newStatus");
        return true;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("PayHere Webhook Error: " . $e->getMessage());
        return false;
    }
}

// Main webhook handling
try {
    // Verify the webhook is from PayHere
    if (!verifyPayHereWebhook($postData)) {
        http_response_code(400);
        echo "Invalid signature";
        exit;
    }
    
    // Process the webhook
    if (processWebhook($postData)) {
        http_response_code(200);
        echo "Webhook processed successfully";
    } else {
        http_response_code(400);
        echo "Error processing webhook";
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("PayHere Webhook Exception: " . $e->getMessage());
    echo "An error occurred";
}
