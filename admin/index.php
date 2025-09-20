<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

// Redirect to dashboard
header('Location: ' . SITE_URL . '/admin/dashboard.php');
exit();
