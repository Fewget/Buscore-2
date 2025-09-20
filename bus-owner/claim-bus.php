<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is a bus owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bus_owner') {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';
$bus = null;
$user_id = $_SESSION['user_id'];

// Check if form data is in session
$form_data = $_SESSION['bus_form_data'] ?? null;

// Check if registration number is provided
if (isset($_GET['reg'])) {
    $registration_number = trim($_GET['reg']);
    
    try {
        // Check if bus exists
        $stmt = $pdo->prepare("SELECT * FROM buses WHERE registration_number = ?");
        $stmt->execute([$registration_number]);
        $bus = $stmt->fetch();
        
        if (!$bus) {
            $error = 'No bus found with this registration number: ' . htmlspecialchars($registration_number);
        } elseif ($bus['user_id'] == $user_id) {
            $error = 'You already own this bus.';
        } else if ($form_data) {
            // Merge form data with bus data
            $bus = array_merge($bus, [
                'bus_name' => $form_data['bus_name'] ?? '',
                'route_number' => $form_data['route_number'] ?? '',
                'route_description' => $form_data['route_description'] ?? '',
                'company_name' => $form_data['company_name'] ?? ''
            ]);
            
            // Clear the form data from session
            unset($_SESSION['bus_form_data']);
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
        error_log('Claim Bus Error: ' . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_bus'])) {
    $registration_number = trim($_POST['registration_number']);
    $company_name = trim($_POST['company_name']);
    $bus_id = (int)$_POST['bus_id'];
    
    if (empty($company_name)) {
        $error = 'Please enter your company name.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // First check if the current user already has a bus with this registration number
            $checkStmt = $pdo->prepare("SELECT id FROM buses WHERE registration_number = ? AND user_id = ?");
            $checkStmt->execute([$registration_number, $user_id]);
            
            if ($checkStmt->rowCount() > 0) {
                throw new Exception('You already have a bus with this registration number.');
            }
            
            // Get the original bus data
            $getBusStmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
            $getBusStmt->execute([$bus_id]);
            $originalBus = $getBusStmt->fetch();
            
            if (!$originalBus) {
                throw new Exception('Original bus record not found.');
            }
            
            // Generate a unique registration number by appending a suffix if needed
            $new_registration = $originalBus['registration_number'];
            $suffix = 1;
            $max_attempts = 10;
            
            while ($suffix <= $max_attempts) {
                try {
                    if ($suffix > 1) {
                        $new_registration = $originalBus['registration_number'] . '-' . $suffix;
                    }
                    
                    // Try to insert with the new registration number
                    $insertStmt = $pdo->prepare("
                        INSERT INTO buses 
                        (registration_number, bus_name, route_number, route_description, company_name, user_id, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    $insertStmt->execute([
                        $new_registration,
                        $originalBus['bus_name'],
                        $originalBus['route_number'],
                        $originalBus['route_description'],
                        $company_name,
                        $user_id
                    ]);
                    
                    // If we get here, the insert was successful
                    break;
                    
                } catch (PDOException $e) {
                    // If it's a duplicate key error, try with the next suffix
                    if ($e->errorInfo[1] == 1062 && $suffix < $max_attempts) {
                        $suffix++;
                        continue;
                    }
                    // Re-throw the exception if we've reached max attempts or it's a different error
                    throw $e;
                }
            }
            
            // Copy service records and other related data if needed
            // Example: $pdo->prepare("INSERT INTO service_records (...) SELECT ... FROM service_records WHERE bus_id = ?")->execute([$bus_id]);
            
            // Update or insert company name in bus_owners table
            $stmt = $pdo->prepare("SELECT id FROM bus_owners WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE bus_owners SET company_name = ? WHERE user_id = ?");
                $stmt->execute([$company_name, $user_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO bus_owners (user_id, company_name) VALUES (?, ?)");
                $stmt->execute([$user_id, $company_name]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = 'Bus claimed successfully!';
            header('Location: dashboard.php');
            exit();
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
            error_log('Claim Bus Error: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
        }
    }
}

$page_title = 'Claim Bus - ' . SITE_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0">
                        <i class="fas fa-hand-holding-medical me-2"></i> Claim Bus
                    </h2>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($bus): ?>
                        <div class="alert alert-info">
                            <h5>Bus Found</h5>
                            <p class="mb-2">
                                <strong>Registration:</strong> <?php echo htmlspecialchars($bus['registration_number']); ?><br>
                                <?php if (!empty($bus['bus_name'])): ?>
                                    <strong>Bus Name:</strong> <?php echo htmlspecialchars($bus['bus_name']); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($bus['route_number'])): ?>
                                    <strong>Route:</strong> <?php echo htmlspecialchars($bus['route_number']); ?><br>
                                <?php endif; ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-info-circle me-1"></i> 
                                Please confirm your company details to claim this bus.
                            </p>
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                            <input type="hidden" name="registration_number" value="<?php echo htmlspecialchars($bus['registration_number']); ?>">
                            
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Your Company Name *</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?php echo htmlspecialchars($bus['company_name'] ?? ''); ?>" required>
                                <div class="form-text">This will be displayed as the bus operator.</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-secondary me-md-2">Cancel</a>
                                <button type="submit" name="claim_bus" class="btn btn-primary">
                                    <i class="fas fa-check-circle me-1"></i> Claim This Bus
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-bus fa-4x text-muted mb-3"></i>
                            <h4>No Bus Found</h4>
                            <p class="text-muted">We couldn't find a bus with the provided registration number.</p>
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
