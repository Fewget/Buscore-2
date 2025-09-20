<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Simulate form data
$_POST = [
    'registration_number' => 'TEST-123',
    'driver_rating' => '3',
    'conductor_rating' => '4',
    'bus_condition_rating' => '5',
    'comments' => 'Test submission'
];

// Include the debug script
require_once 'debug_bus_submit.php';
?>
