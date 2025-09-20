<?php
/**
 * Admin Configuration File
 * 
 * This file contains admin-specific configurations and includes
 * all necessary files for the admin panel to function properly.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the base path for includes
$basePath = dirname(dirname(__DIR__));

try {
    // Include main config file
    $mainConfig = $basePath . '/includes/config.php';
    if (!file_exists($mainConfig)) {
        throw new Exception('Main configuration file not found at: ' . $mainConfig);
    }
    require_once $mainConfig;

    // Include database connection
    $dbConnect = $basePath . '/includes/db_connect.php';
    if (!file_exists($dbConnect)) {
        throw new Exception('Database connection file not found at: ' . $dbConnect);
    }
    require_once $dbConnect;

    // Include common functions
    $functions = $basePath . '/includes/functions.php';
    if (!file_exists($functions)) {
        throw new Exception('Functions file not found at: ' . $functions);
    }
    require_once $functions;

} catch (Exception $e) {
    // Log the error
    error_log('Admin config error: ' . $e->getMessage());
    
    // Display a user-friendly error message
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
    }
    die('An error occurred while loading the admin panel. Please try again later.');
}

// Admin specific configurations
if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', 'admin@example.com');
}

// Set error reporting based on environment
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (!defined('ITEMS_PER_PAGE')) {
    define('ITEMS_PER_PAGE', 10);
}

// Set timezone if not already set
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Kolkata');
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include admin functions
require_once __DIR__ . '/functions.php';

// Check admin access for all admin pages
checkAdminAccess();

// Include database connection
require_once __DIR__ . '/../../includes/db_connect.php';
