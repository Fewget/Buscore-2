<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is admin
check_admin_access();

$page_title = 'Manage Premium Packages';
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("INSERT INTO premium_packages (name, description, features, price, duration_days, is_active) 
                                         VALUES (?, ?, ?, ?, ?, ?)");
                    $features = json_encode(explode(',', $_POST['features']));
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $features,
                        $_POST['price'],
                        $_POST['duration_days'],
                        isset($_POST['is_active']) ? 1 : 0
                    ]);
                    $success = 'Package added successfully!';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE premium_packages 
                                         SET name = ?, description = ?, features = ?, price = ?, 
                                             duration_days = ?, is_active = ?, updated_at = NOW() 
                                         WHERE id = ?");
                    $features = json_encode(explode(',', $_POST['features']));
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['description'],
                        $features,
                        $_POST['price'],
                        $_POST['duration_days'],
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['id']
                    ]);
                    $success = 'Package updated successfully!';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM premium_packages WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $success = 'Package deleted successfully!';
                    break;
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get all packages
$packages = $pdo->query("SELECT * FROM premium_packages ORDER BY is_active DESC, price ASC")->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?php echo $page_title; ?></h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPackageModal">
            <i class="fas fa-plus"></i> Add New Package
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Features</th>
                            <th>Price (Rs.)</th>
                            <th>Duration (Days)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $package): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($package['name']); ?></td>
                                <td><?php echo htmlspecialchars($package['description']); ?></td>
                                <td>
                                    <?php 
                                    $features = json_decode($package['features'], true);
                                    echo implode(', ', array_map('htmlspecialchars', $features));
                                    ?>
                                </td>
                                <td><?php echo number_format($package['price'], 2); ?></td>
                                <td><?php echo $package['duration_days'] ?: 'Lifetime'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $package['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $package['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-package" 
                                            data-id="<?php echo $package['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($package['name']); ?>"
                                            data-description="<?php echo htmlspecialchars($package['description']); ?>"
                                            data-features="<?php echo htmlspecialchars(implode(',', json_decode($package['features'], true))); ?>"
                                            data-price="<?php echo $package['price']; ?>"
                                            data-duration="<?php echo $package['duration_days']; ?>"
                                            data-active="<?php echo $package['is_active']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this package?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $package['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Package Modal -->
<div class="modal fade" id="addPackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Package Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Features (comma separated)</label>
                        <input type="text" name="features" class="form-control" 
                               placeholder="display_company_name,display_bus_name" required>
                        <div class="form-text">Example: display_company_name,display_bus_name</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (Rs.)</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (Days)</label>
                            <input type="number" name="duration_days" class="form-control" min="0" value="30">
                            <div class="form-text">0 for lifetime</div>
                        </div>
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Package Modal -->
<div class="modal fade" id="editPackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Package Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Features (comma separated)</label>
                        <input type="text" name="features" id="edit_features" class="form-control" required>
                        <div class="form-text">Example: display_company_name,display_bus_name</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (Rs.)</label>
                            <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (Days)</label>
                            <input type="number" name="duration_days" id="edit_duration" class="form-control" min="0">
                            <div class="form-text">0 for lifetime</div>
                        </div>
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                        <label class="form-check-label" for="edit_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit button clicks
    document.querySelectorAll('.edit-package').forEach(button => {
        button.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('editPackageModal'));
            
            // Set form values
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_description').value = this.dataset.description;
            document.getElementById('edit_features').value = this.dataset.features;
            document.getElementById('edit_price').value = this.dataset.price;
            document.getElementById('edit_duration').value = this.dataset.duration;
            document.getElementById('edit_is_active').checked = this.dataset.active === '1';
            
            modal.show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
