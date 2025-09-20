<?php
// Test file to check include paths

echo "<h1>Path Test</h1>";

// Test 1: Check current directory
echo "<h3>Current Directory:</h3>";
echo "<pre>" . __DIR__ . "</pre><br>";

// Test 2: Check if config.php exists
$configPath = __DIR__ . '/includes/config.php';
echo "<h3>Checking config.php:</h3>";
if (file_exists($configPath)) {
    echo "<span style='color:green;'>✓ Found config.php at: " . $configPath . "</span><br>";
    
    // Test 3: Try to include config.php
    try {
        require_once $configPath;
        echo "<span style='color:green;'>✓ Successfully included config.php</span><br>";
        
        // Test 4: Check if database connection works
        if (isset($pdo) && $pdo instanceof PDO) {
            echo "<span style='color:green;'>✓ Database connection successful</span><br>";
        } else {
            echo "<span style='color:red;'>✗ Database connection not established</span><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color:red;'>✗ Error including config.php: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span style='color:red;'>✗ config.php not found at: " . $configPath . "</span><br>
";
}

// Test 5: Check admin includes
$adminConfigPath = __DIR__ . '/admin/includes/config.php';
echo "<h3>Checking admin/includes/config.php:</h3>";
if (file_exists($adminConfigPath)) {
    echo "<span style='color:green;'>✓ Found admin config at: " . $adminConfigPath . "</span><br>";
} else {
    echo "<span style='color:red;'>✗ admin/includes/config.php not found at: " . $adminConfigPath . "</span><br>";
}

// Test 6: Check if header.php exists
$headerPath = __DIR__ . '/includes/header.php';
echo "<h3>Checking includes/header.php:</h3>";
if (file_exists($headerPath)) {
    echo "<span style='color:green;'>✓ Found header.php at: " . $headerPath . "</span><br>";
} else {
    echo "<span style='color:red;'>✗ header.php not found at: " . $headerPath . "</span><br>";
}

// Test 7: Check if footer.php exists
$footerPath = __DIR__ . '/includes/footer.php';
echo "<h3>Checking includes/footer.php:</h3>";
if (file_exists($footerPath)) {
    echo "<span style='color:green;'>✓ Found footer.php at: " . $footerPath . "</span><br>";
} else {
    echo "<span style='color:red;'>✗ footer.php not found at: " . $footerPath . "</span><br>";
}

// Test 8: Check if functions.php exists
$functionsPath = __DIR__ . '/includes/functions.php';
echo "<h3>Checking includes/functions.php:</h3>";
if (file_exists($functionsPath)) {
    echo "<span style='color:green;'>✓ Found functions.php at: " . $functionsPath . "</span><br>";
} else {
    echo "<span style='color:red;'>✗ functions.php not found at: " . $functionsPath . "</span><br>";
}

// Test 9: Check current URL and SITE_URL
echo "<h3>Current URL Information:</h3>";
echo "<strong>Current URL:</strong> " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]<br>";
echo "<strong>SITE_URL:</strong> " . (defined('SITE_URL') ? SITE_URL : 'Not defined') . "<br>";

// Test 10: Check if we can include header and footer
echo "<h3>Including Header and Footer:</h3>";
if (file_exists($headerPath) && file_exists($footerPath)) {
    try {
        echo "<div style='border: 2px solid green; padding: 10px; margin: 10px 0;'>";
        echo "<h4>Header Output:</h4>";
        include $headerPath;
        echo "</div>";
        
        echo "<div style='border: 2px solid blue; padding: 10px; margin: 10px 0;'>";
        echo "<h4>Page Content (Test)</h4>";
        echo "<p>This is a test page to check include paths.</p>";
        echo "</div>";
        
        echo "<div style='border: 2px solid green; padding: 10px; margin: 10px 0;'>";
        echo "<h4>Footer Output:</h4>";
        include $footerPath;
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<span style='color:red;'>Error including files: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span style='color:red;'>Cannot include header/footer - files not found</span><br>";
}

// Show server info for debugging
echo "<h3>Server Information:</h3>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "Current Working Directory: " . getcwd() . "\n";

// Show included files
echo "\nIncluded Files:\n";
$includedFiles = get_included_files();
foreach ($includedFiles as $file) {
    echo "- " . $file . "\n";
}
echo "</pre>";
?>
