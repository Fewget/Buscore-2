<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: /BS/login.php');
    exit();
}

$pageTitle = 'View Report';
$error = '';
$report = null;

// Get report ID
$reportId = $_GET['id'] ?? 0;

if (!$reportId) {
    header('Location: view-reports.php');
    exit();
}

try {
    // Get report details
    $stmt = $pdo->prepare("
        SELECT r.*, 
               b.bus_name, b.registration_number, b.company_name as bus_company,
               bo.company_name as owner_company
        FROM bus_reports r
        LEFT JOIN buses b ON r.bus_number = b.registration_number
        LEFT JOIN bus_owners bo ON b.owner_id = bo.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        throw new Exception('Report not found');
    }
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $newStatus = $_POST['status'];
        $adminNotes = trim($_POST['admin_notes']);
        
        $stmt = $pdo->prepare("
            UPDATE bus_reports 
            SET status = ?, admin_notes = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$newStatus, $adminNotes, $reportId]);
        
        // Refresh report data
        $stmt = $pdo->prepare("SELECT * FROM bus_reports WHERE id = ?");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $success = "Report status updated successfully.";
        
    } catch (Exception $e) {
        $error = "Error updating report: " . $e->getMessage();
    }
}

// Include header
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Report #<?php echo $reportId; ?></h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="view-reports.php">Reports</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Report #<?php echo $reportId; ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="view-reports.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
            <?php if ($report['status'] !== 'resolved'): ?>
                <button class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#resolveModal">
                    <i class="fas fa-check"></i> Mark as Resolved
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($report): ?>
        <div class="row">
            <div class="col-md-8">
                <!-- Report Details Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Report Details</h5>
                        <span class="badge bg-<?php echo $report['status'] === 'resolved' ? 'success' : ($report['status'] === 'pending' ? 'warning' : 'info'); ?>">
                            <?php echo ucfirst($report['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Reported On:</div>
                            <div class="col-md-8">
                                <i class="far fa-calendar-alt me-2"></i> 
                                <?php echo date('F j, Y, g:i a', strtotime($report['created_at'])); ?>
                                <small class="text-muted ms-2">(<?php echo time_elapsed_string($report['created_at']); ?>)</small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Bus Number:</div>
                            <div class="col-md-8">
                                <i class="fas fa-bus me-2"></i> 
                                <?php echo htmlspecialchars($report['bus_number']); ?>
                                <?php if (!empty($report['bus_name'])): ?>
                                    <span class="text-muted">(<?php echo htmlspecialchars($report['bus_name']); ?>)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($report['bus_company'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Bus Company:</div>
                            <div class="col-md-8">
                                <i class="fas fa-building me-2"></i> 
                                <?php echo htmlspecialchars($report['bus_company']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Issue Types:</div>
                            <div class="col-md-8">
                                <?php 
                                $issueTypes = explode(',', $report['issue_types']);
                                foreach ($issueTypes as $type) {
                                    echo '<span class="badge bg-secondary me-1 mb-1">' . 
                                         ucfirst(str_replace('_', ' ', trim($type))) . 
                                         '</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($report['description'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Description:</div>
                            <div class="col-md-8">
                                <div class="border rounded p-3 bg-light">
                                    <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($report['location'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Location:</div>
                            <div class="col-md-8">
                                <i class="fas fa-map-marker-alt me-2"></i> 
                                <?php echo htmlspecialchars($report['location']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($report['date_time'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Incident Date/Time:</div>
                            <div class="col-md-8">
                                <i class="far fa-clock me-2"></i> 
                                <?php echo date('F j, Y, g:i a', strtotime($report['date_time'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($report['photo_path'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Photo:</div>
                            <div class="col-md-8">
                                <a href="<?php echo htmlspecialchars($report['photo_path']); ?>" target="_blank" class="d-inline-block">
                                    <img src="<?php echo htmlspecialchars($report['photo_path']); ?>" alt="Report Photo" class="img-thumbnail" style="max-width: 200px;">
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Reported By:</div>
                            <div class="col-md-8">
                                <?php if ($report['is_anonymous']): ?>
                                    <i class="fas fa-user-secret me-2"></i> Anonymous
                                <?php else: ?>
                                    <i class="fas fa-user me-2"></i>
                                    <?php 
                                    $reporter = [];
                                    if (!empty($report['reporter_name'])) $reporter[] = htmlspecialchars($report['reporter_name']);
                                    if (!empty($report['reporter_email'])) $reporter[] = htmlspecialchars($report['reporter_email']);
                                    echo implode(' &bull; ', $reporter);
                                    ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
                
                <!-- Update Status Form -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Update Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="report_id" value="<?php echo $reportId; ?>">
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="pending" <?php echo $report['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="reviewed" <?php echo $report['status'] === 'reviewed' ? 'selected' : ''; ?>>Under Review</option>
                                    <option value="resolved" <?php echo $report['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="admin_notes" class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" placeholder="Add any additional notes or comments..."><?php echo isset($_POST['admin_notes']) ? htmlspecialchars($_POST['admin_notes']) : ''; ?></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="submit" name="update_status" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update Status
                                </button>
                                
                                <?php if ($report['status'] !== 'resolved'): ?>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resolveModal">
                                    <i class="fas fa-check me-1"></i> Mark as Resolved
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Resolve Modal -->
                <div class="modal fade" id="resolveModal" tabindex="-1" aria-labelledby="resolveModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title" id="resolveModalLabel">Mark as Resolved</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="post" action="">
                                <div class="modal-body">
                                    <input type="hidden" name="report_id" value="<?php echo $reportId; ?>">
                                    <input type="hidden" name="status" value="resolved">
                                    
                                    <div class="mb-3">
                                        <label for="resolution_notes" class="form-label">Resolution Notes</label>
                                        <textarea class="form-control" id="resolution_notes" name="admin_notes" rows="4" required placeholder="Please provide details about how this report was resolved..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="update_status" class="btn btn-success">
                                        <i class="fas fa-check me-1"></i> Confirm Resolution
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Report History -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Report History</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6>Report Created</h6>
                                    <p class="text-muted small mb-0">
                                        <?php echo date('M j, Y \a\t g:i a', strtotime($report['created_at'])); ?>
                                    </p>
                                    <?php if (!empty($report['admin_notes'])): ?>
                                        <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($report['status'] !== 'pending'): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-<?php echo $report['status'] === 'resolved' ? 'success' : 'info'; ?>"></div>
                                <div class="timeline-content">
                                    <h6>Marked as <?php echo ucfirst($report['status']); ?></h6>
                                    <p class="text-muted small mb-0">
                                        <?php echo date('M j, Y \a\t g:i a', strtotime($report['updated_at'])); ?>
                                    </p>
                                    <?php if (!empty($report['admin_notes'])): ?>
                                        <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Bus Information -->
                <?php if (!empty($report['bus_name']) || !empty($report['bus_company'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Bus Info:</div>
                            <div class="col-md-8">
                                <?php 
                                $busInfo = [];
                                if (!empty($report['bus_name'])) {
                                    $busInfo[] = htmlspecialchars($report['bus_name']);
                                }
                                if (!empty($report['bus_company'])) {
                                    $busInfo[] = htmlspecialchars($report['bus_company']);
                                }
                                echo implode(' - ', $busInfo);
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Issue Types:</div>
                            <div class="col-md-8">
                                <?php 
                                $issues = explode(',', $report['issue_types']);
                                foreach ($issues as $issue): 
                                ?>
                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars(trim($issue)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($report['description'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Description:</div>
                            <div class="col-md-8">
                                <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Reported By:</div>
                            <div class="col-md-8">
                                <?php if ($report['is_anonymous']): ?>
                                    <span class="text-muted">Anonymous</span>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($report['reporter_name']); ?>
                                    <?php if (!empty($report['reporter_email'])): ?>
                                        <br><a href="mailto:<?php echo htmlspecialchars($report['reporter_email']); ?>">
                                            <?php echo htmlspecialchars($report['reporter_email']); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($report['reporter_phone'])): ?>
                                        <br>Phone: <?php echo htmlspecialchars($report['reporter_phone']); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 fw-bold">Status:</div>
                            <div class="col-md-8">
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
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Update Status Form -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Update Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="pending" <?php echo $report['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_progress" <?php echo $report['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="resolved" <?php echo $report['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="rejected" <?php echo $report['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="admin_notes" class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                          placeholder="Add any internal notes about this report..."><?php echo htmlspecialchars($report['admin_notes'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Report History -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Report History</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Report Created</h6>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y g:i a', strtotime($report['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <?php if (!empty($report['updated_at']) && $report['updated_at'] !== $report['created_at']): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Status Updated</h6>
                                    <p class="mb-1">
                                        <span class="badge bg-<?php 
                                            echo $report['status'] === 'resolved' ? 'success' : 
                                                ($report['status'] === 'rejected' ? 'danger' : 'info'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                        </span>
                                    </p>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y g:i a', strtotime($report['updated_at'])); ?>
                                    </small>
                                    <?php if (!empty($report['admin_notes'])): ?>
                                        <div class="mt-2 p-2 bg-light rounded">
                                            <small><?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Report Actions -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="mailto:<?php echo !empty($report['reporter_email']) ? htmlspecialchars($report['reporter_email']) : ''; ?>" 
                               class="btn btn-outline-primary <?php echo empty($report['reporter_email']) || $report['is_anonymous'] ? 'disabled' : ''; ?>">
                                <i class="fas fa-envelope"></i> Email Reporter
                            </a>
                            <?php if (!empty($report['bus_company'])): ?>
                            <a href="#" class="btn btn-outline-secondary">
                                <i class="fas fa-bus"></i> View Bus Owner
                            </a>
                            <?php endif; ?>
                            <a href="#" class="btn btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete this report? This action cannot be undone.');">
                                <i class="fas fa-trash"></i> Delete Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">Report not found.</div>
    <?php endif; ?>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 1.5rem;
    margin: 0 0 0 1rem;
    border-left: 2px solid #dee2e6;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-marker {
    position: absolute;
    left: -1.75rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    margin-top: 0.25rem;
}

.timeline-content {
    padding-left: 1rem;
}

.timeline-content h6 {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}
</style>

<?php include '../includes/footer.php'; ?>
