<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin access
checkAdminAccess();

// Get first bus for testing
$bus = $pdo->query("SELECT * FROM buses LIMIT 1")->fetch();

if (!$bus) {
    die('No buses found in the database.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toggle Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .toggle-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .status {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
        }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="toggle-container">
            <h2>Toggle Test for Bus: <?php echo htmlspecialchars($bus['registration_number']); ?></h2>
            <hr>
            
            <div class="mb-4">
                <h4>Show Bus Name</h4>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="toggleBusName" 
                           data-bus-id="<?php echo $bus['id']; ?>"
                           data-feature-name="show_bus_name"
                           <?php echo $bus['show_bus_name'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="toggleBusName">
                        Toggle Bus Name Visibility
                    </label>
                </div>
                <div class="status" id="busNameStatus">
                    Current: <?php echo $bus['show_bus_name'] ? 'Visible' : 'Hidden'; ?>
                </div>
            </div>

            <div class="mb-4">
                <h4>Show Company Name</h4>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="toggleCompanyName"
                           data-bus-id="<?php echo $bus['id']; ?>"
                           data-feature-name="show_company_name"
                           <?php echo $bus['show_company_name'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="toggleCompanyName">
                        Toggle Company Name Visibility
                    </label>
                </div>
                <div class="status" id="companyNameStatus">
                    Current: <?php echo $bus['show_company_name'] ? 'Visible' : 'Hidden'; ?>
                </div>
            </div>

            <div class="mt-4">
                <a href="buses.php" class="btn btn-primary">Back to Buses</a>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle toggle changes
        document.querySelectorAll('.form-check-input').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const busId = this.dataset.busId;
                const featureName = this.dataset.featureName;
                const isChecked = this.checked;
                const statusElement = document.getElementById(`${featureName}Status`);
                
                // Show loading state
                const originalHTML = statusElement.innerHTML;
                statusElement.innerHTML = 'Updating...';
                
                // Prepare form data
                const formData = new FormData();
                formData.append('bus_id', busId);
                formData.append('feature_name', featureName);
                formData.append('is_active', isChecked ? '1' : '0');
                
                // Send request
                fetch('update_bus_feature.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusElement.innerHTML = `Current: ${isChecked ? 'Visible' : 'Hidden'}`;
                        statusElement.className = 'status success';
                        setTimeout(() => {
                            statusElement.className = 'status';
                        }, 2000);
                    } else {
                        throw new Error(data.message || 'Update failed');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.checked = !isChecked; // Revert toggle
                    statusElement.innerHTML = `Error: ${error.message}`;
                    statusElement.className = 'status error';
                });
            });
        });
    });
    </script>
</body>
</html>
