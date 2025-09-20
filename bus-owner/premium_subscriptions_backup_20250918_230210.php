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

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?php echo $page_title; ?></h1>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Your Buses</h6>
        </div>
        <div class="card-body">
            <?php if (empty($buses)): ?>
                <div class="text-center py-4">
                    <p>You don't have any buses registered yet.</p>
                    <a href="add-bus.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Your First Bus
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Bus</th>
                                <th>Current Plan</th>
                                <th>Features</th>
                                <th>Expires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($buses as $bus): 
                                $subscription = $subscriptions[$bus['id']] ?? null;
                                $hasActiveSubscription = $subscription !== null;
                                $expiryText = $subscription && $subscription['end_date'] 
                                    ? date('M j, Y', strtotime($subscription['end_date']))
                                    : ($subscription ? 'Lifetime' : 'No active subscription');
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold">
                                            <?php echo !empty($bus['registration_number']) 
                                                ? htmlspecialchars(format_registration_number($bus['registration_number'])) 
                                                : 'Bus #' . $bus['id']; ?>
                                            <?php if (!empty($bus['bus_name'])): ?>
                                                <div class="text-muted small"><?php echo htmlspecialchars($bus['bus_name']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($hasActiveSubscription): ?>
                                            <span class="badge bg-success">
                                                <?php echo htmlspecialchars($subscription['package_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Free</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($hasActiveSubscription): 
                                            $features = json_decode($subscription['features'], true);
                                            if (is_array($features)): ?>
                                                <?php foreach ($features as $feature): ?>
                                                    <span class="badge bg-info text-dark me-1 mb-1">
                                                        <?php 
                                                        $featureNames = [
                                                            'display_company_name' => 'Company Name',
                                                            'display_bus_name' => 'Bus Name'
                                                        ];
                                                        echo $featureNames[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
                                                        ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No premium features</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $expiryText; ?></td>
                                    <td>
                                        <?php if ($hasActiveSubscription): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary manage-subscription" 
                                                    data-bus-id="<?php echo $bus['id']; ?>"
                                                    data-subscription-id="<?php echo $subscription['id']; ?>">
                                                <i class="fas fa-cog"></i> Manage
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-primary upgrade-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#subscribeModal"
                                                    data-bus-id="<?php echo $bus['id']; ?>"
                                                    data-bus-name="<?php echo htmlspecialchars($bus['bus_name'] ?? ''); ?>"
                                                    data-reg-number="<?php echo htmlspecialchars($bus['registration_number'] ?? ''); ?>">
                                                <i class="fas fa-crown"></i> Upgrade
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($buses)): ?>
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Available Plans</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($packages as $package): 
                        $features = json_decode($package['features'], true);
                        $isPopular = strpos(strtolower($package['name']), 'combo') !== false;
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-0 shadow-sm<?php echo $isPopular ? ' border-primary border-2' : ''; ?>">
                                <?php if ($isPopular): ?>
                                    <div class="card-header bg-primary text-white text-center py-2">
                                        <small class="fw-bold">MOST POPULAR</small>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body text-center">
                                    <h3 class="card-title pricing-card-title">
                                        Rs. <?php echo number_format($package['price'], 2); ?>
                                        <small class="text-muted">/ <?php echo $package['duration_days'] ? 'month' : 'one-time'; ?></small>
                                    </h3>
                                    <h5 class="mb-4"><?php echo htmlspecialchars($package['name']); ?></h5>
                                    
                                    <ul class="list-unstyled mb-4">
                                        <?php foreach ($features as $feature): ?>
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <?php 
                                                $featureNames = [
                                                    'display_company_name' => 'Display Company Name',
                                                    'display_bus_name' => 'Display Bus Name'
                                                ];
                                                echo $featureNames[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
                                                ?>
                                            </li>
                                        <?php endforeach; ?>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <?php echo $package['duration_days'] 
                                                ? $package['duration_days'] . ' Days Access' 
                                                : 'Lifetime Access'; ?>
                                        </li>
                                    </ul>
                                    
                                    <button type="button" class="btn btn-lg btn-primary w-100 subscribe-package" 
                                            data-package-id="<?php echo $package['id']; ?>"
                                            data-package-name="<?php echo htmlspecialchars($package['name']); ?>"
                                            data-package-price="<?php echo $package['price']; ?>"
                                            data-duration="<?php echo $package['duration_days'] ? $package['duration_days'] . ' days' : 'Lifetime'; ?>">
                                        Choose Plan
                                    </button>
                                    <input type="hidden" class="package-features" value='<?php echo json_encode(json_decode($package['features'], true)); ?>'>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Subscribe Modal -->
<div class="modal fade" id="subscribeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="subscriptionForm" action="process_payment.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Subscribe to <span id="packageName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="package_id" id="packageId">
                    <input type="hidden" name="bus_id" id="busId">
                    
                    <!-- Bus Details Section (Always Visible) -->
                    <div class="mb-4">
                        <h5>Bus Details</h5>
                        <div class="card">
                            <div class="card-body">
                                <p class="mb-1"><strong>Registration:</strong> <span id="busRegNumber"></span></p>
                                <p class="mb-1"><strong>Bus Name:</strong> <span id="busName">-</span></p>
                                <p class="mb-0"><strong>Current Plan:</strong> <span id="currentPlan" class="badge bg-secondary">None</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Package Selection Section -->
                    <div id="packageSelection">
                        <h5>Select a Package</h5>
                        <div class="row g-3">
                            <?php foreach ($packages as $package): ?>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <h5 class="card-title"><?php echo htmlspecialchars($package['name']); ?></h5>
                                            <h3 class="text-primary">Rs. <?php echo number_format($package['price'], 2); ?></h3>
                                            <p class="text-muted">
                                                <?php echo $package['duration_days'] ? $package['duration_days'] . ' days' : 'Lifetime'; ?>
                                            </p>
                                            <button type="button" 
                                                    class="btn btn-outline-primary select-package"
                                                    data-package-id="<?php echo $package['id']; ?>"
                                                    data-package-name="<?php echo htmlspecialchars($package['name']); ?>"
                                                    data-package-price="<?php echo $package['price']; ?>"
                                                    data-duration="<?php echo $package['duration_days'] ? $package['duration_days'] . ' days' : 'Lifetime'; ?>">
                                                Select Package
                                            </button>
                                            <input type="hidden" class="package-features" value='<?php echo $package['features']; ?>'>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Package Details Section (Initially Hidden) -->
                    <div id="packageDetailsSection" class="d-none">
                        <button type="button" id="backToPackageSelection" class="btn btn-sm btn-outline-secondary mb-3">
                            <i class="fas fa-arrow-left me-1"></i> Back to Packages
                        </button>
                        
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5>Package Details</h5>
                                <p class="mb-1"><strong>Name:</strong> <span id="packageNameText"></span></p>
                                <p class="mb-1"><strong>Price:</strong> <span id="packagePrice" class="text-success fw-bold"></span></p>
                                <p class="mb-3"><strong>Duration:</strong> <span id="packageDuration"></span></p>
                                
                                <h6>Features:</h6>
                                <div id="packageFeatures" class="mb-3"></div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="termsCheck" required>
                                    <label class="form-check-label" for="termsCheck">
                                        I agree to the <a href="../terms.php" target="_blank">Terms of Service</a> and 
                                        <a href="../privacy.php" target="_blank">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="proceedToPayment" disabled>
                        <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manage Subscription Modal -->
<div class="modal fade" id="manageSubscriptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="subscriptionDetails">
                <!-- Filled by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="upgradeSubscription">
                    <i class="fas fa-arrow-up me-2"></i>Upgrade Plan
                </button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// Simple modal handler
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                const bsModal = bootstrap.Modal.getInstance(this);
                if (bsModal) bsModal.hide();
            }
        });
    });
    
    // Store package data for reference
    const packages = {};

    // Initialize package data
    document.querySelectorAll('.package-features').forEach(input => {
        const card = input.closest('.card');
        const button = card.querySelector('.select-package');
        if (button) {
            const packageId = button.dataset.packageId;
            packages[packageId] = {
                id: packageId,
                name: button.dataset.packageName,
                price: parseFloat(button.dataset.packagePrice),
                duration: button.dataset.duration || '1 month',
                features: JSON.parse(input.value)
            };
        }
    });

    // Handle upgrade button clicks
    document.addEventListener('click', function(e) {
        const upgradeBtn = e.target.closest('.upgrade-btn');
        if (!upgradeBtn) return;
        
        e.preventDefault();
        
        try {
            const busId = upgradeBtn.dataset.busId;
            const busRow = upgradeBtn.closest('tr');
            
            // Show the modal first to ensure it's in the DOM
            const modal = new bootstrap.Modal(document.getElementById('subscribeModal'));
            modal.show();
            
            // Update bus details
            document.getElementById('busId').value = busId;
            
            // Get bus info
            const busInfo = {
                regNumber: busRow.cells[0]?.querySelector('.fw-bold')?.textContent.trim() || 'N/A',
                busName: busRow.cells[0]?.querySelector('.text-muted')?.textContent.trim() || 'N/A',
                currentPlan: busRow.cells[1]?.textContent.trim() || 'Free'
            };
            
            // Update UI
            document.getElementById('busRegNumber').textContent = busInfo.regNumber;
            document.getElementById('busName').textContent = busInfo.busName;
            
            const currentPlanBadge = document.getElementById('currentPlan');
            if (currentPlanBadge) {
                currentPlanBadge.textContent = busInfo.currentPlan;
                currentPlanBadge.className = 'badge ' + (busInfo.currentPlan === 'Free' ? 'bg-secondary' : 'bg-success');
            }
            
            // Reset form
            document.getElementById('packageSelection').classList.remove('d-none');
            document.getElementById('packageDetailsSection').classList.add('d-none');
            document.getElementById('termsCheck').checked = false;
            document.getElementById('proceedToPayment').disabled = true;
            
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        }
        
    });

    // Handle package selection
    document.addEventListener('click', function(e) {
        const selectBtn = e.target.closest('.select-package');
        if (!selectBtn) return;
        
        e.preventDefault();
        
        try {
            const packageId = selectBtn.dataset.packageId;
            const pkg = packages[packageId];
            if (!pkg) return;
            
            // Update package details
            document.getElementById('packageId').value = pkg.id;
            
            // Update UI elements
            const updateElement = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value;
            };
            
            updateElement('packageName', pkg.name);
            updateElement('packageNameText', pkg.name);
            updateElement('packagePrice', 'Rs. ' + pkg.price.toFixed(2));
            updateElement('packageDuration', pkg.duration);
            
            // Update features
            const featuresContainer = document.getElementById('packageFeatures');
            if (featuresContainer) {
                featuresContainer.innerHTML = '';
                
                if (Array.isArray(pkg.features)) {
                    pkg.features.forEach(feature => {
                        const displayName = {
                            'display_company_name': 'Display Company Name',
                            'display_bus_name': 'Display Bus Name'
                        }[feature] || feature.replace(/_/g, ' ');
                        
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-info text-dark me-1 mb-1';
                        badge.textContent = displayName;
                        featuresContainer.appendChild(badge);
                    });
                }
            }
            
            // Toggle views
            document.getElementById('packageSelection').classList.add('d-none');
            document.getElementById('packageDetailsSection').classList.remove('d-none');
            
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to select package. Please try again.');
        }
    });
    
    // Back button in package details
    document.addEventListener('click', function(e) {
        if (e.target.closest('#backToPackageSelection')) {
            e.preventDefault();
            document.getElementById('packageSelection').classList.remove('d-none');
            document.getElementById('packageDetailsSection').classList.add('d-none');
        }
    });
    
    // Handle terms checkbox change
    document.addEventListener('change', function(e) {
        if (e.target.matches('#termsCheck')) {
            const paymentBtn = document.getElementById('proceedToPayment');
            if (paymentBtn) {
                paymentBtn.disabled = !e.target.checked;
            }
        }
    });
    
    // Handle form submission
    document.addEventListener('submit', function(e) {
        if (!e.target.matches('#subscriptionForm')) return;
        
        e.preventDefault();
        const form = e.target;
        
        // Validate terms
        if (!form.termsCheck.checked) {
            alert('Please agree to the Terms of Service and Privacy Policy');
            return;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...';
        }
        
        // Submit the form
        form.submit();
    });
    
    // Handle manage subscription button
    document.querySelectorAll('.manage-subscription').forEach(button => {
        button.addEventListener('click', function() {
            const busRow = this.closest('tr');
            
            // Update modal with subscription details
            const detailsDiv = document.getElementById('subscriptionDetails');
            if (detailsDiv) {
                detailsDiv.innerHTML = `
                    <div class="mb-3">
                        <h6>Current Plan</h6>
                        <p>${busRow.cells[1].textContent.trim()}</p>
                    </div>
                    <div class="mb-3">
                        <h6>Features</h6>
                        <div>${busRow.cells[2].innerHTML}</div>
                    </div>
                    <div class="mb-3">
                        <h6>Expires</h6>
                        <p>${busRow.cells[3].textContent.trim()}</p>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        To upgrade or modify your subscription, please contact support.
                    </div>
                `;
            }
        });
    });
    
    // Handle upgrade subscription button
    const upgradeBtn = document.getElementById('upgradeSubscription');
    if (upgradeBtn) {
        upgradeBtn.addEventListener('click', function() {
            // Close the manage modal
            const manageModal = bootstrap.Modal.getInstance(document.getElementById('manageSubscriptionModal'));
            if (manageModal) manageModal.hide();
            
            // Show the subscription modal
            const subscribeButton = document.querySelector('.upgrade-btn');
            if (subscribeButton) {
                subscribeButton.click();
            }
        });
    }
});
</script>
