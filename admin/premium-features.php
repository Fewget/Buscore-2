<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_feature'])) {
            // Add new premium feature
            $stmt = $pdo->prepare("INSERT INTO premium_features 
                                 (bus_id, feature_name, description, start_date, end_date, is_active)
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['bus_id'],
                $_POST['feature_name'],
                $_POST['description'],
                $_POST['start_date'],
                $_POST['end_date'],
                isset($_POST['is_active']) ? 1 : 0
            ]);
            $message = '<div class="alert alert-success">Premium feature added successfully!</div>';
        } 
        elseif (isset($_POST['update_feature'])) {
            // Update existing feature
            $stmt = $pdo->prepare("UPDATE premium_features 
                                 SET bus_id = ?, feature_name = ?, description = ?, 
                                     start_date = ?, end_date = ?, is_active = ?
                                 WHERE id = ?");
            $stmt->execute([
                $_POST['bus_id'],
                $_POST['feature_name'],
                $_POST['description'],
                $_POST['start_date'],
                $_POST['end_date'],
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['feature_id']
            ]);
            $message = '<div class="alert alert-success">Premium feature updated successfully!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM premium_features WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $message = '<div class="alert alert-success">Premium feature deleted successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting feature: ' . $e->getMessage() . '</div>';
    }
}

// Get all premium features with bus details
$features = $pdo->query("
    SELECT pf.*, b.bus_name, b.registration_number 
    FROM premium_features pf
    LEFT JOIN buses b ON pf.bus_id = b.id 
    ORDER BY pf.created_at DESC
")->fetchAll();

// Get all buses for the dropdown
$buses = $pdo->query("SELECT id, bus_name, registration_number FROM buses ORDER BY bus_name")->fetchAll();

// Get feature for editing if ID is provided
$editFeature = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM premium_features WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editFeature = $stmt->fetch();
}

// Feature form fields
$busId = $editFeature ? $editFeature['bus_id'] : '';
$featureName = $editFeature ? $editFeature['feature_name'] : '';
$description = $editFeature ? $editFeature['description'] : '';
$startDate = $editFeature ? date('Y-m-d\TH:i', strtotime($editFeature['start_date'])) : '';
$endDate = $editFeature ? date('Y-m-d\TH:i', strtotime($editFeature['end_date'])) : '';
$isActive = $editFeature ? $editFeature['is_active'] : 1;

// Set page title
$page_title = 'Premium Features Management';
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <main class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Premium Features Management</h1>
            </div>

            <?php echo $message; ?>

            <div class="row">
                <!-- Add/Edit Form -->
                <div class="col-md-5 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?php echo $editFeature ? 'Edit' : 'Add New'; ?> Premium Feature</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <?php if ($editFeature): ?>
                                    <input type="hidden" name="feature_id" value="<?php echo $editFeature['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="bus_id" class="form-label">Bus</label>
                                    <select class="form-select" id="bus_id" name="bus_id" required>
                                        <option value="">Select a bus</option>
                                        <?php foreach ($buses as $bus): ?>
                                            <option value="<?php echo $bus['id']; ?>" 
                                                    <?php echo ($busId == $bus['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($bus['bus_name'] . ' (' . $bus['registration_number'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="feature_name" class="form-label">Feature</label>
                                    <select class="form-select" id="feature_name" name="feature_name" required>
                                        <option value="show_company_name" <?php echo ($featureName === 'show_company_name') ? 'selected' : ''; ?>>Show Company Name</option>
                                        <option value="show_bus_name" <?php echo ($featureName === 'show_bus_name') ? 'selected' : ''; ?>>Show Bus Name</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                        echo htmlspecialchars($description); 
                                    ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="datetime-local" class="form-control" id="start_date" name="start_date" 
                                               value="<?php echo $startDate; ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="datetime-local" class="form-control" id="end_date" name="end_date" 
                                               value="<?php echo $endDate; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo ($isActive) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                                
                                <button type="submit" name="<?php echo $editFeature ? 'update_feature' : 'add_feature'; ?>" 
                                        class="btn btn-primary">
                                    <?php echo $editFeature ? 'Update' : 'Add'; ?> Feature
                                </button>
                                
                                <?php if ($editFeature): ?>
                                    <a href="premium-features.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Features List -->
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Premium Features</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($features) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Bus</th>
                                                <th>Feature</th>
                                                <th>Description</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($features as $feature): 
                                                $endDate = new DateTime($feature['end_date']);
                                                $now = new DateTime();
                                                $interval = $now->diff($endDate);
                                                $isActive = $feature['is_active'] && $endDate > $now;
                                            ?>
                                                <tr>
                                                    <td>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if ($endDate > $now) {
                                                            echo $interval->format('%a days, %h hours left');
                                                        } else {
                                                            echo 'Ended';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <a href="?edit=<?php echo $feature['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?delete=<?php echo $feature['id']; ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('Are you sure you want to delete this feature?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No premium features found.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Initialize countdown timers
document.addEventListener('DOMContentLoaded', function() {
    // You can add JavaScript for countdown timers here if needed
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
