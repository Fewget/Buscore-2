<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Ensure the reports table exists
$createTableSQL = [
    "CREATE TABLE IF NOT EXISTS `bus_reports` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `bus_number` varchar(20) NOT NULL,
        `issue_types` text NOT NULL,
        `description` text DEFAULT NULL,
        `reporter_name` varchar(100) DEFAULT NULL,
        `reporter_email` varchar(255) DEFAULT NULL,
        `is_anonymous` tinyint(1) DEFAULT 0,
        `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `bus_number` (`bus_number`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

$databaseError = '';
try {
    // Check if database exists, if not create it
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Select the database
    $pdo->exec("USE " . DB_NAME);
    
    // Create table
    foreach ($createTableSQL as $sql) {
        $pdo->exec($sql);
    }
} catch (PDOException $e) {
    $databaseError = $e->getMessage();
    error_log("Database error: " . $databaseError);
}

$page_title = 'Report a Bus Issue';
$success = false;
$error = '';

// Show database error if any
if (!empty($databaseError) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = 'There was an issue setting up the database. Please contact support.';
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus_number = trim($_POST['bus_number'] ?? '');
    $issue_types = $_POST['issue_types'] ?? [];
    $other_issue = trim($_POST['other_issue'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $reporter_name = trim($_POST['reporter_name'] ?? '');
    $reporter_email = trim($_POST['reporter_email'] ?? '');
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;
    
    // If 'Other' is selected, add the custom issue to the issue types
    if (in_array('Other', $issue_types) && !empty($other_issue)) {
        // Remove 'Other' from the array and add the custom issue
        $issue_types = array_diff($issue_types, ['Other']);
        $issue_types[] = 'Other: ' . $other_issue;
    }
    
    // Format bus number (add hyphen if missing)
    $formatted_bus_number = preg_replace(['/^(\w{2})(\d{4})$/i', '/^(\d{2,3})(\d{3,4})$/'], ['$1-$2', '$1-$2'], $bus_number);
    
    // Basic validation
    if (empty($bus_number)) {
        $error = 'Please enter the bus number';
    } elseif (!preg_match('/^([A-Za-z]{2}-?\d{4}|\d{2,3}-?\d{3,4})$/i', $bus_number)) {
        $error = 'Please enter a valid registration number (e.g., NA1234, 62-1234, WP-5678, 123-4567)';
    } elseif (empty($issue_types) || (in_array('Other', $issue_types) && empty($other_issue))) {
        $error = 'Please select at least one issue type or specify your own issue';
    } elseif (empty($reporter_name)) {
        $error = 'Please enter your name';
    } elseif (!$anonymous && !filter_var($reporter_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Save to database
        try {
            $stmt = $pdo->prepare("INSERT INTO bus_reports 
                (bus_number, issue_types, description, reporter_name, reporter_email, is_anonymous, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())");
                
            $stmt->execute([
                $formatted_bus_number,
                json_encode($issue_types),
                $description,
                $anonymous ? '' : $reporter_name,
                $anonymous ? '' : $reporter_email,
                $anonymous
            ]);
            
            $success = true;
            
        } catch (PDOException $e) {
            error_log("Error saving report: " . $e->getMessage());
            $error = 'An error occurred while saving your report. Please try again. ';
            $error .= 'If the problem persists, please contact support.';
        }
    }
}

include 'includes/header.php';
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="mb-4">Report a Bus Issue</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h4>Thank You!</h4>
                    <p>Your report has been submitted successfully. We appreciate your feedback.</p>
                    <a href="index.php" class="btn btn-primary">Back to Home</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="post" class="needs-validation" novalidate>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Your Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="reporter_name" class="form-label">Your Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="reporter_name" name="reporter_name" required>
                                    <div class="invalid-feedback">Please enter your name</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="reporter_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="reporter_email" name="reporter_email" required>
                                    <div class="invalid-feedback">Please enter a valid email address</div>
                                </div>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="anonymous" id="anonymous">
                                <label class="form-check-label" for="anonymous">
                                    Submit anonymously (your name and email will not be stored)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Bus Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="bus_number" class="form-label">Bus Registration Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="bus_number" name="bus_number" 
                                       pattern="^([A-Za-z]{2}-?\d{4}|\d{2,3}-?\d{3,4})$"
                                       placeholder="e.g., NA-1234 or 62-1234" required>
                                <div class="invalid-feedback">Please enter a valid registration number (e.g., NA1234, 62-1234, WP-5678, 123-4567)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Issue Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label d-block">Select Issue Type(s) <span class="text-danger">*</span></label>
                                
                                <h6>Bus Condition & Service</h6>
                                <div class="form-check">
                                    <input class="form-check-input issue-type" type="checkbox" name="issue_types[]" value="poor_maintenance" id="issue_poor_maintenance">
                                    <label class="form-check-label" for="issue_poor_maintenance">
                                        Poor Maintenance (broken seats, doors, windows, suspension)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input issue-type" type="checkbox" name="issue_types[]" value="lack_of_cleanliness" id="issue_lack_of_cleanliness">
                                    <label class="form-check-label" for="issue_lack_of_cleanliness">
                                        Lack of Cleanliness (dirty interiors, bad odors)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input issue-type" type="checkbox" name="issue_types[]" value="overcrowding" id="issue_overcrowding">
                                    <label class="form-check-label" for="issue_overcrowding">
                                        Overcrowding (beyond capacity)
                                    </label>
                                </div>
                                
                                <h6 class="mt-3">Conductor Issues</h6>
                                <div class="form-check">
                                    <input class="form-check-input issue-type" type="checkbox" name="issue_types[]" value="rude_conductor" id="issue_rude_conductor">
                                    <label class="form-check-label" for="issue_rude_conductor">
                                        Rude or Aggressive Behavior
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input issue-type" type="checkbox" name="issue_types[]" value="fare_disputes" id="issue_fare_disputes">
                                    <label class="form-check-label" for="issue_fare_disputes">
                                        Fare Disputes (overcharging, shortchanging)
                                    </label>
                                </div>
                                
                                <h6 class="mt-3">Driver Issues</h6>
                                <div class="form-check">
                                    <input class="form-check-input issue-type" type="checkbox" name="issue_types[]" value="reckless_driving" id="issue_reckless_driving">
                                    <label class="form-check-label" for="issue_reckless_driving">
                                        Reckless Driving (speeding, harsh braking)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input issue-type" type="checkbox" name="issue_types[]" value="dangerous_maneuvers" id="issue_dangerous_maneuvers">
                                    <label class="form-check-label" for="issue_dangerous_maneuvers">
                                        Dangerous Maneuvers (unsafe overtaking)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input issue-type" type="checkbox" name="issue_types[]" value="distracted_driving" id="issue_distracted_driving">
                                    <label class="form-check-label" for="issue_distracted_driving">
                                        Distracted Driving (using phone)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input issue-type" type="checkbox" name="issue_types[]" value="loud_noise" id="issue_loud_noise">
                                    <label class="form-check-label" for="issue_loud_noise">
                                        Excessive Noise (loud music/announcements)
                                    </label>
                                </div>
                                
                                <div class="form-check mt-2">
                                    <input class="form-check-input issue-type" type="checkbox" name="issue_types[]" value="Other" id="issue_other">
                                    <label class="form-check-label fw-bold" for="issue_other">Other (please specify)</label>
                                </div>
                                <div class="mt-2 mb-3" id="otherIssueContainer" style="display: none;">
                                    <label for="other_issue" class="form-label">Please specify the issue:</label>
                                    <input type="text" class="form-control" id="other_issue" name="other_issue" placeholder="Enter the issue you're experiencing">
                                </div>
                                <div class="form-text">Check all that apply. You can also add a custom issue using the 'Other' option.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Additional Details (Optional)</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <script>
                            // Show/hide other issue input based on checkbox
                            document.addEventListener('DOMContentLoaded', function() {
                                const otherCheckbox = document.getElementById('issue_other');
                                const otherContainer = document.getElementById('otherIssueContainer');
                                const otherInput = document.getElementById('other_issue');
                                
                                otherCheckbox.addEventListener('change', function() {
                                    if (this.checked) {
                                        otherContainer.style.display = 'block';
                                        otherInput.required = true;
                                    } else {
                                        otherContainer.style.display = 'none';
                                        otherInput.required = false;
                                        otherInput.value = '';
                                    }
                                });
                                
                                // Handle form submission to ensure at least one checkbox is checked
                                document.querySelector('form').addEventListener('submit', function(e) {
                                    const checkboxes = document.querySelectorAll('.issue-type:checked');
                                    const otherChecked = document.getElementById('issue_other').checked;
                                    const otherFilled = document.getElementById('other_issue').value.trim() !== '';
                                    
                                    if (checkboxes.length === 0 || (otherChecked && !otherFilled)) {
                                        e.preventDefault();
                                        alert('Please select at least one issue type or specify your own issue.');
                                    }
                                });
                            });
                            </script>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Submit Report</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
// Form validation
(function () {
    'use strict'
    
    var forms = document.querySelectorAll('.needs-validation')
    
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            // Check if at least one checkbox is checked
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            let atLeastOneChecked = false;
            
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    atLeastOneChecked = true;
                }
            });
            
            if (!atLeastOneChecked) {
                event.preventDefault();
                event.stopPropagation();
                document.getElementById('issueTypesError').style.display = 'block';
            } else {
                document.getElementById('issueTypesError').style.display = 'none';
            }
            
            if (!form.checkValidity() || !atLeastOneChecked) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include 'includes/footer.php'; ?>
