<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

$success = false;
$error = '';
$bus_owners = [];

// Get all bus owners for the dropdown
try {
    $stmt = $pdo->query("SELECT users.id, username, company_name FROM users 
                        LEFT JOIN bus_owners ON users.id = bus_owners.user_id 
                        WHERE role = 'bus_owner' OR role = 'owner' 
                        ORDER BY username");
    $bus_owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching bus owners: ' . $e->getMessage();
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
        $status = 'active'; // Default status
        
        // Validate required fields
        if (empty($registration_number) || empty($route_number) || $owner_id <= 0) {
            throw new Exception('Please fill in all required fields');
        }
        
        // Validate registration number format (example: XX-1234)
        if (!preg_match('/^[A-Za-z]{2,3}-\d{1,4}$/', $registration_number)) {
            throw new Exception('Please enter a valid registration number (e.g., ABC-1234)');
        }
        
        // Check if registration number already exists
        $stmt = $pdo->prepare("SELECT id FROM buses WHERE registration_number = ?");
        $stmt->execute([$registration_number]);
        if ($stmt->fetch()) {
            throw new Exception('A bus with this registration number already exists');
        }
        
        // Insert new bus
        $stmt = $pdo->prepare("INSERT INTO buses 
                              (registration_number, bus_name, route_number, route_description, owner_id, status, created_at, updated_at) 
                              VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $stmt->execute([
            $registration_number,
            $bus_name,
            $route_number,
            $route_description,
            $owner_id,
            $status
        ]);
        
        $bus_id = $pdo->lastInsertId();
        $success = true;
        
        // Redirect to edit page after successful creation
        header("Location: edit-bus.php?id=" . $bus_id . "&created=1");
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Set page title
$page_title = 'Add New Bus';

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Add New Bus</h5>
                    <a href="buses.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Buses
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="post" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="registration_number" class="form-label">Registration Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="registration_number" name="registration_number" 
                                       value="<?php echo htmlspecialchars($_POST['registration_number'] ?? ''); ?>" 
                                       pattern="[A-Za-z]{2,3}-\d{1,4}" required>
                                <div class="invalid-feedback">
                                    Please enter a valid registration number (e.g., ABC-1234)
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="owner_id" class="form-label">Bus Owner <span class="text-danger">*</span></label>
                                <select class="form-select" id="owner_id" name="owner_id" required>
                                    <option value="">Select Owner</option>
                                    <?php foreach ($bus_owners as $owner): ?>
                                        <option value="<?php echo $owner['id']; ?>" 
                                            <?php echo (isset($_POST['owner_id']) && $_POST['owner_id'] == $owner['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($owner['username']); ?>
                                            <?php if (!empty($owner['company_name'])): ?>
                                                (<?php echo htmlspecialchars($owner['company_name']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a bus owner
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="bus_name" class="form-label">Bus Name (Optional)</label>
                                <input type="text" class="form-control" id="bus_name" name="bus_name" 
                                       value="<?php echo htmlspecialchars($_POST['bus_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="route_number" class="form-label">Route Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="route_number" name="route_number" 
                                       value="<?php echo htmlspecialchars($_POST['route_number'] ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please enter a route number
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label for="route_description" class="form-label">Route Description</label>
                                <textarea class="form-control" id="route_description" name="route_description" 
                                          rows="3"><?php echo htmlspecialchars($_POST['route_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Add Bus
                                </button>
                                <a href="buses.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation')
    
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>
