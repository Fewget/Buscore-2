<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if it's an AJAX request and user is admin
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest' ||
    !isset($_SESSION['user_id']) || 
    !isset($_SESSION['role']) || 
    $_SESSION['role'] !== 'admin') {
    
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get and validate input
$busId = filter_input(INPUT_POST, 'bus_id', FILTER_VALIDATE_INT);
$feature = filter_input(INPUT_POST, 'feature', FILTER_SANITIZE_STRING);
$status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_BOOLEAN);

if (!$busId || !$feature) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    // Check if bus exists and has active premium
    $stmt = $pdo->prepare("SELECT is_premium_active FROM buses WHERE id = ?");
    $stmt->execute([$busId]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        throw new Exception('Bus not found');
    }
    
    if (!$bus['is_premium_active']) {
        throw new Exception('Premium features are not active for this bus');
    }
    
    // Update the feature status
    $result = update_premium_feature($busId, $feature, $status, $pdo);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Feature updated successfully',
            'data' => [
                'bus_id' => $busId,
                'feature' => $feature,
                'status' => $status
            ]
        ]);
    } else {
        throw new Exception('Failed to update feature');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
