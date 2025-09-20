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

$bus_id = isset($_GET['bus_id']) ? (int)$_GET['bus_id'] : 0;
$success = '';
$error = '';

// Get bus details
$bus = null;
try {
    // Verify the bus belongs to the logged-in user
    $stmt = $pdo->prepare("
        SELECT b.* 
        FROM buses b 
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$bus_id, $_SESSION['user_id']]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        header('Location: dashboard.php?error=invalid_bus');
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Error fetching bus: " . $e->getMessage());
    $error = "An error occurred while fetching bus details.";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get current mileage data
        $currentMileage = !empty($_POST['current_mileage']) ? (int)$_POST['current_mileage'] : null;
        $mileageRecordedDate = !empty($_POST['mileage_recorded_date']) ? $_POST['mileage_recorded_date'] : date('Y-m-d');
        
        $updateData = [
            'current_mileage' => $currentMileage,
            'mileage_recorded_date' => $mileageRecordedDate,
            'last_inspection_date' => !empty($_POST['last_inspection_date']) ? $_POST['last_inspection_date'] : null,
            'last_inspection_mileage' => !empty($_POST['last_inspection_mileage']) ? (int)$_POST['last_inspection_mileage'] : null,
            'last_oil_change_date' => !empty($_POST['last_oil_change_date']) ? $_POST['last_oil_change_date'] : null,
            'last_oil_change_mileage' => !empty($_POST['last_oil_change_mileage']) ? (int)$_POST['last_oil_change_mileage'] : null,
            'last_brake_liner_change_date' => !empty($_POST['last_brake_liner_change_date']) ? $_POST['last_brake_liner_change_date'] : null,
            'last_brake_liner_mileage' => !empty($_POST['last_brake_liner_mileage']) ? (int)$_POST['last_brake_liner_mileage'] : null,
            'last_tyre_change_date' => !empty($_POST['last_tyre_change_date']) ? $_POST['last_tyre_change_date'] : null,
            'last_tyre_change_mileage' => !empty($_POST['last_tyre_change_mileage']) ? (int)$_POST['last_tyre_change_mileage'] : null,
            'last_battery_change_date' => !empty($_POST['last_battery_change_date']) ? $_POST['last_battery_change_date'] : null,
            'last_battery_change_mileage' => !empty($_POST['last_battery_change_mileage']) ? (int)$_POST['last_battery_change_mileage'] : null,
            'insurance_expiry_date' => !empty($_POST['insurance_expiry_date']) ? $_POST['insurance_expiry_date'] : null,
            'id' => $bus_id
        ];
        
        $stmt = $pdo->prepare("
            UPDATE buses SET 
                current_mileage = :current_mileage,
                mileage_recorded_date = :mileage_recorded_date,
                last_inspection_date = :last_inspection_date,
                last_inspection_mileage = :last_inspection_mileage,
                last_oil_change_date = :last_oil_change_date,
                last_oil_change_mileage = :last_oil_change_mileage,
                last_brake_liner_change_date = :last_brake_liner_change_date,
                last_brake_liner_mileage = :last_brake_liner_mileage,
                last_tyre_change_date = :last_tyre_change_date,
                last_tyre_change_mileage = :last_tyre_change_mileage,
                last_battery_change_date = :last_battery_change_date,
                last_battery_change_mileage = :last_battery_change_mileage,
                insurance_expiry_date = :insurance_expiry_date,
                updated_at = NOW()
            WHERE id = :id AND user_id = " . $_SESSION['user_id']
        );
        
        $stmt->execute($updateData);
        
        // Debug: Log the data being saved
        error_log("Updating maintenance data for bus ID: " . $bus_id);
        error_log("Update data: " . print_r($updateData, true));
        
        // Verify the update was successful
        $rowCount = $stmt->rowCount();
        error_log("Rows affected: " . $rowCount);
        
        if ($rowCount > 0) {
            $success = "Maintenance records updated successfully!";
        } else {
            $error = "No records were updated. Please check if you made any changes.";
            error_log("No rows were updated for bus ID: " . $bus_id);
        }
        // Refresh bus data
        $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
        $stmt->execute([$bus_id]);
        $bus = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error updating maintenance: " . $e->getMessage());
        $error = "An error occurred while updating maintenance records: " . $e->getMessage();
    }
}

