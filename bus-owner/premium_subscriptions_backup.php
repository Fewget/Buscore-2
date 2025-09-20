<?php
// Start session and include required files
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a bus owner
check_bus_owner_access();

$page_title = 'Premium Subscriptions';
$success = isset($_GET['success']) ? trim($_GET['success']) : '';
$error = isset($_GET['error']) ? trim($_GET['error']) : '';

// Get bus owner's buses
$buses = [];
$subscriptions = [];

try {
    $busesStmt = $pdo->prepare("SELECT * FROM buses WHERE user_id = ? ORDER BY registration_number ASC");
    $busesStmt->execute([$_SESSION['user_id']]);
    $buses = $busesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get active subscriptions for these buses
    $busIds = array_column($buses, 'id');

    if (!empty($busIds)) {
        $placeholders = str_repeat('?,', count($busIds) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT s.*, p.name as package_name, p.features, p.duration_days, p.price,
                   b.registration_number, b.bus_name
            FROM bus_subscriptions s
            JOIN premium_packages p ON s.package_id = p.id
            JOIN buses b ON s.bus_id = b.id
            WHERE s.bus_id IN ($placeholders) 
            AND s.is_active = 1
            AND (s.end_date IS NULL OR s.end_date >= NOW())
        
        ");
        $stmt->execute($busIds);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['bus_id'])) {
                $subscriptions[$row['bus_id']] = $row;
            }
        }
    }
} catch (PDOException $e) {
    error_log('Database error in premium_subscriptions.php: ' . $e->getMessage());
    $error = 'An error occurred while loading your buses. Please try again later.';
}

// Get available packages
$packages = [];
try {
    $packagesStmt = $pdo->query("SELECT * FROM premium_packages WHERE is_active = 1 ORDER BY price ASC");
    if ($packagesStmt) {
        $packages = $packagesStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('Error loading premium packages: ' . $e->getMessage());
    $error = 'An error occurred while loading available packages. Please try again later.';
}
?>

<?php include '../includes/header.php'; ?>

<style>
    /* Modal z-index fixes */
    #paymentConfirmationModal {
        z-index: 1060 !important;
    }
    
    .modal-backdrop + .modal-backdrop {
        z-index: 1059 !important;
    }
    
    #paymentConfirmationModal.modal {
        z-index: 1061 !important;
    }
    
    /* Ensure modal content is clickable */
    .modal-content {
        pointer-events: auto;
        position: relative;
        z-index: 1062;
    }
    
    /* Fix for modal stacking */
    .modal {
        z-index: 1060 !important;
    }
    
    /* Make sure modal is visible */
    .modal.show {
        display: block !important;
        overflow-x: hidden;
        overflow-y: auto;
    }
    
    /* Ensure modal dialog is centered */
    .modal-dialog-centered {
        display: flex !important;
        align-items: center;
        min-height: calc(100% - 1rem);
    }
</style>

<div class="container-fluid">
    <!-- Status Messages -->
    <?php 
if (!empty($success)) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($success);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

