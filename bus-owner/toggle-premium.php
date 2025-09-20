<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in and is a bus owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bus_owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate input
if (!isset($_POST['bus_id']) || !is_numeric($_POST['bus_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid bus ID']);
    exit();
}

$busId = (int)$_POST['bus_id'];
$userId = (int)$_SESSION['user_id'];

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // First, verify the bus belongs to the current user
    $stmt = $pdo->prepare("SELECT id, is_premium FROM buses WHERE id = ? AND user_id = ?");
    $stmt->execute([$busId, $userId]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        throw new Exception('Bus not found or access denied');
    }
    
    // Toggle the premium status
    $newPremiumStatus = $bus['is_premium'] ? 0 : 1;
    
    $updateStmt = $pdo->prepare("UPDATE buses SET is_premium = ? WHERE id = ?");
    $updateStmt->execute([$newPremiumStatus, $busId]);
    
    // Log the action
    log_activity($userId, 'bus_premium_toggle', json_encode([
        'bus_id' => $busId,
        'new_status' => $newPremiumStatus
    ]));
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'is_premium' => (bool)$newPremiumStatus,
        'message' => 'Premium status updated successfully'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error toggling premium status: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating premium status'
    ]);
}
