<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a bus owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bus_owner') {
    header('Location: ../login.php');
    exit();
}

$success = false;
$error = '';
$user_id = $_SESSION['user_id'];
$company_name = '';
$is_edit = isset($_GET['edit']) && is_numeric($_GET['edit']);
$bus = null;

// If in edit mode, fetch the existing bus data
if ($is_edit) {
    $bus_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ? AND user_id = ?");
    $stmt->execute([$bus_id, $user_id]);
    $bus = $stmt->fetch();
    
    if (!$bus) {
        $_SESSION['error'] = 'Bus not found or you do not have permission to edit it.';
        header('Location: dashboard.php');
        exit();
    }
    
    // Set company name from bus data if not already set
    if (empty($company_name) && !empty($bus['company_name'])) {
        $company_name = $bus['company_name'];
    }
}

// Get owner's company name if exists
$stmt = $pdo->prepare("SELECT company_name FROM bus_owners WHERE user_id = ?");
$stmt->execute([$user_id]);
$owner_data = $stmt->fetch();

if ($owner_data && !empty($owner_data['company_name'])) {
    $company_name = $owner_data['company_name'];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('Form submitted with POST data: ' . print_r($_POST, true));
    $registration_number = trim($_POST['registration_number'] ?? '');
    $bus_name = trim($_POST['bus_name'] ?? '');
    $route_number = trim($_POST['route_number'] ?? '');
    $route_description = trim($_POST['route_description'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $is_edit = isset($_POST['is_edit']) && $_POST['is_edit'] === '1';
    $bus_id = $is_edit ? (int)$_POST['bus_id'] : null;
    
    // Basic validation
    if (empty($registration_number) || empty($company_name)) {
        $error = 'Registration number and company name are required.';
    } elseif (!function_exists('validate_registration_number')) {
        $error = 'System error: Validation function not found. Please contact support.';
        error_log('Error: validate_registration_number function not found');
    } elseif (!validate_registration_number($registration_number)) {
        $error = 'Please enter a valid registration number (e.g., NA-1234 or 62-1234).';
    } else {
        // Format registration number
        $registration_number = format_registration_number($registration_number);
        // Make bus name NULL if empty
        $bus_name = !empty($bus_name) ? $bus_name : NULL;
        
        try {
            $pdo->beginTransaction();
            
            // Update or insert company name
            if ($owner_data) {
                $stmt = $pdo->prepare("UPDATE bus_owners SET company_name = ? WHERE user_id = ?");
                $stmt->execute([$company_name, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO bus_owners (user_id, company_name) VALUES (?, ?)");
                $stmt->execute([$user_id, $company_name]);
            }
            
            if ($is_edit && $bus_id) {
                // Update existing bus
                $stmt = $pdo->prepare("
                    UPDATE buses 
                    SET registration_number = ?, 
                        bus_name = ?, 
                        route_number = ?, 
                        route_description = ?, 
                        company_name = ?,
                        updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                $params = [
                    $registration_number,
                    $bus_name,
                    $route_number,
                    $route_description,
                    $company_name,
                    $bus_id,
                    $user_id
                ];
                
                error_log('Executing UPDATE with params: ' . print_r($params, true));
                $result = $stmt->execute($params);
                $rowCount = $stmt->rowCount();
                error_log('Update result: ' . ($result ? 'true' : 'false') . ', Rows affected: ' . $rowCount);
                
                if ($result) {
                    $success = true;
                    $_SESSION['success'] = 'Bus updated successfully!';
                    header('Location: ' . SITE_URL . '/bus-owner/dashboard.php?success=bus_updated');
                    exit();
                } else {
                    $error = 'No changes were made or bus not found.';
                }
            } else {
                // Add new bus
                $stmt = $pdo->prepare("
                    INSERT INTO buses 
                    (registration_number, bus_name, route_number, route_description, company_name, user_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $params = [
                    $registration_number,
                    $bus_name,
                    $route_number,
                    $route_description,
                    $company_name,
                    $user_id
                ];
                
                error_log('Executing INSERT with params: ' . print_r($params, true));
                $result = $stmt->execute($params);
                error_log('Insert result: ' . ($result ? 'true' : 'false'));
                
                $success = $result;
                $_SESSION['success'] = 'Bus added successfully!';
                header('Location: ' . SITE_URL . '/bus-owner/dashboard.php?success=bus_added');
                exit();
            }
            
            $pdo->commit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log(($is_edit ? 'Edit' : 'Add') . " Bus Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // Check for duplicate entry error (error code 23000 for MySQL)
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'buses.registration_number') !== false) {
                // Log the specific error for debugging
                error_log("Duplicate registration number detected: " . $registration_number);
                
                // Store the form data in session
                $_SESSION['bus_form_data'] = [
                    'registration_number' => $registration_number,
                    'bus_name' => $bus_name,
                    'route_number' => $route_number,
                    'route_description' => $route_description,
                    'company_name' => $company_name
                ];
                
                // Redirect to claim bus page with the registration number
                header('Location: claim-bus.php?reg=' . urlencode($registration_number));
                exit();
            } else {
                $error = 'An error occurred: ' . $e->getMessage();
                error_log("Non-duplicate error: " . $error);
            }
        }
    }
}

$page_title = ($is_edit ? 'Edit' : 'Add New') . ' Bus - ' . SITE_NAME;
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0">
                        <i class="fas fa-bus me-2"></i> <?php echo $is_edit ? 'Edit Bus' : 'Add New Bus'; ?>
                    </h2>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h4><i class="fas fa-check-circle"></i> Bus Added Successfully!</h4>
                            <p class="mb-0">Your bus has been added to the system.</p>
                            <div class="mt-3">
                                <a href="add-bus.php" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-plus-circle me-1"></i> Add Another Bus
                                </a>
                                <a href="dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-tachometer-alt me-1"></i> Go to Dashboard
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <?php if ($is_edit): ?>
                            <input type="hidden" name="is_edit" value="1">
                            <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                        <?php endif; ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="company_name" class="form-label">Company Name *</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?php echo htmlspecialchars(($bus['company_name'] ?? $company_name)); ?>" required>
                                <div class="invalid-feedback">Please enter your company name.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="bus_name" class="form-label">Bus Name <small class="text-muted">(Optional)</small></label>
                                    <input type="text" class="form-control" id="bus_name" name="bus_name" 
                                       value="<?php echo htmlspecialchars(($bus['bus_name'] ?? $_POST['bus_name'] ?? '')); ?>"
                                       placeholder="e.g., Nelum Kumari">
                                    <div class="form-text">Leave blank if you don't have a specific name for this bus.</div>
                                </div>
                                
                                <div class="col-12">
                                    <label for="registration_number" class="form-label">Registration Number *</label>
                                    <input type="text" class="form-control" id="registration_number" 
                                           name="registration_number" required 
                                           pattern="([A-Za-z]{2}-?\d{4}|\d{2}-?\d{4})"
                                           value="<?php echo htmlspecialchars(($bus['registration_number'] ?? $_POST['registration_number'] ?? '')); ?>"
                                           oninput="formatRegistrationNumber(this)">
                                    <div class="invalid-feedback">Please enter a valid registration number (e.g., NA-1234 or 62-1234).</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="route_number" class="form-label">Route Number <small class="text-muted">(Optional)</small></label>
                                    <input type="text" class="form-control" id="route_number" 
                                           name="route_number"
                                           value="<?php echo htmlspecialchars(($bus['route_number'] ?? $_POST['route_number'] ?? '')); ?>"
                                           placeholder="1">
                                    <div class="form-text">Leave blank if not applicable.</div>
                                </div>
                                
                                <div class="col-12">
                                    <label for="route_description" class="form-label">Route Description</label>
                                    <textarea class="form-control" id="route_description" 
                                              name="route_description" rows="3" 
                                              placeholder="e.g., From City A to City B via Highway 1"><?php echo htmlspecialchars(($bus['route_description'] ?? $_POST['route_description'] ?? '')); ?></textarea>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> <?php echo $is_edit ? 'Update Bus' : 'Save Bus'; ?>
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            <?php if ($is_edit): ?>
                            <a href="../bus_details.php?id=<?php echo $bus['id']; ?>" class="btn btn-outline-info ms-2">
                                <i class="fas fa-eye me-1"></i> View Bus
                            </a>
                            <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Format registration number as user types
function formatRegistrationNumber(input) {
    // Remove any non-alphanumeric characters
    let value = input.value.replace(/[^a-zA-Z0-9]/g, '');
    
    // If we have 6 characters, insert a dash after the first 2
    if (value.length > 2 && value.length <= 6) {
        value = value.substring(0, 2) + '-' + value.substring(2);
    }
    
    // Update the input value
    input.value = value.toUpperCase();
}

// Form validation
(function () {
    'use strict'
    
    // Make bus name not required
    document.addEventListener('DOMContentLoaded', function() {
        const busNameField = document.getElementById('bus_name');
        if (busNameField) {
            busNameField.required = false;
        }
        
        // Format registration number on page load if it exists
        const regNumberField = document.getElementById('registration_number');
        if (regNumberField && regNumberField.value) {
            formatRegistrationNumber(regNumberField);
        }
    });

    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation');

    // Loop over them and prevent submission
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>