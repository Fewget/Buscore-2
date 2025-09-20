<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate input
if (!isset($_POST['bus_id'], $_POST['feature_id'], $_POST['feature_name'], $_POST['is_enabled'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$busId = (int)$_POST['bus_id'];
$featureId = (int)$_POST['feature_id'];
$featureName = $_POST['feature_name'];
$isEnabled = (int)$_POST['is_enabled'];

// Validate feature name
$validFeatures = ['show_bus_name', 'show_company_name'];
if (!in_array($featureName, $validFeatures, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid feature']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Update the buses table
    $columnName = $pdo->quote($featureName);
    $stmt = $pdo->prepare("UPDATE buses SET $columnName = ? WHERE id = ?");
    $stmt->execute([$isEnabled, $busId]);
    
    // 2. If disabling, also update the premium_features table
    if (!$isEnabled) {
        $stmt = $pdo->prepare("
            UPDATE premium_features 
            SET is_active = 0 
            WHERE id = ? 
            AND bus_id = ? 
            AND feature_name = ?
            AND is_active = 1
            AND start_date <= NOW() 
            AND end_date >= NOW()
        
        ");
        $stmt->execute([$featureId, $busId, $featureName]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Feature status updated successfully',
        'feature' => $featureName,
        'enabled' => (bool)$isEnabled
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Error updating feature status: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update feature status',
        'error' => $e->getMessage()
    ]);
}
