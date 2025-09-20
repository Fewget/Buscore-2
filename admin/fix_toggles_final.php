<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

// Function to update toggle switch in database
function updateToggleState($busId, $featureName, $isActive) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE buses SET $featureName = ? WHERE id = ?");
        return $stmt->execute([$isActive ? 1 : 0, $busId]);
    } catch (PDOException $e) {
        error_log("Error updating toggle state: " . $e->getMessage());
        return false;
    }
}

// Handle AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bus_id'], $_POST['feature_name'])) {
        $busId = (int)$_POST['bus_id'];
        $featureName = $_POST['feature_name'];
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1';
        
        // Validate feature name
        if (in_array($featureName, ['show_bus_name', 'show_company_name'], true)) {
            if (updateToggleState($busId, $featureName, $isActive)) {
                $response = ['success' => true, 'message' => 'Toggle updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update toggle'];
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// For non-AJAX requests, show the toggle test page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Toggle Switches</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .toggle-container {
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .toggle-status {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Toggle Switch Test</h1>
        <div class="alert alert-info">
            This page tests the toggle switch functionality. Toggle the switches below to verify they work.
        </div>
        
        <?php
        // Get first 5 buses for testing
        $buses = $pdo->query("SELECT id, registration_number, show_bus_name, show_company_name FROM buses LIMIT 5")->fetchAll();
        
        foreach ($buses as $bus): 
            $busId = $bus['id'];
            $regNumber = htmlspecialchars($bus['registration_number']);
        ?>
        <div class="toggle-container">
            <h4>Bus: <?php echo $regNumber; ?></h4>
            
            <div class="form-check form-switch">
                <input class="form-check-input toggle-test" type="checkbox" 
                       id="toggle-bus-name-<?php echo $busId; ?>"
                       data-bus-id="<?php echo $busId; ?>"
                       data-feature-name="show_bus_name"
                       <?php echo $bus['show_bus_name'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="toggle-bus-name-<?php echo $busId; ?>">
                    Show Bus Name
                </label>
                <div class="toggle-status" id="status-bus-name-<?php echo $busId; ?>">
                    Status: <?php echo $bus['show_bus_name'] ? 'ON' : 'OFF'; ?>
                </div>
            </div>
            
            <div class="form-check form-switch mt-3">
                <input class="form-check-input toggle-test" type="checkbox" 
                       id="toggle-company-<?php echo $busId; ?>"
                       data-bus-id="<?php echo $busId; ?>"
                       data-feature-name="show_company_name"
                       <?php echo $bus['show_company_name'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="toggle-company-<?php echo $busId; ?>">
                    Show Company Name
                </label>
                <div class="toggle-status" id="status-company-<?php echo $busId; ?>">
                    Status: <?php echo $bus['show_company_name'] ? 'ON' : 'OFF'; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="mt-4">
            <a href="buses.php" class="btn btn-primary">Back to Buses</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.toggle-test').change(function() {
            const checkbox = $(this);
            const busId = checkbox.data('bus-id');
            const featureName = checkbox.data('feature-name');
            const isChecked = checkbox.is(':checked');
            const statusElement = $('#status-' + featureName + '-' + busId);
            
            // Show loading state
            statusElement.html('Status: Updating...');
            
            // Send AJAX request
            $.ajax({
                url: 'fix_toggles_final.php',
                type: 'POST',
                data: {
                    bus_id: busId,
                    feature_name: featureName,
                    is_active: isChecked ? 1 : 0
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        statusElement.html('Status: ' + (isChecked ? 'ON' : 'OFF') + ' (Updated)');
                        setTimeout(() => {
                            statusElement.html('Status: ' + (isChecked ? 'ON' : 'OFF'));
                        }, 2000);
                    } else {
                        // Revert checkbox if update failed
                        checkbox.prop('checked', !isChecked);
                        statusElement.html('Error: ' + (response.message || 'Update failed'));
                    }
                },
                error: function() {
                    // Revert checkbox on error
                    checkbox.prop('checked', !isChecked);
                    statusElement.html('Error: Could not connect to server');
                }
            });
        });
    });
    </script>
</body>
</html>
