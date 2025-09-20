<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Debug: Log request and session
error_log("=== ADD SERVICE RECORD ACCESSED ===");
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in. Redirecting to login.");
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: " . SITE_URL . "/login.php");
    exit();
}

// Debug: Log user role
error_log("User ID: " . $_SESSION['user_id']);
error_log("User role: " . ($_SESSION['role'] ?? 'not set'));

// Check if user has the bus_owner role
if (($_SESSION['role'] ?? '') !== 'bus_owner') {
    error_log("Access denied. User does not have bus_owner role.");
    header("Location: " . SITE_URL . "/index.php?error=unauthorized");
    exit();
}

error_log("User authorized to access add-service-record.php");

$bus_id = isset($_GET['bus_id']) ? (int)$_GET['bus_id'] : 0;
$success = false;
$error = '';

// Verify that the bus belongs to the logged-in owner
if ($bus_id > 0) {
    error_log("Verifying bus ownership. Bus ID: $bus_id, User ID: " . $_SESSION['user_id']);
    
    $stmt = $pdo->prepare("SELECT id, registration_number, user_id FROM buses WHERE id = ?");
    $stmt->execute([$bus_id]);
    $bus = $stmt->fetch();
    
    error_log("Bus query result: " . print_r($bus, true));
    
    if (!$bus) {
        error_log("Bus not found with ID: $bus_id");
        $_SESSION['error'] = 'Bus not found.';
        header("Location: dashboard.php");
        exit();
    }
    
    if ($bus['user_id'] != $_SESSION['user_id']) {
        error_log("Unauthorized access attempt. Bus user_id: " . $bus['user_id'] . ", Session user_id: " . $_SESSION['user_id']);
        $_SESSION['error'] = 'You are not authorized to add service records for this bus.';
        header("Location: dashboard.php");
        exit();
    }
    
    error_log("Bus ownership verified successfully");
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log the raw POST data for debugging
    error_log('POST data: ' . print_r($_POST, true));
    
    $service_type = $_POST['service_type'] ?? '';
    $service_date = $_POST['service_date'] ?? '';
    $mileage = (int)($_POST['mileage'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $update_bus = isset($_POST['update_bus']);
    
    // Log the processed values
    error_log("Processed values - Type: $service_type, Date: $service_date, Mileage: $mileage, Update Bus: " . ($update_bus ? 'Yes' : 'No'));
    
    // Validate input
    if (empty($service_type) || empty($service_date) || $mileage <= 0) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert service record
            $stmt = $pdo->prepare("
                INSERT INTO service_records 
                (bus_id, service_type, service_date, mileage, description)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $bus_id,
                $service_type,
                $service_date,
                $mileage,
                $description
            ]);
            
            // Update bus's last service info if requested
            if ($update_bus) {
                $update_fields = [];
                $params = [];
                
                if ($service_type === 'engine_oil') {
                    $update_fields[] = 'last_engine_oil_change = ?';
                    $update_fields[] = 'last_engine_oil_mileage = ?';
                    $params[] = $service_date;
                    $params[] = $mileage;
                } elseif ($service_type === 'brake_pads') {
                    $update_fields[] = 'last_brake_change = ?';
                    $update_fields[] = 'last_brake_mileage = ?';
                    $params[] = $service_date;
                    $params[] = $mileage;
                }
                
                if (!empty($update_fields)) {
                    $params[] = $bus_id;
                    $sql = "UPDATE buses SET " . implode(', ', $update_fields) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                }
            }
            
            $pdo->commit();
            $success = true;
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            // Log detailed error information
            error_log("Service Record Save Error: " . $e->getMessage());
            error_log("SQL Query: INSERT INTO service_records (bus_id, service_type, service_date, mileage, description) VALUES (?, ?, ?, ?, ?)");
            error_log("Parameters: " . print_r([$bus_id, $service_type, $service_date, $mileage, $description], true));
            
            // For debugging - show more detailed error in development
            if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
                $error = 'Error: ' . $e->getMessage() . ' (This detailed message is shown in development mode only)';
            } else {
                $error = 'An error occurred while saving the service record. Please try again or contact support.';
            }
        }
    }
}

// Get bus details for the form
$bus = null;
if ($bus_id > 0) {
    $stmt = $pdo->prepare("SELECT id, registration_number FROM buses WHERE id = ?");
    $stmt->execute([$bus_id]);
    $bus = $stmt->fetch();
}

$page_title = 'Add Service Record';
?>

<?php 
$headerClass = 'fixed-top';
include '../includes/header.php'; 
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Add Service Record</h1>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-bus me-2"></i>
                Bus: <?php echo htmlspecialchars(format_registration_number($bus['registration_number'])); ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    Service record added successfully!
                    <div class="mt-2">
                        <a href="dashboard.php" class="btn btn-sm btn-outline-primary me-2">
                            <i class="fas fa-tachometer-alt me-1"></i> Back to Dashboard
                        </a>
                        <a href="add-service-record.php?bus_id=<?php echo $bus_id; ?>" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-plus-circle me-1"></i> Add Another Record
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="post" id="serviceRecordForm">
                    <input type="hidden" name="bus_id" value="<?php echo $bus_id; ?>">
                    
                    <!-- Service Type -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Service Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="service_type" class="form-label">Service Type <span class="text-danger">*</span></label>
                                            <select class="form-select" id="service_type" name="service_type" required>
                                                <option value="">Select Service Type</option>
                                                <option value="engine_oil">Engine Oil Change</option>
                                                <option value="brake_pads">Brake Pads Replacement</option>
                                                <option value="tire_rotation">Tire Rotation</option>
                                                <option value="other">Other Service</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="service_date" class="form-label">Service Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="service_date" name="service_date" 
                                                value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="mileage" class="form-label">Mileage (km) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="mileage" name="mileage" 
                                                min="0" step="1" required>
                                        </div>
                                        
                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3" 
                                                placeholder="Enter service details, notes, or observations"></textarea>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="update_bus" name="update_bus" checked>
                                                <label class="form-check-label" for="update_bus">
                                                    Update bus service records with this information
                                                </label>
                                                <div class="form-text text-muted">
                                                    If checked, this will update the bus's last service information for the selected service type.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Service Record
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
