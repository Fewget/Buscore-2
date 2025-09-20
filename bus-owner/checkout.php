<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a bus owner
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'bus_owner') {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

// Check if required parameters are provided
if (!isset($_GET['package_id']) || !isset($_GET['bus_ids'])) {
    header('Location: premium_subscriptions.php');
    exit();
}

$package_id = (int)$_GET['package_id'];
$bus_ids = isset($_GET['bus_ids']) ? explode(',', $_GET['bus_ids']) : [];
$user_id = $_SESSION['user_id'];

// Validate bus IDs
if (empty($bus_ids)) {
    header('Location: premium_subscriptions.php?error=no_buses_selected');
    exit();
}

// Convert all bus IDs to integers
$bus_ids = array_map('intval', $bus_ids);
$placeholders = str_repeat('?,', count($bus_ids) - 1) . '?';

// Fetch package details
$stmt = $pdo->prepare("SELECT * FROM premium_packages WHERE id = ?");
$stmt->execute([$package_id]);
$package = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch details for all selected buses
$sql = "SELECT * FROM buses WHERE id IN ($placeholders) AND user_id = ?";
$params = array_merge($bus_ids, [$user_id]);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$package || empty($buses)) {
    header('Location: premium_subscriptions.php');
    exit();
}

$page_title = 'Checkout - ' . $package['name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Checkout</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Order Summary</h5>
                            <h6><?php echo htmlspecialchars($package['name']); ?></h6>
                            <p class="mb-2">Duration: <?php echo $package['duration_days']; ?> days</p>
                            <p class="mb-2">Selected Buses (<?php echo count($buses); ?>):</p>
                            <ul class="list-group mb-3">
                                <?php foreach ($buses as $bus): ?>
                                <li class="list-group-item">
                                    <?php 
                                    echo !empty($bus['registration_number']) ? 
                                        htmlspecialchars($bus['registration_number']) : 
                                        'Bus #' . $bus['id']; 
                                    ?>
                                    <?php if (!empty($bus['bus_name'])): ?>
                                        (<?php echo htmlspecialchars($bus['bus_name']); ?>)
                                    <?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Price per bus:</span>
                                <span>Rs. <?php echo number_format($package['price'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Total for <?php echo count($buses); ?> buses:</span>
                                <span>Rs. <?php echo number_format($package['price'] * count($buses), 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Tax (0%):</span>
                                <span>Rs. 0.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Total:</h5>
                                <h5>Rs. <?php echo number_format($package['price'] * count($buses), 2); ?></h5>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Payment Method</h5>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payhere" value="payhere" checked>
                                        <label class="form-check-label" for="payhere">
                                            <img src="../assets/img/payhere-logo.png" alt="PayHere" style="height: 30px;">
                                        </label>
                                    </div>
                                    <p class="small text-muted">You will be redirected to PayHere to complete your payment securely.</p>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="../terms.php" target="_blank">Terms and Conditions</a>
                                </label>
                            </div>
                            
                            <form id="payment-form" method="post" action="create_payment_hash.php">
                                <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">
                                <?php foreach ($buses as $bus): ?>
                                    <input type="hidden" name="bus_ids[]" value="<?php echo $bus['id']; ?>">
                                <?php endforeach; ?>
                                
                                <button type="submit" id="pay-now" class="btn btn-primary btn-lg w-100">
                                    Pay Rs. <?php echo number_format($package['price'] * count($buses), 2); ?>
                                </button>
                            </form>
                            
                            <a href="premium_subscriptions.php" class="btn btn-outline-secondary w-100 mt-2">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PayHere Checkout Script -->
<script type="text/javascript" src="https://www.payhere.lk/lib/payhere.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const payNowBtn = document.getElementById('pay-now');
    
    payNowBtn.addEventListener('click', function() {
        if (!document.getElementById('terms').checked) {
            alert('Please accept the terms and conditions');
            return;
        }
        
        // Disable button to prevent multiple clicks
        payNowBtn.disabled = true;
        payNowBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        
        // Get payment hash from server
        const form = document.getElementById('payment-form');
        const formData = new FormData(form);
        
        fetch('create_payment_hash.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Initialize the payment
                const payment = {
                    "sandbox": true, // Set to false for production
                    "merchant_id": "<?php echo PAYHERE_MERCHANT_ID; ?>",
                    "return_url": `<?php echo SITE_URL; ?>/bus-owner/payment_success.php`,
                    "cancel_url": `<?php echo SITE_URL; ?>/bus-owner/checkout.php?package_id=<?php echo $package_id; ?>&bus_id=<?php echo $bus_id; ?>`,
                    "notify_url": `<?php echo SITE_URL; ?>/api/payhere_callback.php`,
                    "order_id": data.payment_id,
                    "items": "<?php echo htmlspecialchars($package['name']); ?> (<?php echo count($buses); ?> buses)",
                    "amount": (<?php echo $package['price'] * count($buses); ?>).toFixed(2),
                    "currency": "LKR",
                    "hash": data.hash,
                    "first_name": "<?php echo htmlspecialchars($_SESSION['user_name']); ?>",
                    "email": "<?php echo htmlspecialchars($_SESSION['user_email']); ?>"
                };
                
                // Show the PayHere payment page
                payhere.startPayment(payment);
            } else {
                alert('Error: ' + (data.message || 'Failed to initialize payment'));
                payNowBtn.disabled = false;
                payNowBtn.textContent = 'Pay Now';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your payment. Please try again.');
            payNowBtn.disabled = false;
            payNowBtn.textContent = 'Pay Now';
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
