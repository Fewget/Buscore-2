<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config file
require_once 'includes/config.php';

// Test database connection
try {
    // Check connection
    echo "<h2>Testing Database Connection</h2>";
    echo "<p>Connected to database successfully.</p>";
    
    // Check if tables exist
    $tables = ['buses', 'ratings'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>Table '$table' exists.</p>";
            
            // Show table structure
            echo "<h3>Structure of '$table' table:</h3>";
            $desc = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>";
            print_r($desc);
            echo "</pre>";
            
            // Show sample data if any
            $sample = $pdo->query("SELECT * FROM $table LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($sample)) {
                echo "<h4>Sample data from '$table':</h4>";
                echo "<pre>";
                print_r($sample);
                echo "</pre>";
            } else {
                echo "<p>No data in '$table' table.</p>";
            }
        } else {
            echo "<p style='color: red;'>Table '$table' does NOT exist!</p>";
        }
    }
    
} catch (PDOException $e) {
    die("<p style='color: red;'>Connection failed: " . $e->getMessage() . "</p>");
}

// Test form data processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Form Data Received</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Test required fields
    $required = ['registration_number', 'driver_rating', 'conductor_rating', 'bus_condition_rating'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        echo "<p style='color: orange;'>Missing required fields: " . implode(', ', $missing) . "</p>";
    } else {
        echo "<p style='color: green;'>All required fields are present.</p>";
    }
}
?>

<h2>Test Form Submission</h2>
<form method="post" action="">
    <div class="mb-3">
        <label class="form-label">Registration Number *</label>
        <input type="text" name="registration_number" class="form-control" required value="TEST-123">
    </div>
    
    <div class="mb-3">
        <label class="form-label">Driver Rating *</label>
        <input type="number" name="driver_rating" class="form-control" min="1" max="5" required value="4">
    </div>
    
    <div class="mb-3">
        <label class="form-label">Conductor Rating *</label>
        <input type="number" name="conductor_rating" class="form-control" min="1" max="5" required value="4">
    </div>
    
    <div class="mb-3">
        <label class="form-label">Bus Condition Rating *</label>
        <input type="number" name="bus_condition_rating" class="form-control" min="1" max="5" required value="4">
    </div>
    
    <div class="mb-3">
        <label class="form-label">Comments</label>
        <textarea name="comments" class="form-control">Test comment</textarea>
    </div>
    
    <button type="submit" class="btn btn-primary">Test Submit</button>
</form>

<style>
    body { padding: 20px; font-family: Arial, sans-serif; }
    h2 { margin-top: 30px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>
