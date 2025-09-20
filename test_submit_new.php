<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/test_errors.log');

// Set headers first to ensure no output before them
header('Content-Type: application/json');

// Function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Log the request
error_log("=== New Test Submission ===");
error_log("POST data: " . print_r($_POST, true));

try {
    // Check if required fields are present
    $required = ['registration_number', 'driver_rating', 'conductor_rating', 'bus_condition_rating'];
    $missing = [];
    $data = [];
    
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $missing[] = $field;
        } else {
            $data[$field] = trim($_POST[$field]);
        }
    }
    
    if (!empty($missing)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing));
    }
    
    // All good, return success
    sendJsonResponse([
        'status' => 'success',
        'message' => 'Test submission successful',
        'data' => $data
    ]);
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    
    sendJsonResponse([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => [
            'post_data' => $_POST,
            'missing_fields' => $missing ?? []
        ]
    ], 400);
}

// This should never be reached due to sendJsonResponse calls
error_log("Warning: Reached end of script without sending response");
sendJsonResponse([
    'status' => 'error',
    'message' => 'Unexpected script termination'
], 500);
