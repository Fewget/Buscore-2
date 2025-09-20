<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Simulate admin login for testing
$_SESSION['user_id'] = 1; // Assuming 1 is an admin user ID
$_SESSION['is_admin'] = true;

// Get first bus for testing
$bus = $pdo->query("SELECT id, registration_number, show_bus_name, show_company_name FROM buses LIMIT 1")->fetch();

if (!$bus) {
    die('No buses found in the database.');
}

// Debug: Show current session info
// echo '<pre>Session: '; print_r($_SESSION); echo '</pre>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toggle API Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            display: none;
        }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-container">
            <h2>Test Toggle API</h2>
            <p>Testing with Bus: <?php echo htmlspecialchars($bus['registration_number']); ?> (ID: <?php echo $bus['id']; ?>)</p>
            
            <div class="mb-4">
                <h4>Show Bus Name</h4>
                <p>Current: <strong><?php echo $bus['show_bus_name'] ? 'Visible' : 'Hidden'; ?></strong></p>
                <button class="btn btn-primary" onclick="testToggle('show_bus_name', this)">
                    Toggle Bus Name
                </button>
            </div>
            
            <div class="mb-4">
                <h4>Show Company Name</h4>
                <p>Current: <strong><?php echo $bus['show_company_name'] ? 'Visible' : 'Hidden'; ?></strong></p>
                <button class="btn btn-primary" onclick="testToggle('show_company_name', this)">
                    Toggle Company Name
                </button>
            </div>
            
            <div id="result" class="result"></div>
            
            <div class="mt-4">
                <a href="buses.php" class="btn btn-secondary">Back to Buses</a>
            </div>
        </div>
    </div>

    <script>
    async function testToggle(feature, button) {
        const resultDiv = document.getElementById('result');
        const originalText = button.textContent;
        
        // Show loading state
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
        
        try {
            const formData = new FormData();
            formData.append('bus_id', <?php echo $bus['id']; ?>);
            formData.append('feature_name', feature);
            formData.append('is_active', '1'); // Will be toggled on the server
            
            const response = await fetch('update_bus_feature.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showResult('success', `${feature} updated successfully!`);
                // Reload the page to see changes
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(data.message || 'Update failed');
            }
        } catch (error) {
            showResult('error', `Error: ${error.message}`);
            console.error('Error:', error);
        } finally {
            button.disabled = false;
            button.textContent = originalText;
        }
    }
    
    function showResult(type, message) {
        const resultDiv = document.getElementById('result');
        resultDiv.className = `result ${type}`;
        resultDiv.textContent = message;
        resultDiv.style.display = 'block';
    }
    </script>
</body>
</html>
