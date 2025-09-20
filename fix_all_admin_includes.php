<?php
/**
 * Script to fix include paths in all admin files
 */

// Get all PHP files in the admin directory
$adminFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/admin', RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$changesMade = [];

foreach ($adminFiles as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Fix config.php and functions.php includes
        $content = preg_replace(
            [
                "#require_once\s*['\"].*includes/config\.php['\"]\s*;#",
                "#require_once\s*['\"].*includes/functions\.php['\"]\s*;#",
                "#include\s*['\"].*includes/header\.php['\"]\s*;#",
                "#include\s*['\"].*includes/footer\.php['\"]\s*;#",
                "#include\s*['\"].*includes/sidebar\.php['\"]\s*;#"
            ],
            [
                "require_once __DIR__ . '/../../includes/config.php';",
                "require_once __DIR__ . '/../../includes/functions.php';",
                "include __DIR__ . '/includes/header.php';",
                "include __DIR__ . '/includes/footer.php';",
                "include __DIR__ . '/includes/sidebar.php';"
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
            $changesMade[] = str_replace(__DIR__, '', $filePath);
        }
    }
}

// Output results
echo "<h2>Admin Include Path Fixing Complete</h2>";
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
