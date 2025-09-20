<?php
// Admin specific functions
// Note: checkAdminAccess() is now in the main includes/functions.php file

/**
 * Check if current user is admin
 */
function is_admin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Log admin activity
 */
function log_admin_activity($admin_id, $action, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_activity_log 
            (admin_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $admin_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error logging admin activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get admin name by ID
 */
function get_admin_name($admin_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$admin_id]);
        $user = $stmt->fetch();
        
        return $user ? $user['username'] : 'Unknown';
    } catch (PDOException $e) {
        error_log("Error getting admin name: " . $e->getMessage());
        return 'Error';
    }
}
