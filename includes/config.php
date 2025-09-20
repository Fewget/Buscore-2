<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'buscore_db');

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Show detailed error message in development
    die("<h2>Database Connection Error</h2><p>" . $e->getMessage() . "</p>");
}

// Site configuration
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Buscore');
}
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/BS');
}

// PayHere Configuration
define('PAYHERE_MERCHANT_ID', 'your_merchant_id_here');
define('PAYHERE_MERCHANT_SECRET', 'your_merchant_secret_here');

// Set to true for sandbox/testing, false for production
define('PAYHERE_SANDBOX', true);

// PayHere API endpoints
define('PAYHERE_CHECKOUT_URL', PAYHERE_SANDBOX 
    ? 'https://sandbox.payhere.lk/pay/checkout' 
    : 'https://www.payhere.lk/pay/checkout');

define('PAYHERE_API_BASE_URL', PAYHERE_SANDBOX 
    ? 'https://sandbox.payhere.lk/merchant/v1' 
    : 'https://www.payhere.lk/merchant/v1');

// Set default timezone
date_default_timezone_set('Asia/Colombo');
?>
