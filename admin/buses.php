<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

$message = '';

// Handle bus status and premium features update
if (isset($_POST['update_bus']) && isset($_POST['bus_id'])) {
    try {
        $pdo->beginTransaction();
        
        // Update basic bus info
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
        
        // Update premium features if needed
        if (isset($_POST['premium_features'])) {
            $busId = $_POST['bus_id'];
            $features = $_POST['premium_features'];
            
            // Update each feature
            foreach ($features as $feature => $status) {
                update_premium_feature($busId, $feature, $status, $pdo);
            }
            
            // Update premium status
            update_bus_premium_status($busId, $pdo);
        }
        
        $pdo->commit();
        $message = '<div class="alert alert-success">Bus settings updated successfully!</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
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

// Add columns if they don't exist
$pdo->exec("ALTER TABLE buses ADD COLUMN IF NOT EXISTS show_bus_name TINYINT(1) DEFAULT 1");
$pdo->exec("ALTER TABLE buses ADD COLUMN IF NOT EXISTS show_company_name TINYINT(1) DEFAULT 1");

// Build query
$query = "SELECT b.*, u.username as owner_username, 
          (SELECT (AVG(driver_rating) + AVG(conductor_rating) + AVG(bus_condition_rating)) / 3 
           FROM ratings 
           WHERE bus_id = b.id) as avg_rating,
          (SELECT COUNT(*) FROM reviews WHERE bus_id = b.id) as review_count,
          COALESCE(b.show_bus_name, 1) as show_bus_name,
          COALESCE(b.show_company_name, 1) as show_company_name
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

// Add status column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE buses ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active'");
    $pdo->exec("ALTER TABLE buses ADD COLUMN IF NOT EXISTS show_company_name TINYINT(1) DEFAULT 1");
    $pdo->exec("ALTER TABLE buses ADD COLUMN IF NOT EXISTS show_bus_name TINYINT(1) DEFAULT 1");
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

// Initialize status if not set
if (!isset($bus['status'])) {
    $bus['status'] = 'active'; // Default status
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Manage Buses</h5>
                    <a href="add-bus.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add New Bus
                    </a>
                </div>
            </div>

            <?php echo $message; ?>

            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Search by registration, name, or company..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="maintenance" <?php echo $status === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="owner_id">
                                <option value="">All Owners</option>
                                <?php foreach ($owners as $owner): ?>
                                    <option value="<?php echo $owner['id']; ?>" 
                                        <?php echo $owner_id == $owner['id'] ? 'selected' : ''; ?>>
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
                                        <th>Registration Number</th>
                                        <th>Bus Name</th>
                                        <th>Company</th>
                                        <th>Status</th>
                                        <th class="text-center">Show Bus Name</th>
                                        <th class="text-center">Show Company</th>
                                        <th class="text-center">Premium Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($buses as $bus): 
                                        $rating = $bus['avg_rating'] ? number_format($bus['avg_rating'], 1) : 'N/A';
                                    ?>
                                        <tr>
                                            <td><?php echo $bus['id']; ?></td>
                                            <?php 
                                            // Extract registration number from bus_name if it's in the format "name (XX-####)"
                                            $busName = $bus['bus_name'] ?? '';
                                            $regNumber = $bus['registration_number'] ?? '';
                                            
                                            // If bus_name contains a registration number in parentheses, extract it
                                            if (!empty($busName) && preg_match('/\(([A-Z0-9-]+)\)$/', $busName, $matches)) {
                                                $regNumber = $matches[1];
                                                $busName = trim(preg_replace('/\s*\([^)]*\)$/', '', $busName));
                                            }
                                            ?>
                                            <td class="text-nowrap"><?php echo htmlspecialchars(format_registration_number($regNumber)); ?></td>
                                            <td><?php echo htmlspecialchars($busName); ?></td>
                                            <td><?php echo htmlspecialchars($bus['company_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $bus['status'] === 'active' ? 'success' : ($bus['status'] === 'maintenance' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($bus['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input toggle-feature" type="checkbox" role="switch" 
                                                           id="toggle-bus-name-<?php echo $bus['id']; ?>"
                                                           data-bus-id="<?php echo $bus['id']; ?>"
                                                           data-feature-name="show_bus_name"
                                                           <?php echo ($bus['show_bus_name'] == 1) ? 'checked' : ''; ?>>
                                                </div>
                                                <div class="mt-1">
                                                    <span class="badge <?php echo ($bus['show_bus_name'] == 1) ? 'bg-success' : 'bg-secondary'; ?> status-badge">
                                                        <?php echo ($bus['show_bus_name'] == 1) ? 'Visible' : 'Hidden'; ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input toggle-feature" type="checkbox" role="switch" 
                                                           id="toggle-company-<?php echo $bus['id']; ?>"
                                                           data-bus-id="<?php echo $bus['id']; ?>"
                                                           data-feature-name="show_company_name"
                                                           <?php echo $bus['show_company_name'] ? 'checked' : ''; ?>>
                                                </div>
                                                <div class="small mt-1">
                                                    <?php 
                                                    $isCompanyVisible = $bus['show_company_name'] ?? 0;
                                                    if ($isCompanyVisible) {
                                                        echo '<span class="badge bg-success">Visible</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary">Hidden</span>';
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                $hasPremium = false;
                                                
                                                // Check if bus has any premium features
                                                $stmt = $pdo->prepare("
                                                    SELECT COUNT(*) as count 
                                                    FROM premium_features 
                                                    WHERE bus_id = ? 
                                                    AND is_active = 1 
                                                    AND start_date <= NOW() 
                                                    AND end_date >= NOW()
                                                ");
                                                $stmt->execute([$bus['id']]);
                                                $premiumCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                                
                                                // Get all possible features and their status
                                                $featureConfig = [
                                                    'show_bus_name' => [
                                                        'icon' => 'bus',
                                                        'label' => 'Bus Name',
                                                        'enabled' => $bus['show_bus_name'] ?? 0
                                                    ],
                                                    'show_company_name' => [
                                                        'icon' => 'building',
                                                        'label' => 'Company',
                                                        'enabled' => $bus['show_company_name'] ?? 0
                                                    ]
                                                ];
                                                
                                                // Get active premium features
                                                $features = $pdo->prepare("
                                                    SELECT feature_name, end_date, id as feature_id
                                                    FROM premium_features 
                                                    WHERE bus_id = ? 
                                                    AND is_active = 1 
                                                    AND start_date <= NOW() 
                                                    AND end_date >= NOW()
                                                    ORDER BY end_date ASC
                                                
                                                ");
                                                $features->execute([$bus['id']]);
                                                $activeFeatures = $features->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                $hasPremium = !empty($activeFeatures);
                                                $earliestEndDate = null;
                                                $featureBadges = [];
                                                
                                                // Process active features
                                                foreach ($activeFeatures as $feature) {
                                                    $featureName = $feature['feature_name'];
                                                    $featureId = $feature['feature_id'];
                                                    
                                                    if (isset($featureConfig[$featureName])) {
                                                        $isEnabled = $featureConfig[$featureName]['enabled'];
                                                        $featureBadges[] = [
                                                            'id' => $featureId,
                                                            'name' => $featureName,
                                                            'icon' => $featureConfig[$featureName]['icon'],
                                                            'label' => $featureConfig[$featureName]['label'],
                                                            'enabled' => $isEnabled,
                                                            'end_date' => $feature['end_date']
                                                        ];
                                                        
                                                        if ($earliestEndDate === null || $feature['end_date'] < $earliestEndDate) {
                                                            $earliestEndDate = new DateTime($feature['end_date']);
                                                        }
                                                    }
                                                }
                                                
                                                // Format time remaining
                                                $timeLeft = '';
                                                if ($earliestEndDate) {
                                                    $now = new DateTime();
                                                    $interval = $now->diff($earliestEndDate);
                                                    $daysLeft = $interval->days;
                                                    $hoursLeft = $interval->h;
                                                    
                                                    if ($daysLeft > 0) {
                                                        $timeLeft = $daysLeft . ' day' . ($daysLeft != 1 ? 's' : '') . ' ' . $hoursLeft . ' hour' . ($hoursLeft != 1 ? 's' : '');
                                                    } else {
                                                        $timeLeft = $hoursLeft . ' hour' . ($hoursLeft != 1 ? 's' : '');
                                                    }
                                                }
                                                
                                                // Build tooltip content
                                                $tooltip = '<div class="text-start">';
                                                $tooltip .= '<div class="fw-bold mb-2">Premium Features:</div>';
                                                
                                                foreach ($featureBadges as $feature) {
                                                    $statusClass = $feature['enabled'] ? 'text-success' : 'text-muted';
                                                    $statusText = $feature['enabled'] ? 'On' : 'Off';
                                                    $tooltip .= sprintf('
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <span><i class="fas fa-%s me-1"></i> %s</span>
                                                            <div class="form-check form-switch d-inline-block mb-0">
                                                                <input class="form-check-input toggle-feature" 
                                                                       type="checkbox" 
                                                                       data-bus-id="%s"
                                                                       data-feature-id="%s"
                                                                       data-feature-name="%s"
                                                                       %s>
                                                            </div>
                                                        </div>',
                                                        $feature['icon'],
                                                        $feature['label'],
                                                        $bus['id'],
                                                        $feature['id'],
                                                        $feature['name'],
                                                        $feature['enabled'] ? 'checked' : ''
                                                    );
                                                }
                                                
                                                if ($timeLeft) {
                                                    $tooltip .= '<div class="small text-muted mt-2">Earliest expiry: ' . $timeLeft . ' remaining</div>';
                                                }
                                                
                                                $tooltip .= '</div>';
                                                
                                                // Build display badges
                                                $displayBadges = [];
                                                foreach ($featureBadges as $feature) {
                                                    $badgeClass = $feature['enabled'] ? 'bg-success' : 'bg-secondary';
                                                    $displayBadges[] = sprintf(
                                                        '<span class="badge %s me-1" title="%s: %s">
                                                            <i class="fas fa-%s me-1"></i> %s
                                                        </span>',
                                                        $badgeClass,
                                                        $feature['label'],
                                                        $feature['enabled'] ? 'Enabled' : 'Disabled',
                                                        $feature['icon'],
                                                        $feature['enabled'] ? 'On' : 'Off'
                                                    );
                                                }
                                                
                                                if (empty($displayBadges)) {
                                                    $displayBadges[] = '<span class="badge bg-secondary">None</span>';
                                                }
                                                
                                                $statusBadge = $hasPremium ? 'success' : 'secondary';
                                                $statusText = $hasPremium ? 'Active' : 'Inactive';
                                                ?>
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <span class="badge bg-<?php echo $statusBadge; ?> me-2">
                                                        <i class="fas fa-crown me-1"></i> <?php echo $statusText; ?>
                                                    </span>
                                                    <div class="feature-badges">
                                                        <?php echo implode('', $displayBadges); ?>
                                                    </div>
                                                </div>
                                                <div class="d-none" data-bs-toggle="tooltip" data-bs-html="true" 
                                                     title="<?php echo htmlspecialchars($tooltip); ?>">
                                                </div>
                                            </td>
                                            <td>
                                                <a href="bus-details.php?id=<?php echo $bus['id']; ?>" 
                                                   class="btn btn-sm btn-info" 
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit-bus.php?id=<?php echo $bus['id']; ?>" 
                                                   class="btn btn-sm btn-primary" 
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $bus['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this bus? This will also delete all related reviews and ratings.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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

<!-- Initialize tooltips and handle feature toggles -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#busesTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 25,
        columnDefs: [
            { orderable: false, targets: [5, 6] } // Disable sorting on actions and features columns
        ]
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            html: true,
            boundary: 'window',
            customClass: 'premium-feature-tooltip',
            sanitize: false
        });
    });
    
    // Show tooltip when hovering over the entire premium features cell
    document.querySelectorAll('td.text-center').forEach(function(cell) {
        const tooltipEl = cell.querySelector('[data-bs-toggle="tooltip"]');
        if (tooltipEl) {
            const tooltip = new bootstrap.Tooltip(tooltipEl, {
                trigger: 'manual',
                html: true,
                boundary: 'window',
                customClass: 'premium-feature-tooltip',
                placement: 'top',
                sanitize: false
            });
            
            cell.addEventListener('mouseenter', function() {
                tooltip.show();
            });
            
            cell.addEventListener('mouseleave', function() {
                tooltip.hide();
            });
        }
    });
    
    // Function to update toggle UI
    function updateToggleUI(checkbox, isActive) {
        const statusElement = checkbox.closest('td').querySelector('.status-badge');
        if (statusElement) {
            if (isActive) {
                statusElement.className = 'badge bg-success status-badge';
                statusElement.textContent = 'ON';
            } else {
                statusElement.className = 'badge bg-secondary status-badge';
                statusElement.textContent = 'OFF';
            }
        }
    }

    // Handle feature toggle switches
    document.addEventListener('change', function(e) {
        if (e.target && e.target.matches('.toggle-feature')) {
            const checkbox = e.target;
            const busId = checkbox.dataset.busId;
            const featureName = checkbox.dataset.featureName;
            const isChecked = checkbox.checked;
            
            // Show loading state
            const originalHTML = checkbox.parentNode.innerHTML;
            checkbox.parentNode.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('bus_id', busId);
            formData.append('feature_name', featureName);
            formData.append('is_active', isChecked ? '1' : '0');
            
            // Send AJAX request to update the feature
            fetch('update_bus_feature.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the UI to reflect the change
                    const badge = checkbox.closest('tr').querySelector(`.feature-badge[data-feature="${featureName}"]`);
                    if (badge) {
                        badge.className = `badge ${isChecked ? 'bg-success' : 'bg-secondary'}`;
                        badge.innerHTML = isChecked ? 'On' : 'Off';
                    }
                    
                    // Update the status text next to the toggle
                    const statusElement = checkbox.closest('.form-switch').nextElementSibling;
                    if (statusElement) {
                        const badgeClass = isChecked ? 'bg-success' : 'bg-secondary';
                        const statusText = isChecked ? 'Visible' : 'Hidden';
                        statusElement.innerHTML = `<span class="badge ${badgeClass}">${statusText}</span>`;
                    }
                    
                    // Show success message
                    showAlert('Feature updated successfully', 'success');
                    
                    // Refresh the page after a short delay to ensure UI is in sync
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    throw new Error(data.message || 'Failed to update feature');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                checkbox.checked = !isChecked; // Revert the checkbox
                showAlert(error.message || 'An error occurred while updating the feature', 'danger');
            })
            .finally(() => {
                // Restore the original HTML
                checkbox.parentNode.innerHTML = originalHTML;
                // Make sure the checkbox state is correct
                const input = checkbox.parentNode.querySelector('.form-check-input');
                if (input) {
                    input.checked = !isChecked; // Revert to original state
                }
            });
        }
                
    });
});
    
    // Handle feature toggles
    document.addEventListener('change', function(e) {
        if (e.target && e.target.matches('.toggle-feature')) {
            const checkbox = e.target;
            const busId = checkbox.dataset.busId;
            const feature = checkbox.dataset.feature;
            const status = checkbox.checked;
            
            // Show loading state
            const originalHTML = checkbox.outerHTML;
            checkbox.disabled = true;
            checkbox.outerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';
            
            // Send AJAX request
            fetch('update_bus_features.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `bus_id=${busId}&feature=${feature}&status=${status ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Feature updated successfully', 'success');
                    // Reload the page to reflect changes
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error(data.message || 'Failed to update feature');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert(error.message || 'An error occurred', 'danger');
                // Revert the checkbox
                checkbox.outerHTML = originalHTML;
                const newCheckbox = document.querySelector(`.toggle-feature[data-bus-id="${busId}"][data-feature="${feature}"]`);
                if (newCheckbox) newCheckbox.checked = !status;
            });
        }
    });
    
    // Helper function to show alerts
    function showAlert(message, type) {
        // Remove any existing alerts
        document.querySelectorAll('.alert-dismissible').forEach(alert => alert.remove());
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        alertDiv.style.zIndex = '1060';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(alertDiv);
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 3000);
    }
});
</script>

<style>
/* Style for premium feature badges */
.feature-badges {
    display: inline-flex;
    gap: 0.25rem;
}

.feature-badges .badge {
    font-size: 0.7rem;
    padding: 0.25em 0.5em;
    font-weight: normal;
}

/* Custom tooltip styling */
.premium-feature-tooltip .tooltip-inner {
    max-width: 300px;
    text-align: left;
    padding: 1rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.premium-feature-tooltip .tooltip-arrow::before {
    border-top-color: #fff;
}

/* Make the entire cell clickable for tooltip */
td.text-center {
    cursor: help;
}
</style>
