<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

$message = '';

// Handle bus status and premium features update
if (isset($_POST['update_bus']) && isset($_POST['bus_id'])) {
    try {
        $updates = [];
        $params = [];
        
        if (isset($_POST['status'])) {
            $updates[] = 'status = ?';
            $params[] = $_POST['status'];
        }
        
        $updates[] = 'show_company_name = ?';
        $params[] = isset($_POST['show_company_name']) ? 1 : 0;
        
        $updates[] = 'show_bus_name = ?';
        $params[] = isset($_POST['show_bus_name']) ? 1 : 0;
        
        $params[] = $_POST['bus_id'];
        
        $sql = "UPDATE buses SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $message = '<div class="alert alert-success">Bus settings updated successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error updating bus settings: ' . $e->getMessage() . '</div>';
    }
}

// Handle bus deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete related records first
        $pdo->prepare("DELETE FROM reviews WHERE bus_id = ?")->execute([$_GET['delete']]);
        $pdo->prepare("DELETE FROM ratings WHERE bus_id = ?")->execute([$_GET['delete']]);
        
        // Then delete the bus
        $pdo->prepare("DELETE FROM buses WHERE id = ?")->execute([$_GET['delete']]);
        
        // Commit transaction
        $pdo->commit();
        
        $message = '<div class="alert alert-success">Bus and all related data deleted successfully!</div>';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = '<div class="alert alert-danger">Error deleting bus: ' . $e->getMessage() . '</div>';
    }
}

// Get search parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$owner_id = $_GET['owner_id'] ?? '';

// Build query
$query = "SELECT b.*, u.username as owner_username, 
          (SELECT (AVG(driver_rating) + AVG(conductor_rating) + AVG(bus_condition_rating)) / 3 
           FROM ratings 
           WHERE bus_id = b.id) as avg_rating,
          (SELECT COUNT(*) FROM reviews WHERE bus_id = b.id) as review_count
          FROM buses b 
          LEFT JOIN users u ON b.user_id = u.id 
          WHERE 1=1";
          
$params = [];

if (!empty($search)) {
    $query .= " AND (b.registration_number LIKE ? OR b.bus_name LIKE ? OR b.company_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($status)) {
    $query .= " AND b.status = ?";
    $params[] = $status;
}

if (!empty($owner_id) && is_numeric($owner_id)) {
    $query .= " AND b.user_id = ?";
    $params[] = $owner_id;
}

$query .= " ORDER BY b.created_at DESC";

// Ensure required columns exist
try {
    $pdo->exec("ALTER TABLE buses ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active'");
    $pdo->exec("ALTER TABLE buses ADD COLUMN IF NOT EXISTS show_company_name TINYINT(1) DEFAULT 1");
    $pdo->exec("ALTER TABLE buses ADD COLUMN IF NOT EXISTS show_bus_name TINYINT(1) DEFAULT 1");
    
    // Execute query and get buses
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no buses found, initialize as empty array
    if (!is_array($buses)) {
        $buses = [];
    }
    
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $buses = [];
}

// Get all bus owners for filter
try {
    $owners = $pdo->query("SELECT id, username FROM users WHERE role = 'bus_owner' OR role = 'owner' ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Error fetching bus owners: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $owners = [];
}

// Set page title
$page_title = 'Manage Buses';

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <main class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Buses</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add-bus.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Bus
                    </a>
                </div>
            </div>

            <?php echo $message; ?>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search by registration, bus name, or company..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="maintenance" <?php echo $status === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="owner_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Owners</option>
                                <?php foreach ($owners as $owner): ?>
                                    <option value="<?php echo $owner['id']; ?>" <?php echo $owner_id == $owner['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($owner['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Buses Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($buses) && is_array($buses) && count($buses) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Registration</th>
                                        <th>Bus Name</th>
                                        <th>Company</th>
                                        <th>Owner</th>
                                        <th>Rating</th>
                                        <th>Status</th>
                                        <th>Show Company</th>
                                        <th>Show Bus Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($buses as $bus): 
                                        $rating = $bus['avg_rating'] ? number_format($bus['avg_rating'], 1) : 'N/A';
                                    ?>
                                        <tr>
                                            <td><?php echo $bus['id']; ?></td>
                                            <td><?php echo htmlspecialchars($bus['registration_number']); ?></td>
                                            <td><?php echo htmlspecialchars($bus['bus_name']); ?></td>
                                            <td><?php echo htmlspecialchars($bus['company_name']); ?></td>
                                            <td><?php echo htmlspecialchars($bus['owner_username'] ?? 'N/A'); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="text-warning me-1">
                                                        <i class="fas fa-star"></i> <?php echo $rating; ?>
                                                    </span>
                                                    <small class="text-muted">(<?php echo $bus['review_count']; ?>)</small>
                                                </div>
                                            </td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="active" <?php echo ($bus['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo ($bus['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                        <option value="maintenance" <?php echo ($bus['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                                    </select>
                                                    <input type="hidden" name="update_bus" value="1">
                                                </form>
                                            </td>
                                            <td class="text-center">
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($bus['status'] ?? 'active'); ?>">
                                                    <div class="form-check form-switch d-inline-block">
                                                        <input class="form-check-input" type="checkbox" role="switch" 
                                                               name="show_company_name" value="1" 
                                                               onchange="this.form.submit()"
                                                               <?php echo ($bus['show_company_name'] ?? 1) ? 'checked' : ''; ?>>
                                                    </div>
                                                    <input type="hidden" name="update_bus" value="1">
                                                </form>
                                            </td>
                                            <td class="text-center">
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($bus['status'] ?? 'active'); ?>">
                                                    <div class="form-check form-switch d-inline-block">
                                                        <input class="form-check-input" type="checkbox" role="switch" 
                                                               name="show_bus_name" value="1" 
                                                               onchange="this.form.submit()"
                                                               <?php echo ($bus['show_bus_name'] ?? 1) ? 'checked' : ''; ?>>
                                                    </div>
                                                    <input type="hidden" name="update_bus" value="1">
                                                </form>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit-bus.php?id=<?php echo $bus['id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $bus['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this bus? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No buses found matching your criteria.</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
