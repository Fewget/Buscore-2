<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: /BS/login.php');
    exit();
}

$pageTitle = 'View Bus Reports';
$reports = [];
$error = '';

// Get search parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? date('Y-m-d');

try {
    $query = "SELECT r.*, 
                     GROUP_CONCAT(DISTINCT CONCAT(b.bus_name, ' (', b.registration_number, ')') SEPARATOR ', ') as bus_info
              FROM bus_reports r
              LEFT JOIN buses b ON r.bus_number = b.registration_number
              WHERE 1=1";
    
    $params = [];
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (r.bus_number LIKE ? OR r.issue_types LIKE ? OR r.reporter_name LIKE ? OR r.reporter_email LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
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
    <h2>Bus Reports</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Search and Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Bus number, issue, reporter...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reports Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($reports)): ?>
                <div class="alert alert-info">No reports found matching your criteria. <a href="view-reports.php">Show all reports</a></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Report ID</th>
                                <th>Bus Number</th>
                                <th>Issue Types</th>
                                <th>Reported On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td>#<?php echo $report['id']; ?></td>
                                    <td><?php echo htmlspecialchars($report['bus_number']); ?></td>
                                    <td>
                                        <?php 
                                        $issueTypes = explode(',', $report['issue_types']);
                                        foreach ($issueTypes as $type) {
                                            echo '<span class="badge bg-secondary me-1">' . ucfirst(str_replace('_', ' ', trim($type))) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($report['created_at'])); ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = [
                                            'pending' => 'bg-warning',
                                            'reviewed' => 'bg-info',
                                            'resolved' => 'bg-success'
                                        ][$report['status']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($report['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view-report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Report ID</th>
                                <th>Bus Number</th>
                                <th>Bus Info</th>
                                <th>Issue Types</th>
                                <th>Reported By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td>#<?php echo $report['id']; ?></td>
                                    <td><?php echo htmlspecialchars($report['bus_number']); ?></td>
                                    <td><?php echo !empty($report['bus_info']) ? htmlspecialchars($report['bus_info']) : 'N/A'; ?></td>
                                    <td>
                                        <?php 
                                        $issues = explode(',', $report['issue_types']);
                                        echo '<span class="badge bg-secondary me-1">' . implode('</span> <span class="badge bg-secondary me-1">', $issues) . '</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($report['is_anonymous']): ?>
                                            <span class="text-muted">Anonymous</span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($report['reporter_name']); ?>
                                            <?php if (!empty($report['reporter_email'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($report['reporter_email']); ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($report['created_at'])); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'pending' => 'bg-warning',
                                            'in_progress' => 'bg-info',
                                            'resolved' => 'bg-success',
                                            'rejected' => 'bg-danger'
                                        ][$report['status']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view-report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="update-report-status.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-warning" title="Update Status">
                                            <i class="fas fa-edit"></i>
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
</div>

<?php include '../includes/footer.php'; ?>
