<?php
/**
 * Check if a premium feature is active for a bus
 * 
 * @param PDO $pdo Database connection
 * @param int $busId Bus ID
 * @param string $featureName Feature name (show_company_name or show_bus_name)
 * @return bool True if the feature is active, false otherwise
 */
function isPremiumFeatureActive($pdo, $busId, $featureName) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM premium_features 
            WHERE bus_id = ? 
            AND feature_name = ? 
            AND is_active = 1 
            AND start_date <= NOW() 
            AND end_date >= NOW()
        ");
        $stmt->execute([$busId, $featureName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Error checking premium feature: " . $e->getMessage());
        return false;
    }
}

/**
 * Get the premium feature status for a bus
 * 
 * @param PDO $pdo Database connection
 * @param int $busId Bus ID
 * @param string $featureName Feature name (show_company_name or show_bus_name)
 * @return array Feature details or empty array if not found
 */
function getPremiumFeatureStatus($pdo, $busId, $featureName) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM premium_features 
            WHERE bus_id = ? 
            AND feature_name = ? 
            AND is_active = 1 
            AND start_date <= NOW() 
            AND end_date >= NOW()
            ORDER BY end_date DESC
            LIMIT 1
        ");
        $stmt->execute([$busId, $featureName]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("Error getting premium feature status: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if company name should be shown for a bus
 * 
 * @param PDO $pdo Database connection
 * @param int $busId Bus ID
 * @return bool True if company name should be shown, false otherwise
 */
function shouldShowCompanyName($pdo, $busId) {
    return isPremiumFeatureActive($pdo, $busId, 'show_company_name');
}

/**
 * Check if bus name should be shown for a bus
 * 
 * @param PDO $pdo Database connection
 * @param int $busId Bus ID
 * @return bool True if bus name should be shown, false otherwise
 */
function shouldShowBusName($pdo, $busId) {
    return isPremiumFeatureActive($pdo, $busId, 'show_bus_name');
}

/**
 * Get the remaining time for a premium feature
 * 
 * @param string $endDate End date in MySQL datetime format
 * @return string Formatted remaining time (e.g., "2 days, 5 hours left" or "Expired")
 */
function getRemainingTime($endDate) {
    $end = new DateTime($endDate);
    $now = new DateTime();
    
    if ($end <= $now) {
        return 'Expired';
    }
    
    $interval = $now->diff($end);
    $parts = [];
    
    if ($interval->d > 0) {
        $parts[] = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
    }
    
    if ($interval->h > 0 || !empty($parts)) {
        $parts[] = $interval->h . ' hour' . ($interval->h != 1 ? 's' : '');
    }
    
    return implode(', ', $parts) . ' left';
}
