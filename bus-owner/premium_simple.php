<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
check_bus_owner_access();

// Get some test data
$packages = $pdo->query("SELECT * FROM premium_packages WHERE is_active = 1 LIMIT 2")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Subscriptions (Simple)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .package-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Premium Subscriptions</h1>
        
        <!-- Test Bus Card -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Test Bus</h5>
                <p class="text-muted">NA-1234</p>
                <button class="btn btn-primary upgrade-btn" data-bus-id="1">
                    <i class="fas fa-arrow-up me-1"></i>Upgrade Bus
                </button>
            </div>
        </div>
        
        <!-- Packages -->
        <h3 class="mb-3">Available Packages</h3>
        <div class="row g-4">
            <?php foreach ($packages as $package): 
                $features = json_decode($package['features'], true);
            ?>
                <div class="col-md-6">
                    <div class="card h-100 package-card select-package" 
                         data-package-id="<?php echo $package['id']; ?>"
                         data-package-name="<?php echo htmlspecialchars($package['name']); ?>"
                         data-package-price="<?php echo $package['price']; ?>">
                        <div class="card-body">
                            <h4 class="card-title"><?php echo htmlspecialchars($package['name']); ?></h4>
                            <h3 class="text-primary">Rs. <?php echo number_format($package['price'], 2); ?></h3>
                            <div class="mt-3">
                                <?php if (is_array($features)): ?>
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
                            </div>
                            <button class="btn btn-outline-primary mt-3">
                                Select Package
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Subscribe Modal -->
    <div class="modal fade" id="subscribeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Subscribe to <span id="packageName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>You are about to subscribe to: <strong id="selectedPackage"></strong></p>
                    <p>Price: <span id="packagePrice" class="text-success fw-bold"></span></p>
                    
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="termsCheck">
                        <label class="form-check-label" for="termsCheck">
                            I agree to the terms and conditions
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="proceedToPayment" disabled>
                        <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                    </button>
                    <!-- Hidden submit button for form validation -->
                    <button type="submit" class="d-none" id="formSubmitBtn"></button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Simple modal instance
        let modal = null;
        
        // Prevent form submission
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
        }
        
        // Handle package selection
        document.querySelectorAll('.select-package').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on a button inside the card
                if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                    return;
                }
                
                const packageId = this.dataset.packageId;
                const packageName = this.dataset.packageName;
                const packagePrice = 'Rs. ' + parseFloat(this.dataset.packagePrice).toFixed(2);
                
                // Update modal content and store package data
                document.getElementById('packageName').textContent = packageName;
                document.getElementById('selectedPackage').textContent = packageName;
                document.getElementById('packagePrice').textContent = packagePrice;
                
                // Store current package data for payment
                currentPackage = {
                    id: packageId,
                    name: packageName,
                    price: parseFloat(this.dataset.packagePrice)
                };
                
                // Show modal
                if (!modal) {
                    const modalEl = document.getElementById('subscribeModal');
                    modal = new bootstrap.Modal(modalEl);
                }
                modal.show();
            });
        });
        
        // Handle terms checkbox
        const termsCheck = document.getElementById('termsCheck');
        const proceedBtn = document.getElementById('proceedToPayment');
        let currentPackage = null;
        
        if (termsCheck && proceedBtn) {
            termsCheck.addEventListener('change', function() {
                proceedBtn.disabled = !this.checked;
            });
            
            // Handle payment button click
            proceedBtn.addEventListener('click', function(e) {
                // Prevent form submission
                e.preventDefault();
                e.stopPropagation();
                
                if (!termsCheck.checked) {
                    alert('Please agree to the terms and conditions');
                    return false;
                }
                
                if (!currentPackage) {
                    console.error('No package selected');
                    return false;
                }
                
                // Show loading state
                const originalText = proceedBtn.innerHTML;
                proceedBtn.disabled = true;
                proceedBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...';
                
                // In a real implementation, you would make an AJAX call to your server
                // to create a payment request and get the PayHere payment URL
                console.log('Initiating payment for package:', currentPackage);
                
                // Simulate API call (replace with actual API call)
                setTimeout(() => {
                    // This is a simulation - in production, you would:
                    // 1. Call your server endpoint to create a payment
                    // 2. Get the PayHere payment URL
                    // 3. Redirect to PayHere
                    
                    // Example of what the response might look like:
                    const paymentData = {
                        sandbox: true, // Set to false for production
                        merchant_id: 'YOUR_MERCHANT_ID',
                        return_url: window.location.href,
                        cancel_url: window.location.href,
                        notify_url: window.location.origin + '/api/payment_notify.php',
                        order_id: 'ORD' + Date.now(),
                        items: 'Premium Package: ' + currentPackage.name,
                        amount: currentPackage.price,
                        currency: 'LKR',
                        first_name: 'Customer', // Get from user profile
                        last_name: 'Name',      // Get from user profile
                        email: 'customer@example.com', // Get from user profile
                        phone: '0771234567',    // Get from user profile
                        address: 'No.1, Galle Road',
                        city: 'Colombo',
                        country: 'Sri Lanka',
                        hash: 'HASH_STRING' // Generated on server side
                    };
                    
                    console.log('Payment data prepared:', paymentData);
                    
                    // In a real implementation, you would call PayHere's JS API here
                    // For example:
                    // PayHere.startPayment(paymentData);
                    
                    // Show success message in modal
                    const modalBody = document.querySelector('#subscribeModal .modal-body');
                    if (modalBody) {
                        modalBody.innerHTML = `
                            <div class="text-center py-4">
                                <div class="mb-3">
                                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                                </div>
                                <h4 class="text-success">Payment Successful!</h4>
                                <p>Your order #${paymentData.order_id} has been processed.</p>
                                <p>Your subscription will be activated shortly.</p>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    You will receive a confirmation email with the details.
                                </div>
                            </div>
                        `;
                        
                        // Update footer
                        const modalFooter = document.querySelector('#subscribeModal .modal-footer');
                        if (modalFooter) {
                            modalFooter.innerHTML = `
                                <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">
                                    <i class="fas fa-check-circle me-2"></i>Done
                                </button>
                            `;
                        }
                        
                        // Close modal after 5 seconds
                        setTimeout(() => {
                            if (modal) {
                                modal.hide();
                                // Reload the page to show updated subscription status
                                window.location.reload();
                            }
                        }, 5000);
                    } else {
                        // Fallback to alert if DOM update fails
                        alert('Payment successful! Order #' + paymentData.order_id);
                        if (modal) modal.hide();
                        window.location.reload();
                    }
                    
                }, 1500); // Simulate network delay
            });
        }
        
        // Handle upgrade button
        document.querySelector('.upgrade-btn')?.addEventListener('click', function() {
            // Just show the first package by default for testing
            const firstPackage = document.querySelector('.select-package');
            if (firstPackage) {
                firstPackage.click();
            }
        });
        
        // Log modal events for debugging
        const modalEl = document.getElementById('subscribeModal');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', () => console.log('Modal show event'));
            modalEl.addEventListener('shown.bs.modal', () => console.log('Modal shown event'));
            modalEl.addEventListener('hide.bs.modal', () => console.log('Modal hide event'));
            modalEl.addEventListener('hidden.bs.modal', () => console.log('Modal hidden event'));
        }
    });
    </script>
</body>
</html>
