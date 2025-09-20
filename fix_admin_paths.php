<?php
/**
 * Script to fix include paths in admin files
 */

// Files to process
$adminFiles = [
    'activity.php',
    'add_premium_columns.php',
    'buses.php',
    'buses_fixed.php',
    'check_buses_columns.php',
    'check_db_structure.php',
    'check_premium_table.php',
    'create_premium_features_table.php',
    'dashboard.php',
    'fix_buses_table.php',
    'premium-features.php',
    'reviews.php',
    'settings.php',
    'update_database.php',
    'update_feature_status.php',
    'update_premium_features.php',
    'users.php',
    'index.php'
];

$changesMade = [];

foreach ($adminFiles as $file) {
    $filePath = __DIR__ . '/admin/' . $file;
    if (!file_exists($filePath)) {
        echo "File not found: $file<br>";
        continue;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Fix include paths
    $content = str_replace(
        [
            "require_once '../includes/config.php';",
            "require_once '../includes/functions.php';",
            "include '../includes/header.php';",
            "include '../includes/footer.php';",
            "include '../includes/sidebar.php';",
            "include_once '../includes/config.php';",
            "include_once '../includes/functions.php';",
            "include_once '../includes/header.php';",
            "include_once '../includes/footer.php';",
            "include_once '../includes/sidebar.php';"
        ],
        [
            "require_once __DIR__ . '/includes/config.php';",
            "require_once __DIR__ . '/includes/functions.php';",
            "include __DIR__ . '/includes/header.php';",
            "include __DIR__ . '/includes/footer.php';",
            "include __DIR__ . '/includes/sidebar.php';",
            "include_once __DIR__ . '/includes/config.php';",
            "include_once __DIR__ . '/includes/functions.php';",
            "include_once __DIR__ . '/includes/header.php';",
            "include_once __DIR__ . '/includes/footer.php';",
            "include_once __DIR__ . '/includes/sidebar.php';"
        ],
        $content
    );
    
    // Fix login redirects
    $content = str_replace(
        [
            "header('Location: ../login.php');",
            "header('Location: /login.php');"
        ],
        [
            "header('Location: /BS/login.php');",
            "header('Location: /BS/login.php');"
        ],
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $changesMade[] = $file;
    }
}

// Output results
echo "<h2>Path Fixing Complete</h2>";
if (!empty($changesMade)) {
    echo "<p>Updated the following files:</p>";
    echo "<ul>";
    foreach ($changesMade as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No files needed updating.</p>";
}
?>