$pageTitle = "Update Maintenance | " . SITE_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Update Maintenance Records</h1>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Bus: <?php echo htmlspecialchars($bus['bus_name'] . ' - ' . $bus['registration_number']); ?></h5>
        </div>
        <div class="card-body">
            <form method="post" id="maintenanceForm">
                <!-- Current Mileage Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Current Mileage</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Current Mileage (km)</label>
                                            <input type="number" class="form-control" name="current_mileage" 
                                                   value="<?php echo !empty($bus['current_mileage']) ? $bus['current_mileage'] : ''; ?>" 
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Date Recorded</label>
                                            <input type="date" class="form-control" name="mileage_recorded_date" 
                                                   value="<?php echo date('Y-m-d'); ?>" 
                                                   readonly
                                                   style="background-color: #f8f9fa; cursor: not-allowed;">
                                            <div class="form-text text-muted">Date is automatically set to today</div>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($bus['mileage_recorded_date'])): ?>
                                    <div class="text-muted small">
                                        Last updated on: <?php echo date('d M Y', strtotime($bus['mileage_recorded_date'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <!-- Inspection -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">Full Inspection</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Last Inspection Date</label>
                                    <input type="date" class="form-control" name="last_inspection_date" 
                                           value="<?php echo !empty($bus['last_inspection_date']) ? $bus['last_inspection_date'] : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Inspection Mileage (km)</label>
                                    <input type="number" class="form-control" name="last_inspection_mileage" 
                                           value="<?php echo !empty($bus['last_inspection_mileage']) ? $bus['last_inspection_mileage'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Engine Oil -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-warning">
                                <h6 class="mb-0">Engine Oil Change</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Last Oil Change Date</label>
                                    <input type="date" class="form-control" name="last_oil_change_date" 
                                           value="<?php echo !empty($bus['last_oil_change_date']) ? $bus['last_oil_change_date'] : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Oil Change Mileage (km)</label>
                                    <input type="number" class="form-control" name="last_oil_change_mileage" 
                                           value="<?php echo !empty($bus['last_oil_change_mileage']) ? $bus['last_oil_change_mileage'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Brake Liner -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0">Brake Liner Change</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Last Brake Liner Change Date</label>
                                    <input type="date" class="form-control" name="last_brake_liner_change_date" 
                                           value="<?php echo !empty($bus['last_brake_liner_change_date']) ? $bus['last_brake_liner_change_date'] : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Brake Liner Change Mileage (km)</label>
                                    <input type="number" class="form-control" name="last_brake_liner_mileage" 
                                           value="<?php echo !empty($bus['last_brake_liner_mileage']) ? $bus['last_brake_liner_mileage'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tyre Change -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">Tyre Change</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Last Tyre Change Date</label>
                                    <input type="date" class="form-control" name="last_tyre_change_date" 
                                           value="<?php echo !empty($bus['last_tyre_change_date']) ? $bus['last_tyre_change_date'] : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tyre Change Mileage (km)</label>
                                    <input type="number" class="form-control" name="last_tyre_change_mileage" 
                                           value="<?php echo !empty($bus['last_tyre_change_mileage']) ? $bus['last_tyre_change_mileage'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Insurance -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Insurance</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Current Mileage (km)</label>
                                    <input type="number" class="form-control" name="current_mileage" 
                                           value="<?php echo !empty($bus['current_mileage']) ? $bus['current_mileage'] : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Mileage Recorded Date</label>
                                    <input type="date" class="form-control" name="mileage_recorded_date" 
                                           value="<?php echo !empty($bus['mileage_recorded_date']) ? $bus['mileage_recorded_date'] : date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Engine Service -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-dark text-white">
                                <h6 class="mb-0">Engine Service</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Last Service Date</label>
                                    <input type="date" class="form-control" name="last_engine_service_date" 
                                           value="<?php echo !empty($bus['last_engine_service_date']) ? $bus['last_engine_service_date'] : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Service Interval (km)</label>
                                    <input type="number" class="form-control" name="engine_service_interval_km" 
                                           value="<?php echo !empty($bus['engine_service_interval_km']) ? $bus['engine_service_interval_km'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>   
