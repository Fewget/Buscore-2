<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a bus owner
check_bus_owner_access();

$page_title = 'Premium Subscriptions';
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Get bus owner's buses
$buses = $pdo->prepare("SELECT * FROM buses WHERE user_id = ? ORDER BY registration_number ASC");
$buses->execute([$_SESSION['user_id']]);
$buses = $buses->fetchAll(PDO::FETCH_ASSOC);

// Get active subscriptions for these buses
$busIds = array_column($buses, 'id');
$subscriptions = [];

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
        $subscriptions[$row['bus_id']] = $row;
    }
}

// Get available packages
$packages = $pdo->query("SELECT * FROM premium_packages WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <!-- Status Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?php echo $page_title; ?></h1>
            <p class="text-muted mb-0">Upgrade your bus listings with premium features</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <?php if (empty($buses)): ?>
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-bus fa-4x text-muted mb-3"></i>
                <h4>No Buses Found</h4>
                <p class="text-muted">You don't have any buses registered yet.</p>
            </div>
            <a href="add-bus.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Add Your First Bus
            </a>
        </div>
    <?php else: ?>
        <!-- Bus Selection for Premium Features -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Select Buses for Premium Features</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllBuses">
                            <label class="form-check-label fw-bold" for="selectAllBuses">
                                Select All Buses
                            </label>
                        </div>
                    </div>
                    <?php foreach ($buses as $bus): ?>
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input class="form-check-input bus-checkbox" type="checkbox" 
                                   id="bus_<?php echo $bus['id']; ?>" 
                                   value="<?php echo $bus['id']; ?>">
                            <label class="form-check-label" for="bus_<?php echo $bus['id']; ?>">
                                <?php echo !empty($bus['registration_number']) ? htmlspecialchars($bus['registration_number']) : 'Bus #' . $bus['id']; ?>
                                <?php if (!empty($bus['bus_name'])): ?>
                                    (<?php echo htmlspecialchars($bus['bus_name']); ?>)
                                <?php endif; ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Available Packages -->
        <div class="row">
            <?php foreach ($packages as $package): 
                $features = json_decode($package['features'], true);
                $isPopular = $package['id'] == 2; // Mark the middle package as popular
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 package-card <?php echo $isPopular ? 'border-primary border-2' : 'border-0'; ?> ">
                        <?php if ($isPopular): ?>
                            <div class="card-header bg-primary text-white text-center py-2">
                                <small class="fw-bold">MOST POPULAR</small>
                            </div>
                        <?php else: ?>
                            <div class="card-header bg-white"></div>
                        <?php endif; ?>
                        
                        <div class="card-body text-center p-4">
                            <h3 class="card-title"><?php echo htmlspecialchars($package['name']); ?></h3>
                            <div class="my-4">
                                <span class="display-4 fw-bold">Rs. <?php echo number_format($package['price'], 2); ?></span>
                                <span class="text-muted">/month</span>
                            </div>
                            
                            <ul class="list-unstyled mb-4">
                                <?php if (is_array($features)): ?>
                                    <?php foreach ($features as $feature): ?>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <?php echo htmlspecialchars($feature); ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                            
                            <button class="btn btn-primary btn-lg w-100 py-2 select-package-btn" 
                                    data-package-id="<?php echo $package['id']; ?>"
                                    data-package-name="<?php echo htmlspecialchars($package['name']); ?>"
                                    data-package-price="<?php echo $package['price']; ?>"
                                    data-features='<?php echo json_encode($features); ?>'>
                                <span class="btn-text">
                                    <i class="fas fa-credit-card me-2"></i>Subscribe Selected Buses
                                </span>
                                <div class="spinner-border spinner-border-sm ms-2 d-none" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </button>
                            
                            <div class="mt-2 text-center">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Select buses from the list above
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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
function initializePremiumSubscription() {
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
    
    // Handle package selection
    const selectPackageBtns = document.querySelectorAll('.select-package-btn');
    
    selectPackageBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const selectedBuses = Array.from(document.querySelectorAll('.bus-checkbox:checked'));
            
            if (selectedBuses.length === 0) {
                // Show modal if no buses are selected
                const modal = new bootstrap.Modal(document.getElementById('busSelectionModal'));
                modal.show();
                return;
            }
            
            // Get package details
            const packageId = this.getAttribute('data-package-id');
            const busIds = selectedBuses.map(cb => cb.value);
            
            // Show loading state
            const buttonText = this.querySelector('.btn-text');
            const spinner = this.querySelector('.spinner-border');
            const originalText = buttonText.innerHTML;
            
            buttonText.innerHTML = 'Processing...';
            if (spinner) spinner.classList.remove('d-none');
            this.disabled = true;
            
            try {
                // Redirect to checkout with all selected bus IDs
                const busIdsParam = busIds.join(',');
                window.location.href = `checkout.php?package_id=${packageId}&bus_ids=${encodeURIComponent(busIdsParam)}`;
            } catch (error) {
                console.error('Error during checkout:', error);
                alert('Failed to process your request. Please try again.');
                
                // Reset button state
                buttonText.innerHTML = originalText;
                if (spinner) spinner.classList.add('d-none');
                this.disabled = false;
            }
        });
    });
}

// Initialize when DOM is fully loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePremiumSubscription);
} else {
    initializePremiumSubscription();
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
