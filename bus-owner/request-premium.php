<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in and is a bus owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bus_owner') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get and validate input
    $busId = filter_input(INPUT_POST, 'bus_id', FILTER_VALIDATE_INT);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING) ?? '';
    $userId = $_SESSION['user_id'];
    
    if (!$busId) {
        throw new Exception('Invalid bus ID');
    }
    
    // Verify the bus belongs to the current user
    $stmt = $pdo->prepare("SELECT id FROM buses WHERE id = ? AND user_id = ?");
    $stmt->execute([$busId, $userId]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        throw new Exception('Bus not found or you do not have permission to modify it');
    }
    
    // Check if there's already a pending request for this bus
    $stmt = $pdo->prepare("
        SELECT id FROM premium_requests 
        WHERE bus_id = ? AND status = 'pending' 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$busId]);
    
    if ($stmt->rowCount() > 0) {
        throw new Exception('There is already a pending request for this bus');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Create new premium request
        $stmt = $pdo->prepare("
            INSERT INTO premium_requests 
            (bus_id, requested_by, status, notes, created_at)
            VALUES (?, ?, 'pending', ?, NOW())
        ");
        $stmt->execute([$busId, $userId, $notes]);
        
        // Update bus status to pending
        $stmt = $pdo->prepare("
            UPDATE buses 
            SET premium_status = 'pending',
                premium_requested_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$busId]);
        
        // Log this action
        log_activity($pdo, $userId, "Requested premium status for bus #$busId");
        
        // Commit transaction
        $pdo->commit();
        
        // Send notification to admin (you can implement this function)
        // send_admin_notification("New premium request for bus #$busId");
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Your premium status request has been submitted for review.'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
