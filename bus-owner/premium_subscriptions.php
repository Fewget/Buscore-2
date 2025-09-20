<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is a bus owner
if (!isset($_SESSION['user_id'])) {
    // Store the current URL for redirection after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

// Check if user is a bus owner
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'bus_owner') {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$page_title = 'Premium Subscriptions';
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Get bus owner's buses
try {
    $buses = $pdo->prepare("SELECT * FROM buses WHERE user_id = ? ORDER BY registration_number ASC");
    $buses->execute([$_SESSION['user_id']]);
    $buses = $buses->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Database error in premium_subscriptions.php: ' . $e->getMessage());
    $error = 'Error loading bus information. Please try again later.';
    $buses = [];
}

// Get active subscriptions for these buses
$busIds = array_column($buses, 'id');
$subscriptions = [];

if (!empty($busIds)) {
    try {
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
    } catch (PDOException $e) {
        error_log('Error fetching subscriptions: ' . $e->getMessage());
    }
}

// Get available packages
try {
    $packages = $pdo->query("SELECT * FROM premium_packages WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching packages: ' . $e->getMessage());
    $packages = [];
}

// Include header after all processing is done
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <h2 class="mb-4">Premium Subscriptions</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (empty($buses)): ?>
        <div class="alert alert-info">
            You don't have any buses registered yet. Please add a bus first to subscribe to premium features.
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Select Buses</h5>
            </div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="selectAllBuses">
                    <label class="form-check-label" for="selectAllBuses">
                        Select All Buses
                    </label>
                </div>
                
                <div class="row">
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

        <div class="row">
            <?php foreach ($packages as $package): 
                $features = json_decode($package['features'], true);
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h4 class="my-0 font-weight-normal"><?php echo htmlspecialchars($package['name']); ?></h4>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h1 class="card-title pricing-card-title">
                                Rs. <?php echo number_format($package['price'], 2); ?>
                                <small class="text-muted">/ month</small>
                            </h1>
                            <ul class="list-unstyled mt-3 mb-4">
                                <?php if (is_array($features)): ?>
                                    <?php foreach ($features as $feature): ?>
                                        <li><?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                            <button type="button" class="btn btn-lg btn-block btn-outline-primary mt-auto select-package-btn"
                                    data-package-id="<?php echo $package['id']; ?>">
                                Subscribe Selected Buses
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Bus Selection Modal -->
<div class="modal fade" id="busSelectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">No Buses Selected</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Please select at least one bus to subscribe to a premium package.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle select all buses checkbox
    const selectAllCheckbox = document.getElementById('selectAllBuses');
    const busCheckboxes = document.querySelectorAll('.bus-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            busCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Update select all when individual checkboxes change
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
            const busIds = selectedBuses.map(cb => cb.value);
            
            // Show loading state
            const buttonText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            
            // Redirect to checkout with selected buses and package
            window.location.href = `checkout.php?package_id=${packageId}&bus_ids=${busIds.join(',')}`;
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