if (!empty($error)) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($error);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Bus Owner Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0"><?php echo htmlspecialchars($page_title); ?></h1>
                <p class="text-muted mb-0">Upgrade your bus listings with premium features</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

    <?php 
    if (empty($buses)) { 
        echo '<div class="alert alert-warning">';
        echo '<i class="fas fa-exclamation-triangle me-2"></i>';
        echo 'You don\'t have any buses registered yet. Please add buses to your account to subscribe to premium features.';
        echo '</div>';
        echo '<div class="text-center mt-4">';
        echo '<a href="add-bus.php" class="btn btn-primary">';
        echo '<i class="fas fa-plus me-2"></i> Add Your First Bus';
        echo '</a>';
        echo '</div>';
    } else { 
        echo '<!-- Bus Selection for Premium Features -->';
        echo '<div class="card shadow mb-4">';
        echo '<div class="card-header py-3">';
        echo '<h6 class="m-0 font-weight-bold text-primary">Select Buses for Premium Features</h6>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<div class="row">';
        echo '<div class="col-md-12 mb-3">';
        echo '<div class="form-check">';
        echo '<input class="form-check-input" type="checkbox" id="selectAllBuses">';
        echo '<label class="form-check-label fw-bold" for="selectAllBuses">';
        echo 'Select All Buses';
        echo '</label>';
        echo '</div>';
        echo '</div>';
                    <?php 
                    foreach ($buses as $bus) {
                        echo '<div class="col-md-4 mb-2">';
                        echo '<div class="form-check">';
                        echo '<input class="form-check-input bus-checkbox" type="checkbox" ';
                        echo 'id="bus_' . $bus['id'] . '" ';
                        echo 'value="' . $bus['id'] . '">';
                        echo '<label class="form-check-label" for="bus_' . $bus['id'] . '">';
                        echo !empty($bus['registration_number']) ? htmlspecialchars($bus['registration_number']) : 'Bus #' . $bus['id'];
                        if (!empty($bus['bus_name'])) {
                            echo ' (' . htmlspecialchars($bus['bus_name']) . ')';
                        }
                        echo '</label>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Available Packages -->
        <div class="row">
            <?php 
            if (!empty($packages)) {
                foreach ($packages as $package) { 
                    $features = json_decode($package['features'], true);
                    $isPopular = $package['id'] == 2; // Mark the middle package as popular
                    
                    echo '<div class="col-md-4 mb-4">';
                    echo '<div class="card h-100 package-card ' . ($isPopular ? 'border-primary border-2' : 'border-0') . '">';
                    
                    if ($isPopular) {
                        echo '<div class="card-header bg-primary text-white text-center py-2">';
                        echo '<small class="fw-bold">MOST POPULAR</small>';
                        echo '</div>';
                    } else {
                        echo '<div class="card-header bg-white"></div>';
                    }
                    
                    echo '<div class="card-body text-center p-4">';
                    echo '<h3 class="card-title">' . htmlspecialchars($package['name']) . '</h3>';
                    echo '<div class="my-4">';
                    echo '<span class="display-4 fw-bold">Rs. ' . number_format($package['price'], 2) . '</span>';
                    echo '<span class="text-muted">/month</span>';
                    echo '</div>';
                    
                    echo '<ul class="list-unstyled mb-4">';
                    if (is_array($features) && !empty($features)) {
                        foreach ($features as $feature) {
                            if (!empty($feature)) {
                                echo '<li class="mb-2">';
                                echo '<i class="fas fa-check text-success me-2"></i>';
                                echo htmlspecialchars($feature);
                                echo '</li>';
                            }
                        }
                    }
                    echo '</ul>';
                    
                    echo '<button class="btn btn-primary btn-lg w-100 py-2 select-package-btn" ';
                    echo 'data-package-id="' . $package['id'] . '" ';
                    echo 'data-package-name="' . htmlspecialchars($package['name']) . '" ';
                    echo 'data-package-price="' . $package['price'] . '" ';
                    echo 'data-features=\'' . json_encode($features) . '\'>';
                    echo '<span class="btn-text">';
                    echo '<i class="fas fa-credit-card me-2"></i>Subscribe Selected Buses';
                    echo '</span>';
                    echo '<div class="spinner-border spinner-border-sm ms-2 d-none" role="status">';
                    echo '<span class="visually-hidden">Loading...</span>';
                    echo '</div>';
                    echo '</button>';
                    
                    echo '<div class="mt-2 text-center">';
                    echo '<small class="text-muted">';
                    echo '<i class="fas fa-info-circle me-1"></i>';
                    echo 'Select buses from the list above';
                    echo '</small>';
                    echo '</div>';
                    
                    echo '</div>'; // card-body
                    echo '</div>'; // card
                    echo '</div>'; // col-md-4
                }
            }
            ?>
        </div>';
    } 
    ?>
</div>

<!-- Bus Selection Modal -->
<div class="modal fade" id="busSelectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bus Selection Required</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please select at least one bus to continue with the subscription.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Confirmation Modal -->
<div class="modal fade" id="paymentConfirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>You are about to subscribe the following buses to <span id="packageName"></span>:</p>
                <ul id="selectedBusesList" class="mb-3"></ul>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Total amount to be charged: <strong>Rs. <span id="totalAmount">0.00</span></strong>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="termsCheck">
                    <label class="form-check-label" for="termsCheck">
                        I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and 
                        <a href="privacy.php" target="_blank">Privacy Policy</a>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="proceedToPayment" disabled>
                    <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .package-card {
        transition: all 0.3s ease;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    
    .package-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.5rem rgba(78, 115, 223, 0.2);
    }
    
    .package-card .card-header {
        border: none;
    }
    
    .package-card .card-body {
        padding: 2rem;
    }
    
    .package-card .btn {
        border-radius: 50px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .package-card .display-4 {
        font-size: 2.5rem;
        font-weight: 700;
    }
    
    .package-card ul li {
        padding: 0.5rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .package-card ul li:last-child {
        border-bottom: none;
    }
    
    .package-card .text-muted {
        color: #858796 !important;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .package-card {
            margin-bottom: 1.5rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle "Select All" checkbox
    const selectAllCheckbox = document.getElementById('selectAllBuses');
    const busCheckboxes = document.querySelectorAll('.bus-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            busCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
        
        // Update "Select All" checkbox when individual checkboxes change
        busCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(busCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            });
        });
    }
    
    // Initialize payment confirmation modal
    const paymentModalEl = document.getElementById('paymentConfirmationModal');
    let paymentModal = null;
    
    if (paymentModalEl) {
        // Create a new modal instance
        paymentModal = new bootstrap.Modal(paymentModalEl, {
            backdrop: 'static',
            keyboard: false,
            focus: true
        });
        
        // Handle modal show event
        paymentModalEl.addEventListener('show.bs.modal', function() {
            // Ensure the modal is on top
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.style.zIndex = '1060';
            }
            paymentModalEl.style.zIndex = '1061';
            
            // Reset form state
            const form = paymentModalEl.querySelector('form');
            if (form) {
                form.reset();
            }
            
            // Re-enable the proceed button
            const proceedBtn = paymentModalEl.querySelector('#proceedToPayment');
            if (proceedBtn) {
                proceedBtn.disabled = true;
            }
        });
        
        // Handle modal hide event
        paymentModalEl.addEventListener('hidden.bs.modal', function() {
            // Clean up any existing backdrops
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                if (backdrop.parentNode) {
                    backdrop.parentNode.removeChild(backdrop);
                }
            });
            
            // Reset modal state
            paymentModalEl.style.display = 'none';
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
            document.body.style.overflow = '';
        });
        
        // Handle terms checkbox change
        const termsCheck = paymentModalEl.querySelector('#termsCheck');
        const proceedBtn = paymentModalEl.querySelector('#proceedToPayment');
        
        if (termsCheck && proceedBtn) {
            termsCheck.addEventListener('change', function() {
                proceedBtn.disabled = !this.checked;
            });
        }
    }
    
    // Handle package selection
    const selectPackageBtns = document.querySelectorAll('.select-package-btn');
    
    selectPackageBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const selectedBuses = Array.from(document.querySelectorAll('.bus-checkbox:checked'));
            
            if (selectedBuses.length === 0) {
                // Show modal if no buses are selected
                const modal = new bootstrap.Modal(document.getElementById('busSelectionModal'));
                modal.show();
                return;
            }
            
            // Get package details
            const packageId = this.dataset.packageId;
            const packageName = this.dataset.packageName;
            const packagePrice = parseFloat(this.dataset.packagePrice);
            const busIds = selectedBuses.map(cb => cb.value);
            
            // Update modal content
            document.getElementById('packageName').textContent = packageName;
            
            const busesList = document.getElementById('selectedBusesList');
            busesList.innerHTML = '';
            
            selectedBuses.forEach(checkbox => {
                const busId = checkbox.value;
                const busLabel = document.querySelector(`label[for="${checkbox.id}"]`).textContent.trim();
                const listItem = document.createElement('li');
                listItem.textContent = busLabel;
                busesList.appendChild(listItem);
            });
            
            // Calculate and display total amount
            const totalAmount = packagePrice * selectedBuses.length;
            document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
            
            // Reset and show the confirmation modal
            const confirmModal = new bootstrap.Modal(document.getElementById('paymentConfirmationModal'));
            document.getElementById('termsCheck').checked = false;
            document.getElementById('proceedToPayment').disabled = true;
            confirmModal.show();
            
            // Store package and bus info for the payment process
            document.getElementById('proceedToPayment').onclick = function() {
                processPayment(packageId, busIds, this);
            };
        });
    });
    
    // Handle terms checkbox
    document.getElementById('termsCheck')?.addEventListener('change', function() {
        document.getElementById('proceedToPayment').disabled = !this.checked;
    });
    
    // Process payment function
    async function processPayment(packageId, busIds, button) {
        const buttonText = button.querySelector('.btn-text');
        const spinner = button.querySelector('.spinner-border');
        const originalText = buttonText.innerHTML;
        
        try {
            // Show loading state
            buttonText.innerHTML = 'Processing...';
            spinner.classList.remove('d-none');
            button.disabled = true;
            
            // Call your payment processing endpoint
            const response = await fetch('create_payment_hash.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    package_id: packageId,
                    bus_ids: busIds
                })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                // Redirect to payment gateway or show success message
                if (data.payment_url) {
                    window.location.href = data.payment_url;
                } else {
                    window.location.href = `payment_success.php?order_id=${data.order_id}`;
                }
            } else {
                // Show error message
                alert(data.message || 'Failed to process payment. Please try again.');
                
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('paymentConfirmationModal'));
                modal.hide();
            }
        } catch (error) {
            console.error('Payment processing error:', error);
            alert('An error occurred while processing your payment. Please try again.');
        } finally {
            // Reset button state
            if (buttonText) buttonText.innerHTML = originalText;
            if (spinner) spinner.classList.add('d-none');
            if (button) button.disabled = false;
            
            // Re-enable the payment button
            const paymentBtn = document.getElementById('proceedToPayment');
            if (paymentBtn) paymentBtn.disabled = false;
        }
    }
    
    // Function to show alert messages
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Insert alert at the top of the container
        const container = document.querySelector('.container-fluid');
        if (container) {
            container.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto-remove alert after 5 seconds
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    }
    
    // Handle upgrade buttons if they exist
    document.querySelectorAll('.upgrade-btn').forEach(upgradeBtn => {
        upgradeBtn.addEventListener('click', function() {
            const busId = this.dataset.busId;
            const busName = this.dataset.busName || '';
            const regNumber = this.dataset.regNumber || '';
            
            // Update bus selection checkboxes
            const checkboxes = document.querySelectorAll('.bus-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = (checkbox.value === busId);
            });
            
            // Update "Select All" checkbox
            const selectAllCheckbox = document.getElementById('selectAllBuses');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            
            // Show success message
            showAlert('info', `Selected bus: ${regNumber} ${busName ? '(' + busName + ')' : ''}`);
        });
    });
    
    // Handle manage subscription buttons if they exist
    document.querySelectorAll('.manage-subscription').forEach(btn => {
        btn.addEventListener('click', function() {
            const busId = this.dataset.busId;
            const subscriptionId = this.dataset.subscriptionId;
            
            // Show a message or redirect to management page
            showAlert('info', 'Subscription management feature coming soon!');
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
