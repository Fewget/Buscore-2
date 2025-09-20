<?php
/**
 * Check if current user has admin access
 * Redirects to login if not logged in, or home if not admin
 */
function check_admin_access() {
    // Alias for backward compatibility
    return checkAdminAccess();
}

/**
 * Check if current user has admin access
 * Redirects to login if not logged in, or home if not admin
 */
function checkAdminAccess() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
    
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a specific URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Check if user is logged in (admin)
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Format date to a readable format
 */
function format_date($date) {
    return date('F j, Y, g:i a', strtotime($date));
}

/**
 * Generate a random string
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Check if request is AJAX
 */
function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Get client IP address
 */
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Check if current user is a bus owner
 * Redirects to login if not logged in, or home if not a bus owner
 */
function check_bus_owner_access() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
    
    // Check if user is a bus owner
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'bus_owner') {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
}

/**
 * Log activity
 */
function log_activity($user_id, $action, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, get_client_ip()]);
        return true;
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get rating stars HTML
 */
function get_rating_stars($rating) {
    $stars = '';
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $fullStars) {
            $stars .= '<i class="fas fa-star"></i>';
        } elseif ($i == $fullStars + 1 && $hasHalfStar) {
            $stars .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $stars .= '<i class="far fa-star"></i>';
        }
    }
    
    return $stars;
}

/**
 * Validate email address
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get current URL
 */
function current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Format a registration number to standard 6-character format (LL-NNNN or NN-NNNN)
 * 
 * @param string $regNumber The registration number to format
 * @return string Formatted registration number in LL-NNNN or NN-NNNN format
 */
function format_registration_number($regNumber) {
    if (empty($regNumber)) {
        return $regNumber;
    }
    
    // Clean the input (remove all non-alphanumeric characters and convert to uppercase)
    $cleanNumber = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $regNumber));
    
    // If the number is empty after cleaning, return the original
    if (empty($cleanNumber)) {
        return $regNumber;
    }
    
    // Take the first 6 characters
    $firstSix = substr($cleanNumber, 0, 6);
    
    // If we have less than 6 characters, return as is
    if (strlen($firstSix) < 6) {
        return $firstSix;
    }
    
    // Format as LL-NNNN or NN-NNNN
    return substr($firstSix, 0, 2) . '-' . substr($firstSix, 2);
}

/**
 * Validate registration number format
 * Returns true if valid (LL-NNNN or NN-NNNN format), false otherwise
 */
function validate_registration_number($reg_number) {
    if (empty($reg_number)) {
        return false;
    }
    
    // Clean the input and convert to uppercase
    $clean = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($reg_number)));
    
    // Check for LL-NNNN format (e.g., NC7746) or NN-NNNN format (e.g., 123456)
    return (bool)preg_match('/^([A-Z]{2}\d{4}|\d{6})$/', $clean);
}

/**
 * Convert a timestamp to a human-readable time ago string
 */
/**
 * Check if premium features are enabled for a bus
 * @param int $busId The ID of the bus
 * @param PDO $pdo Database connection
 * @return bool True if premium features are enabled, false otherwise
 */
function is_premium_enabled($busId, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT is_premium FROM buses WHERE id = ?");
        $stmt->execute([$busId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['is_premium'] == 1;
    } catch (PDOException $e) {
        error_log("Error checking premium status: " . $e->getMessage());
        return false;
    }
}

/**
 * Convert a timestamp to a human-readable time ago string
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $days = $diff->d;
    $weeks = floor($days / 7);
    $days = $days % 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'w' => 'week',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    // Handle weeks separately since it's not a direct property of DateInterval
    if ($weeks > 0) {
        $string['w'] = $weeks . ' week' . ($weeks > 1 ? 's' : '');
    } else {
        unset($string['w']);
    }
    
    // Handle days
    if ($days > 0) {
        $string['d'] = $days . ' day' . ($days > 1 ? 's' : '');
    } else {
        unset($string['d']);
    }
    
    // Handle other time units
    foreach ($string as $k => &$v) {
        if (in_array($k, ['w', 'd'])) continue; // Skip weeks and days as we've already handled them
        if ($diff->$k > 0) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
// Check if a premium feature is enabled for a bus
function is_premium_feature_enabled($busId, $feature, $pdo) {
    $stmt = $pdo->prepare("SELECT premium_features, is_premium_active FROM buses WHERE id = ?");
    $stmt->execute([$busId]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus || !$bus['is_premium_active']) {
        return false;
    }
    
    $features = json_decode($bus['premium_features'], true);
    return isset($features[$feature]) && $features[$feature] === true;
}

// Update premium feature status for a bus
function update_premium_feature($busId, $feature, $status, $pdo) {
    // Get current features
    $stmt = $pdo->prepare("SELECT premium_features FROM buses WHERE id = ?");
    $stmt->execute([$busId]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        return false;
    }
    
    $features = json_decode($bus['premium_features'] ?? '{}', true);
    $features[$feature] = (bool)$status;
    
    // Update features
    $stmt = $pdo->prepare("UPDATE buses SET premium_features = ? WHERE id = ?");
    return $stmt->execute([json_encode($features), $busId]);
}

// Get all premium features for a bus
function get_bus_premium_features($busId, $pdo) {
    $stmt = $pdo->prepare("SELECT premium_features, is_premium_active, premium_expires_at FROM buses WHERE id = ?");
    $stmt->execute([$busId]);
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        return [];
    }
    
    $features = json_decode($bus['premium_features'] ?? '{}', true);
    $features['is_active'] = (bool)$bus['is_premium_active'];
    $features['expires_at'] = $bus['premium_expires_at'];
    
    return $features;
}

// Check if bus has active premium subscription
function has_active_premium($busId, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bus_subscriptions 
                          WHERE bus_id = ? AND is_active = 1 
                          AND (end_date IS NULL OR end_date > NOW())");
    $stmt->execute([$busId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['count'] > 0;
}

// Update premium status for a bus
function update_bus_premium_status($busId, $pdo) {
    $hasActivePremium = has_active_premium($busId, $pdo);
    
    if ($hasActivePremium) {
        // Get the latest subscription end date
        $stmt = $pdo->prepare("SELECT MAX(end_date) as latest_end_date 
                              FROM bus_subscriptions 
                              WHERE bus_id = ? AND is_active = 1");
        $stmt->execute([$busId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $expiresAt = $result['latest_end_date'];
        
        // Enable premium
        $stmt = $pdo->prepare("UPDATE buses 
                              SET is_premium_active = 1, 
                                  premium_expires_at = ? 
                              WHERE id = ?");
        $stmt->execute([$expiresAt, $busId]);
    } else {
        // Disable premium
        $stmt = $pdo->prepare("UPDATE buses 
                              SET is_premium_active = 0, 
                                  premium_expires_at = NULL 
                              WHERE id = ?");
        $stmt->execute([$busId]);
    }
    
    return $hasActivePremium;
}

/**
 * Check if current user is an admin
 * @return bool True if user is an admin, false otherwise
 */
function isAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if current user is a bus owner
 * @return bool True if user is a bus owner, false otherwise
 */
function isBusOwner() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['role']) && $_SESSION['role'] === 'bus_owner';
}
?>
