<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check admin access
checkAdminAccess();

$success = false;
$error = '';
$bus_owners = [];
$bus = null;

// Get all bus owners for the dropdown
try {
    $stmt = $pdo->query("SELECT users.id, users.username, bus_owners.company_name 
                         FROM users 
                         LEFT JOIN bus_owners ON users.id = bus_owners.user_id 
                         WHERE role = 'bus_owner' OR role = 'owner' 
                         ORDER BY users.username");
    $bus_owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching bus owners: ' . $e->getMessage();
}

// Get bus details
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $bus_id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM buses WHERE id = ?");
        $stmt->execute([$bus_id]);
        $bus = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$bus) {
            $_SESSION['error'] = 'Bus not found';
            header('Location: buses.php');
            exit();
        }
    } catch (PDOException $e) {
        $error = 'Error fetching bus details: ' . $e->getMessage();
    }
} else {
    $_SESSION['error'] = 'Invalid bus ID';
    header('Location: buses.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and sanitize form data
        $registration_number = trim($_POST['registration_number'] ?? '');
        $bus_name = trim($_POST['bus_name'] ?? '');
        $route_number = trim($_POST['route_number'] ?? '');
        $route_description = trim($_POST['route_description'] ?? '');
        $owner_id = intval($_POST['owner_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $seats = intval($_POST['seats'] ?? 0);
        
        // Validate required fields
        if (empty($registration_number) || empty($route_number) || $owner_id <= 0) {
            throw new Exception('Please fill in all required fields');
        }
        
        // Validate registration number format (example: XX-1234)
        if (!preg_match('/^[A-Za-z]{2,3}-\d{1,4}$/', $registration_number)) {
            throw new Exception('Please enter a valid registration number (e.g., ABC-1234)');
        }
        
        // Check if registration number already exists for a different bus
        $stmt = $pdo->prepare("SELECT id FROM buses WHERE registration_number = ? AND id != ?");
        $stmt->execute([$registration_number, $bus_id]);
        if ($stmt->fetch()) {
            throw new Exception('A bus with this registration number already exists');
        }
        
        // Update bus in database
        $stmt = $pdo->prepare("
            UPDATE buses 
            SET registration_number = ?, 
                bus_name = ?, 
                route_number = ?, 
                route_description = ?, 
                user_id = ?,
                status = ?,
                seats = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $registration_number,
            $bus_name,
            $route_number,
            $route_description,
            $owner_id,
            $status,
            $seats,
            $bus_id
        ]);
        
        $_SESSION['success'] = 'Bus updated successfully';
        header('Location: bus-details.php?id=' . $bus_id);
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Bus: <?php echo htmlspecialchars($bus['registration_number']); ?></h5>
                    <a href="bus-details.php?id=<?php echo $bus_id; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Bus Details
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registration_number" class="form-label">Registration Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="registration_number" name="registration_number" 
                                           value="<?php echo htmlspecialchars($bus['registration_number']); ?>" required>
                                    <div class="form-text">Format: ABC-1234</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bus_name" class="form-label">Bus Name</label>
                                    <input type="text" class="form-control" id="bus_name" name="bus_name" 
                                           value="<?php echo htmlspecialchars($bus['bus_name'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="route_number" class="form-label">Route Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="route_number" name="route_number" 
                                           value="<?php echo htmlspecialchars($bus['route_number']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="seats" class="form-label">Number of Seats</label>
                                    <input type="number" class="form-control" id="seats" name="seats" min="1" 
                                           value="<?php echo htmlspecialchars($bus['seats'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="route_description" class="form-label">Route Description</label>
                            <textarea class="form-control" id="route_description" name="route_description" 
                                      rows="3"><?php echo htmlspecialchars($bus['route_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="owner_id" class="form-label">Bus Owner <span class="text-danger">*</span></label>
                                    <select class="form-select" id="owner_id" name="owner_id" required>
                                        <option value="">Select Owner</option>
                                        <?php foreach ($bus_owners as $owner): ?>
                                            <option value="<?php echo $owner['id']; ?>" 
                                                <?php echo ($owner['id'] == $bus['user_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($owner['company_name'] . ' (' . $owner['username'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo ($bus['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($bus['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="maintenance" <?php echo ($bus['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="bus-details.php?id=<?php echo $bus_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Form validation
(function () {
    'use strict';
    
    // Fetch the form we want to apply custom validation to
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
})();
</script>
