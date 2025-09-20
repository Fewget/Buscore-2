<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

$message = '';
$bus_owners = [];

// Handle bus owner status update
if (isset($_POST['update_status']) && isset($_POST['owner_id'])) {
    try {
        $owner_id = intval($_POST['owner_id']);
        $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
        
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND (role = 'bus_owner' OR role = 'owner')");
        $stmt->execute([$status, $owner_id]);
        
        $message = '<div class="alert alert-success">Owner status updated successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error updating owner status: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Handle bus owner deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $pdo->beginTransaction();
        
        $owner_id = intval($_GET['delete']);
        
        // Check if owner has any buses
        $stmt = $pdo->prepare("SELECT COUNT(*) as bus_count FROM buses WHERE user_id = ?");
        $stmt->execute([$owner_id]);
        $result = $stmt->fetch();
        
        if ($result['bus_count'] > 0) {
            throw new Exception('Cannot delete owner with active buses. Please reassign or delete the buses first.');
        }
        
        // Delete from bus_owners table
        $pdo->prepare("DELETE FROM bus_owners WHERE user_id = ?")->execute([$owner_id]);
        
        // Delete from users table
        $pdo->prepare("DELETE FROM users WHERE id = ? AND (role = 'bus_owner' OR role = 'owner')")->execute([$owner_id]);
        
        $pdo->commit();
        
        $message = '<div class="alert alert-success">Bus owner deleted successfully!</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<div class="alert alert-danger">Error deleting bus owner: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Get all bus owners with their details
try {
    $sql = "SELECT 
                u.id, 
                u.username, 
                u.email, 
                u.status, 
                u.created_at,
                bo.company_name,
                (SELECT COUNT(*) FROM buses WHERE user_id = u.id) as bus_count
            FROM users u
            LEFT JOIN bus_owners bo ON u.id = bo.user_id
            WHERE u.role = 'bus_owner' OR u.role = 'owner'
            ORDER BY u.created_at DESC";
    
    $bus_owners = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Error fetching bus owners: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Set page title
$page_title = 'Manage Bus Owners';

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Manage Bus Owners</h5>
                    <a href="add-bus-owner.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add New Owner
                    </a>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="ownersTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Company</th>
                                    <th>Buses</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bus_owners as $owner): ?>
                                    <tr>
                                        <td><?php echo $owner['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    <span class="avatar-title bg-soft-primary text-primary rounded">
                                                        <?php echo strtoupper(substr($owner['username'], 0, 2)); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($owner['username']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($owner['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo !empty($owner['company_name']) ? htmlspecialchars($owner['company_name']) : '<span class="text-muted">N/A</span>'; ?></td>
                                        <td>
                                            <span class="badge bg-soft-primary text-dark">
                                                <i class="fas fa-bus me-1"></i> <?php echo $owner['bus_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="owner_id" value="<?php echo $owner['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $owner['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" name="update_status" class="btn btn-sm btn-<?php echo $owner['status'] === 'active' ? 'success' : 'secondary'; ?> btn-sm">
                                                    <?php echo ucfirst($owner['status']); ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($owner['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit-bus-owner.php?id=<?php echo $owner['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $owner['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this bus owner? This action cannot be undone.');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                                <a href="buses.php?user_id=<?php echo $owner['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   title="View Buses">
                                                    <i class="fas fa-bus"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize DataTable
document.addEventListener('DOMContentLoaded', function() {
    $('#ownersTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 25,
        columnDefs: [
            { orderable: false, targets: [7] } // Disable sorting on actions column
        ]
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            html: true,
            boundary: 'window'
        });
    });
});
</script>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>
