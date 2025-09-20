<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in and is a bus owner
if (!isLoggedIn() || !isBusOwner()) {
    header('Location: /BS/login.php');
    exit();
}

$pageTitle = 'View Report';
$error = '';
$report = null;

// Get report ID
$reportId = $_GET['id'] ?? 0;
$ownerId = $_SESSION['user_id'];

if (!$reportId) {
    header('Location: reports.php');
    exit();
}

try {
    // Get report details only if it belongs to the bus owner
    $stmt = $pdo->prepare("
        SELECT r.*, b.bus_name, b.registration_number
        FROM bus_reports r
        JOIN buses b ON r.bus_number = b.registration_number
        WHERE r.id = ? AND b.owner_id = ?
    ");
    $stmt->execute([$reportId, $ownerId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        throw new Exception('Report not found or access denied');
    }
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Include header
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Report #<?php echo $reportId; ?></h2>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Reports
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($report): ?>
        <div class="row">
            <div class="col-md-8">
                <!-- Report Details Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Report Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Reported On:</div>
                            <div class="col-md-8">
                                <?php echo date('F j, Y, g:i a', strtotime($report['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Bus Number:</div>
                            <div class="col-md-8">
                                <?php echo htmlspecialchars($report['bus_number']); ?>
                                <?php if (!empty($report['bus_name'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($report['bus_name']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Issue Types:</div>
                            <div class="col-md-8">
                                <?php 
                                $issues = explode(',', $report['issue_types']);
                                foreach ($issues as $issue): 
                                ?>
                                    <span class="badge bg-secondary me-1 mb-1">
                                        <?php echo htmlspecialchars(trim($issue)); ?>
                                    </span>
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
                                
                                <?php if (!empty($report['admin_notes'])): ?>
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <h6>Admin Notes:</h6>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($report['admin_notes'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Add Response Form -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Add Response</h5>
                    </div>
                    <div class="card-body">
                        <form id="responseForm">
                            <div class="mb-3">
                                <label for="response" class="form-label">Your Response</label>
                                <textarea class="form-control" id="response" name="response" rows="4" 
                                          placeholder="Type your response here..." required></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="mark_resolved" name="mark_resolved"
                                       <?php echo $report['status'] === 'resolved' ? 'disabled' : ''; ?>>
                                <label class="form-check-label" for="mark_resolved">
                                    Mark as resolved
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Response
                            </button>
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
                                <div class="timeline-marker bg-<?php 
                                    echo $report['status'] === 'resolved' ? 'success' : 
                                        ($report['status'] === 'rejected' ? 'danger' : 'info'); 
                                ?>"></div>
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
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Placeholder for responses -->
                            <div id="responsesContainer">
                                <!-- Responses will be loaded here via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($report['status'] !== 'resolved'): ?>
                            <button type="button" class="btn btn-success" id="markResolvedBtn">
                                <i class="fas fa-check"></i> Mark as Resolved
                            </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline-secondary" id="printReportBtn">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hidden form for status updates -->
        <form id="statusForm" method="post" style="display: none;">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="status" value="resolved">
        </form>
        
    <?php else: ?>
        <div class="alert alert-warning">Report not found or you don't have permission to view it.</div>
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
    margin-bottom: 0.25remn
}

.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
    margin-bottom: 0.25rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle mark as resolved button
    const markResolvedBtn = document.getElementById('markResolvedBtn');
    if (markResolvedBtn) {
        markResolvedBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to mark this report as resolved?')) {
                document.getElementById('statusForm').submit();
            }
        });
    }
    
    // Handle print button
    const printReportBtn = document.getElementById('printReportBtn');
    if (printReportBtn) {
        printReportBtn.addEventListener('click', function() {
            window.print();
        });
    }
    
    // Handle response form submission
    const responseForm = document.getElementById('responseForm');
    if (responseForm) {
        responseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Here you would typically send the response to the server
            const responseText = document.getElementById('response').value;
            const markAsResolved = document.getElementById('mark_resolved').checked;
            
            // Simulate AJAX submission
            setTimeout(() => {
                // Add the new response to the timeline
                const responsesContainer = document.getElementById('responsesContainer');
                const responseId = 'response-' + Date.now();
                
                const responseHtml = `
                    <div class="timeline-item" id="${responseId}">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6 class="mb-0">Your Response</h6>
                            <p class="mb-1">${responseText}</p>
                            <small class="text-muted">Just now</small>
                        </div>
                    </div>
                `;
                
                responsesContainer.insertAdjacentHTML('afterbegin', responseHtml);
                
                // Clear the form
                responseForm.reset();
                
                // Show success message
                alert('Your response has been submitted.');
                
                // If marked as resolved, reload the page to show updated status
                if (markAsResolved) {
                    location.reload();
                }
                
                // Scroll to the new response
                document.getElementById(responseId).scrollIntoView({ behavior: 'smooth' });
                
            }, 500);
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
