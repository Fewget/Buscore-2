<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Set headers first to ensure no output before them
header('Content-Type: application/json');

// Function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Handle errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $errorData = [
        'status' => 'error',
        'message' => 'A server error occurred',
        'error' => [
            'code' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]
    ];
    
    error_log("PHP Error: {$errstr} in {$errfile} on line {$errline}");
    sendJsonResponse($errorData, 500);
});

// Handle exceptions
set_exception_handler(function($e) {
    $errorData = [
        'status' => 'error',
        'message' => 'An unexpected error occurred',
        'error' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ];
    
    error_log("Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    sendJsonResponse($errorData, 500);
});

try {
    require_once 'includes/config.php';

    // Log the start of the request
    error_log("=== New Form Submission ===");
    error_log("POST data: " . print_r($_POST, true));

    // Ensure we have POST data
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Invalid request method. Please use POST.'
        ], 405);
    }

    // Check required fields
    $required = ['registration_number', 'driver_rating', 'conductor_rating', 'bus_condition_rating'];
    $missing = [];
    $data = [];

    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $missing[] = $field;
        } else {
            $data[$field] = trim($_POST[$field]);
        }
    }

    if (!empty($missing)) {
        $missingFields = array_map(function($field) {
            return ucfirst(str_replace('_', ' ', $field));
        }, $missing);
        
        $message = 'Please fill in all required fields: ' . implode(', ', $missingFields);
        
        error_log("Missing required fields: " . implode(', ', $missing));
        
        sendJsonResponse([
            'status' => 'error',
            'message' => $message,
            'missing' => $missing,
            'debug' => [
                'post_data' => $_POST,
                'missing_fields' => $missing
            ]
        ], 400);
    }

    // Process the form
    $response = [];

    try {
        // Check if bus exists
        $stmt = $pdo->prepare("SELECT id FROM buses WHERE registration_number = ?");
        $stmt->execute([$data['registration_number']]);
        
        if ($stmt->rowCount() > 0) {
            $bus = $stmt->fetch(PDO::FETCH_ASSOC);
            $busId = $bus['id'];
            $response['bus_exists'] = true;
        } else {
            $pdo->beginTransaction();
            
            try {
                // Insert new bus
                $stmt = $pdo->prepare("INSERT INTO buses (
                    registration_number,
                    route_number,
                    route_description,
                    is_approved,
                    created_by,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                
                $stmt->execute([
                    $data['registration_number'],
                    $_POST['route_number'] ?? null,
                    $_POST['route_description'] ?? null,
                    isset($_SESSION['user_id']) ? 1 : 0, // Auto-approve if logged in
                    $_SESSION['user_id'] ?? null
                ]);
                
                $busId = $pdo->lastInsertId();
                $pdo->commit();
                $response['bus_created'] = true;
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
        
        // Add rating
        $stmt = $pdo->prepare("INSERT INTO ratings (bus_id, user_id, driver_rating, conductor_rating, bus_condition_rating, comment) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $busId,
            $_SESSION['user_id'] ?? null,
            (int)$data['driver_rating'],
            (int)$data['conductor_rating'],
            (int)$data['bus_condition_rating'],
            $_POST['comments'] ?? null
        ]);
        
        error_log("Inserted rating with ID: " . $pdo->lastInsertId());
        $response['status'] = 'success';
        $response['message'] = 'Bus and rating added successfully';
        sendJsonResponse($response);
        
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        error_log("Error Info: " . print_r(isset($pdo) ? $pdo->errorInfo() : 'No PDO connection', true));
        
        throw $e; // Let the exception handler deal with it
    }
    
} catch (Exception $e) {
    // This will be caught by the exception handler
    throw $e;
}
