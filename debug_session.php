<?php
session_start();
header('Content-Type: text/plain');
echo "=== SESSION DATA ===\n";
print_r($_SESSION);

echo "\n\n=== COOKIES ===\n";
print_r($_COOKIE);

echo "\n\n=== SESSION CONFIG ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Save Path: " . session_save_path() . "\n";

// Check if user is logged in
echo "\n\n=== AUTH STATUS ===\n";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "\n";
    echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
    
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'bus_owner') {
        echo "Status: Logged in as Bus Owner\n";
    } else {
        echo "Status: Logged in but not as Bus Owner\n";
    }
} else {
    echo "Status: Not logged in\n";
}
?>
