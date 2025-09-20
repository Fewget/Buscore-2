<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a bus owner
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'bus_owner') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['package_id']) || (!isset($_POST['bus_id']) && !isset($_POST['bus_ids']))) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit();
}

$package_id = (int)$_POST['package_id'];

// Handle both bus_id and bus_ids[] parameters
if (isset($_POST['bus_ids'])) {
    $bus_ids = is_array($_POST['bus_ids']) ? array_map('intval', $_POST['bus_ids']) : [(int)$_POST['bus_ids']];
} else {
    $bus_ids = is_array($_POST['bus_id']) ? array_map('intval', $_POST['bus_id']) : [(int)$_POST['bus_id']];
}

$is_multiple = count($bus_ids) > 1;
$total_buses = count($bus_ids);
$user_id = $_SESSION['user_id'];

// Fetch package details
$stmt = $pdo->prepare("SELECT * FROM premium_packages WHERE id = ?");
$stmt->execute([$package_id]);
$package = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$package) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid package']);
    exit();
}

// Fetch bus details for the first bus (for payment details)
$bus_id = $bus_ids[0]; // Use the first bus for payment details
$stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ? AND user_id = ?");
$stmt->execute([$bus_id, $user_id]);
$bus = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bus) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid bus']);
    exit();
}

// If multiple buses, verify all bus IDs belong to the user
if ($is_multiple && count($bus_ids) > 1) {
    $placeholders = rtrim(str_repeat('?,', count($bus_ids)), ',');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM buses WHERE id IN ($placeholders) AND user_id = ?");
    $params = array_merge($bus_ids, [$user_id]);
    $stmt->execute($params);
    $valid_buses = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($valid_buses !== count($bus_ids)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'One or more invalid buses selected']);
        exit();
    }
}

// Generate a unique payment ID
$payment_id = 'PAY-' . time() . '-' . $user_id . '-' . $package_id;

// Calculate the hash
$merchant_secret = '8mM5XJ4kQZg6vW9y'; // Replace with your PayHere Merchant Secret for sandbox
$hash = strtoupper(
    md5(
        PAYHERE_MERCHANT_ID .
        $payment_id .
        number_format($package['price'], 2, '.', '') .
        'LKR' .
        strtoupper(md5($merchant_secret))
    )
);

// Calculate total amount (price per bus * number of buses)
$total_amount = $package['price'] * $total_buses;

// Store payment details in session for verification after payment
$_SESSION['payment_details'] = [
    'package_id' => $package_id,
    'bus_ids' => $bus_ids, // Store all bus IDs
    'is_multiple' => $is_multiple,
    'total_buses' => $total_buses,
    'amount' => $total_amount,
    'unit_price' => $package['price'],
    'duration_days' => $package['duration_days'],
    'payment_id' => $payment_id
];

// Prepare the return URL
$return_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]" . "/BS/bus-owner/payment_success.php";
$cancel_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]" . "/BS/bus-owner/premium_subscriptions.php";
$notify_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]" . "/BS/bus-owner/payment_notify.php";

// Build the PayHere payment URL
$payhere_url = "https://sandbox.payhere.lb/pay/checkout"; // Use https://www.payhere.lb/pay/checkout for live

// Return the payment details with the payment URL
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'payment_id' => $payment_id,
    'hash' => $hash,
    'payment_url' => $payhere_url,
    'params' => [
        'merchant_id' => PAYHERE_MERCHANT_ID,
        'return_url' => $return_url,
        'cancel_url' => $cancel_url,
        'notify_url' => $notify_url,
        'first_name' => $bus['owner_name'],
        'last_name' => '',
        'email' => $_SESSION['email'],
        'phone' => $bus['contact_number'] ?? '',
        'address' => $bus['address'] ?? '',
        'city' => $bus['city'] ?? '',
        'country' => 'Lebanon',
        'order_id' => $payment_id,
        'items' => $package['name'] . ' Subscription' . ($total_buses > 1 ? " (x$total_buses)" : ''),
        'currency' => 'LKR',
        'amount' => number_format($total_amount, 2, '.', ''),
        'hash' => $hash,
        'custom_1' => $package_id,
        'custom_2' => $is_multiple ? 'multiple' : $bus_id,
        'custom_3' => $is_multiple ? json_encode($bus_ids) : ''
    ]
]);
?>
