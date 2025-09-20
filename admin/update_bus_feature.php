<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// For testing - uncomment to simulate admin login
// $_SESSION['user_id'] = 1;
// $_SESSION['is_admin'] = true;

// Set content type to JSON
header('Content-Type: application/json');

// Check if it's an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Forbidden: Not an AJAX request',
        'debug' => [
            'session' => $_SESSION,
            'server' => $_SERVER
        ]
    ]);
    exit;
}

// Check if user is logged in and is admin
$isAdmin = ($_SESSION['admin_logged_in'] ?? 0) == 1 || 
           ($_SESSION['role'] ?? '') === 'admin' || 
           ($_SESSION['is_admin'] ?? 0) == 1;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized: Admin access required',
        'debug' => [
            'session' => $_SESSION,
            'user_id' => $_SESSION['user_id'] ?? null,
            'is_admin' => $isAdmin,
            'admin_logged_in' => $_SESSION['admin_logged_in'] ?? null,
            'role' => $_SESSION['role'] ?? null
        ]
    ]);
    exit;
}

// Get and validate input
$busId = filter_input(INPUT_POST, 'bus_id', FILTER_VALIDATE_INT);
$featureName = filter_input(INPUT_POST, 'feature_name', FILTER_SANITIZE_STRING);
$isActive = filter_input(INPUT_POST, 'is_active', FILTER_VALIDATE_BOOLEAN);

if (!$busId || !$featureName) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Validate feature name
$allowedFeatures = ['show_bus_name', 'show_company_name'];
if (!in_array($featureName, $allowedFeatures, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid feature']);
    exit;
}

try {
    // Update the feature in the database
    $stmt = $pdo->prepare("UPDATE buses SET $featureName = ? WHERE id = ?");
    $result = $stmt->execute([$isActive ? 1 : 0, $busId]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Feature updated successfully',
            'feature' => $featureName,
            'is_active' => $isActive
        ]);
    } else {
        throw new Exception('Failed to update feature');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
