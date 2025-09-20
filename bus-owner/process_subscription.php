<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a bus owner
check_bus_owner_access();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

try {
    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate input
    $packageId = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);
    $busIds = isset($_POST['bus_ids']) ? explode(',', $_POST['bus_ids']) : [];
    $userId = $_SESSION['user_id'];

    if (!$packageId) {
        throw new Exception('Invalid package selected');
    }

    if (empty($busIds)) {
        throw new Exception('No buses selected');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Get package details
    $stmt = $pdo->prepare("SELECT * FROM premium_packages WHERE id = ? AND is_active = 1");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$package) {
        throw new Exception('Selected package is not available');
    }

    // Verify bus ownership
    $placeholders = str_repeat('?,', count($busIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id FROM buses WHERE id IN ($placeholders) AND user_id = ?");
    $stmt->execute(array_merge($busIds, [$userId]));
    $validBusIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($validBusIds) !== count($busIds)) {
        throw new Exception('Invalid bus selection');
    }

    // Calculate total amount
    $totalAmount = $package['price'] * count($validBusIds);
    $subscriptionIds = [];
    $subscriptionStart = date('Y-m-d H:i:s');
    $subscriptionEnd = $package['duration_days'] > 0 
        ? date('Y-m-d H:i:s', strtotime("+{$package['duration_days']} days"))
        : null;

    // Create subscription records
    $stmt = $pdo->prepare("
        INSERT INTO bus_subscriptions 
        (bus_id, package_id, owner_id, start_date, end_date, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())
    ");

    foreach ($validBusIds as $busId) {
        $stmt->execute([
            $busId,
            $packageId,
            $userId,
            $subscriptionStart,
            $subscriptionEnd
        ]);
        $subscriptionIds[] = $pdo->lastInsertId();
    }

    // Create payment record
    $stmt = $pdo->prepare("
        INSERT INTO premium_payments 
        (subscription_id, amount, payment_method, status, payment_details, created_at, updated_at)
        VALUES (?, ?, 'payhere', 'pending', ?, NOW(), NOW())
    ");
    
    // For now, we'll use the first subscription ID as the main payment reference
    // In a real implementation, you might want to create a separate payment record for each subscription
    $paymentDetails = json_encode([
        'subscription_ids' => $subscriptionIds,
        'package_id' => $packageId,
        'bus_ids' => $validBusIds,
        'user_id' => $userId
    ]);
    
    $stmt->execute([$subscriptionIds[0], $totalAmount, $paymentDetails]);
    $paymentId = $pdo->lastInsertId();

    // Generate a unique order ID
    $orderId = 'SUB' . str_pad($paymentId, 8, '0', STR_PAD_LEFT);
    
    // Update payment with order ID
    $stmt = $pdo->prepare("UPDATE premium_payments SET order_id = ? WHERE id = ?");
    $stmt->execute([$orderId, $paymentId]);

    // Commit transaction
    $pdo->commit();

    // Prepare payment data for PayHere
    $payhereData = [
        'merchant_id' => PAYHERE_MERCHANT_ID, // Defined in config.php
        'return_url' => SITE_URL . '/bus-owner/subscription_success.php',
        'cancel_url' => SITE_URL . '/bus-owner/subscription_cancel.php',
        'notify_url' => SITE_URL . '/api/payhere_webhook.php',
        'first_name' => $_SESSION['user_first_name'] ?? 'Bus',
        'last_name' => $_SESSION['user_last_name'] ?? 'Owner',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '0771234567',
        'address' => 'Colombo',
        'city' => 'Colombo',
        'country' => 'Sri Lanka',
        'order_id' => $orderId,
        'items' => $package['name'] . ' (x' . count($validBusIds) . ')',
        'currency' => 'LKR',
        'amount' => number_format($totalAmount, 2, '.', '')
    ];

    // Add hash for security
    $hash = strtoupper(
        md5(
            PAYHERE_MERCHANT_ID .
            $orderId . 
            number_format($totalAmount, 2, '.', '') . 
            'LKR' . 
            strtoupper(md5(PAYHERE_MERCHANT_SECRET))
        )
    );
    $payhereData['hash'] = $hash;

    // Store payment ID in session for verification
    $_SESSION['pending_payment'] = [
        'payment_id' => $paymentId,
        'order_id' => $orderId,
        'amount' => $totalAmount,
        'subscription_ids' => $subscriptionIds
    ];

    // Set the appropriate redirect URL based on environment
    $response['success'] = true;
    $response['message'] = 'Redirecting to payment gateway...';
    
    if (PAYHERE_SANDBOX) {
        // In sandbox mode, redirect to success page directly for testing
        $response['redirect'] = SITE_URL . '/bus-owner/subscription_success.php?order_id=' . $orderId;
    } else {
        // In production, redirect to PayHere
        $response['redirect'] = PAYHERE_CHECKOUT_URL . '?' . http_build_query($payhereData);
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
    
    // Log the error
    error_log("Subscription Error: " . $e->getMessage());
    error_log("Stack Trace: " . $e->getTraceAsString());
}

// Ensure we always return a JSON response
try {
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    // Fallback error response if JSON encoding fails
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error processing your request.'
    ]);
}
exit;
