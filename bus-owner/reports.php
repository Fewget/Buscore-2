<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in and is a bus owner
check_bus_owner_access();

$pageTitle = 'My Bus Reports';
$reports = [];
$error = '';

// Get the bus owner's ID
$ownerId = $_SESSION['user_id'];

// Get search parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? date('Y-m-d');

try {
    // Get reports for buses owned by this owner
    $query = "SELECT r.*, 
                     b.bus_name, b.registration_number,
                     GROUP_CONCAT(DISTINCT r.issue_types) as all_issues
              FROM bus_reports r
              JOIN buses b ON r.bus_number = b.registration_number
              WHERE b.user_id = ?";
    
    $params = [$ownerId];
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (r.bus_number LIKE ? OR r.issue_types LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm]);
    }
    
    // Add status filter
    if ($status !== 'all') {
        $query .= " AND r.status = ?";
        $params[] = $status;
    }
    
    // Add date range filter
    if (!empty($date_from)) {
        $query .= " AND DATE(r.created_at) >= ?";
        $params[] = $date_from;
    }
    if (!empty($date_to)) {
        $query .= " AND DATE(r.created_at) <= ?";
        $params[] = $date_to;
    }
    
    $query .= " GROUP BY r.id ORDER BY r.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error fetching reports: " . $e->getMessage();
}

// Include header
include '../includes/header.php';
?>

<div class="container mt-4">
    <h2>My Bus Reports</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Search and Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-5">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Bus number or issue...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reports Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($reports)): ?>
                <div class="alert alert-info">No reports found matching your criteria.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Bus</th>
                                <th>Issues</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): 
                                $statusClass = [
                                    'pending' => 'bg-warning',
                                    'in_progress' => 'bg-info',
                                    'resolved' => 'bg-success',
                                    'rejected' => 'bg-danger'
                                ][$report['status']] ?? 'bg-secondary';
                            ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($report['created_at'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($report['bus_number']); ?></strong>
                                        <?php if (!empty($report['bus_name'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($report['bus_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $issues = explode(',', $report['all_issues']);
                                        $uniqueIssues = array_unique($issues);
                                        foreach (array_slice($uniqueIssues, 0, 3) as $issue): 
                                        ?>
                                            <span class="badge bg-secondary me-1 mb-1">
                                                <?php echo htmlspecialchars(trim($issue)); ?>
                                            </span>
                                        <?php endforeach; 
                                        if (count($uniqueIssues) > 3): ?>
                                            <span class="badge bg-light text-dark">+<?php echo (count($uniqueIssues) - 3); ?> more</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view-report.php?id=<?php echo $report['id']; ?>" 
                                           class="btn btn-sm btn-primary" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Statistics Card -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Reports</h5>
                    <h3 class="mb-0"><?php echo count($reports); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h5 class="card-title">Pending</h5>
                    <h3 class="mb-0">
                        <?php 
                        $pending = array_filter($reports, fn($r) => $r['status'] === 'pending');
                        echo count($pending);
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Resolved</h5>
                    <h3 class="mb-0">
                        <?php 
                        $resolved = array_filter($reports, fn($r) => $r['status'] === 'resolved');
                        echo count($resolved);
                        ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
    margin-bottom: 0.25rem;
}
</style>

<?php include '../includes/footer.php'; ?>
