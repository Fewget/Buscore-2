<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is a bus owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bus_owner') {
    header('Location: ../login.php');
    exit();
}

$pageTitle = "Bus Owner Dashboard | " . SITE_NAME;
// Handle success and error messages
$messages = [];
if (isset($_GET['success'])) {
    $messages[] = ['type' => 'success', 'text' => $_GET['success']];
}
if (isset($_GET['error'])) {
    $messages[] = ['type' => 'danger', 'text' => $_GET['error']];
}

// Initialize buses array
$buses = [];

// Get bus owner's buses
try {
    // Debug: Log the user ID being used for the query
    error_log("Fetching buses for user ID: " . $_SESSION['user_id']);
    
    // Get all buses with premium status
    $stmt = $pdo->prepare("
        SELECT b.*, 
               (SELECT AVG(driver_rating) FROM ratings WHERE bus_id = b.id) as average_rating,
               (SELECT COUNT(*) FROM ratings WHERE bus_id = b.id) as rating_count
        FROM buses b 
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the fetched buses
    error_log("Fetched buses: " . print_r($buses, true));
    
} catch (PDOException $e) {
    error_log("Error fetching buses: " . $e->getMessage());
    $error = "An error occurred while fetching your buses. Please try again later.";
    $buses = []; // Ensure $buses is always an array
}

include __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">My Buses</h1>
        <div>
            <a href="reports.php" class="btn btn-info text-white me-2">
                <i class="fas fa-flag me-1"></i> View Reports
            </a>
            <a href="premium_subscriptions.php" class="btn btn-warning me-2">
                <i class="fas fa-crown me-1"></i> Premium Subscriptions
            </a>
            <a href="add-bus.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Add New Bus
            </a>
        </div>
    </div>

    <?php foreach ($messages as $message): ?>
        <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message['text']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>

    <?php 
    // Debug output - only show in development environment
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): 
        error_log("Debug Info - User ID: " . $_SESSION['user_id'] . ", Buses found: " . (is_array($buses) ? count($buses) : '0 (not an array)'));
    endif; ?>
    
    <?php if (empty($buses)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-bus fa-4x text-muted mb-3"></i>
                    <h3>No Buses Found</h3>
                    <p class="text-muted">You haven't added any buses yet. Get started by adding your first bus.</p>
                </div>
                <a href="add-bus.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Your First Bus
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($buses as $bus): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="position-relative">
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($bus['route_number']); ?>
                                </span>
                            </div>
                            <div class="bus-image" style="height: 180px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-bus fa-4x text-muted"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <?php 
                                        // Format the registration number
                                        $regNumber = !empty($bus['registration_number']) ? format_registration_number($bus['registration_number']) : 'Bus #' . $bus['id'];
                                        echo htmlspecialchars($regNumber);
                                        
                                        if (!empty($bus['premium_expiry']) && strtotime($bus['premium_expiry']) > time()) { 
                                            try {
                                                $expiryDate = new DateTime($bus['premium_expiry']);
                                                $now = new DateTime();
                                                $expiresIn = $now->diff($expiryDate);
                                        ?>
                                                <span class="badge bg-warning text-dark ms-2" 
                                                      data-bs-toggle="tooltip" 
                                                      title="Expires in <?php echo $expiresIn->format('%a days, %h hours'); ?>">
                                                    <i class="fas fa-crown me-1"></i> Premium
                                                </span>
                                        <?php
                                            } catch (Exception $e) {
                                                // Silently handle date parsing errors
                                            }
                                        } else {
                                            $isActive = $bus['isActive'] ?? false;
                                            $premiumStatus = $bus['premiumStatus'] ?? 'inactive';
                                            if (!$isActive && $premiumStatus !== 'pending'): 
                                        ?>
                                                <span class="badge bg-secondary ms-2">Standard</span>
                                        <?php 
                                            endif;
                                        }
                                        ?>
                                    </h5>
                                    <?php if (!empty($bus['bus_name'])): ?>
                                        <p class="text-muted mb-0 small">
                                            <i class="fas fa-bus me-1"></i> <?php echo htmlspecialchars($bus['bus_name']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                        <li><a class="dropdown-item" href="add-bus.php?edit=<?php echo $bus['id']; ?>"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                        <?php 
                                        $isActive = $bus['isActive'] ?? false;
                                        $premiumStatus = $bus['premiumStatus'] ?? 'inactive';
                                        if (!$isActive && $premiumStatus !== 'pending'): 
                                        ?>
                                            <li>
                                                <button class="dropdown-item text-warning request-premium-btn" 
                                                        data-bus-id="<?php echo $bus['id']; ?>"
                                                        data-bus-name="<?php echo htmlspecialchars($bus['bus_name']); ?>">
                                                    <i class="fas fa-crown me-2"></i>Request Premium
                                                </button>
                                            </li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteBusModal<?php echo $bus['id']; ?>"><i class="fas fa-trash-alt me-2"></i>Delete</a></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <?php if (!empty($bus['route_description'])): ?>
                            <p class="text-muted mb-2">
                                <i class="fas fa-route me-2"></i>
                                <?php echo htmlspecialchars($bus['route_description']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($bus['company_name'])): ?>
                            <p class="text-muted mb-2">
                                <i class="fas fa-building me-2"></i>
                                <?php echo htmlspecialchars($bus['company_name']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <?php if (!empty($bus['company_name'])): ?>
                                    <span class="badge bg-primary me-1">
                                        <?php echo htmlspecialchars($bus['company_name']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">
                                    Added <?php echo time_elapsed_string($bus['created_at']); ?>
                                </small>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="add-service-record.php?bus_id=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-success me-2">
                                        <i class="fas fa-plus me-1"></i> Add Service
                                    </a>
                                    <a href="service_records.php?bus_id=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-info me-2">
                                        <i class="fas fa-tools me-1"></i> Service Records
                                    </a>
                                    <a href="update_maintenance.php?bus_id=<?php echo $bus['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Update Maintenance">
                                        <i class="fas fa-wrench"></i>
                                    </a>
                                </div>
                                <button type="button" 
                                        class="btn <?php echo $bus['is_premium'] ? 'btn-warning' : 'btn-outline-warning'; ?> toggle-premium" 
                                        data-bus-id="<?php echo $bus['id']; ?>"
                                        title="<?php echo $bus['is_premium'] ? 'Disable Premium' : 'Make Premium'; ?>">
                                    <i class="fas fa-crown"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteBusModal<?php echo $bus['id']; ?>" title="Delete Bus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delete Confirmation Modal -->
                    <div class="modal fade" id="deleteBusModal<?php echo $bus['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Deletion</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete bus <strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong>? This action cannot be undone.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <form action="delete-bus.php" method="POST" class="d-inline">
                                        <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Delete Bus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="row mt-5">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Buses</h5>
                    <h2 class="mb-0"><?php echo is_array($buses) ? count($buses) : 0; ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Active Buses</h6>
                            <h2 class="mb-0">
                                <?php 
                                    echo count($buses); // All buses are considered active
                                ?>
                            </h2>
                        </div>
                        <div class="icon-shape bg-white bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Total Ratings</h6>
                            <h2 class="mb-0">
                                <?php 
                                    echo array_sum(array_column($buses, 'rating_count')); 
                                ?>
                            </h2>
                        </div>
                        <div class="icon-shape bg-white bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-star fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Request Premium Modal -->
<div class="modal fade" id="requestPremiumModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="requestPremiumForm" method="POST" action="request-premium.php">
                <div class="modal-header">
                    <h5 class="modal-title">Request Premium Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="bus_id" id="requestBusId">
                    <p>You are requesting premium status for: <strong id="requestBusName"></strong></p>
                    <p>Premium status provides your bus with featured placement and additional visibility on our platform.</p>
                    
                    <div class="mb-3">
                        <label for="requestNotes" class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control" id="requestNotes" name="notes" rows="3" 
                                  placeholder="Let us know why you'd like premium status for this bus"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Your request will be reviewed by our team. You'll be notified once a decision is made.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle premium toggle
    document.querySelectorAll('.toggle-premium').forEach(button => {
        button.addEventListener('click', function() {
            const busId = this.getAttribute('data-bus-id');
            const isPremium = this.classList.contains('btn-warning');
            const button = this;
            const badge = document.getElementById('premium-badge-' + busId);
            
            // Show loading state
            const originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            
            // Send AJAX request
            fetch('toggle-premium.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'bus_id=' + encodeURIComponent(busId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle button and badge
                    if (data.is_premium) {
                        button.classList.remove('btn-outline-warning');
                        button.classList.add('btn-warning');
                        button.setAttribute('title', 'Disable Premium');
                        badge.textContent = 'Premium';
                        badge.classList.remove('bg-secondary');
                        badge.classList.add('bg-warning', 'text-dark');
                    } else {
                        button.classList.remove('btn-warning');
                        button.classList.add('btn-outline-warning');
                        button.setAttribute('title', 'Make Premium');
                        badge.textContent = 'Standard';
                        badge.classList.remove('bg-warning', 'text-dark');
                        badge.classList.add('bg-secondary');
                    }
                } else {
                    alert('Error: ' + (data.message || 'Failed to update premium status'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalHtml;
            });
        });
    });
});
</script>
