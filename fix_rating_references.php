<?php
// Files to update
$files = [
    'search.php' => [
        ["r.driver_rating + r.conductor_rating + r.condition_rating", "r.driver_rating + r.conductor_rating + r.bus_condition_rating"]
    ],
    'reviews.php' => [
        ["r.driver_rating + r.conductor_rating + r.condition_rating", "r.driver_rating + r.conductor_rating + r.bus_condition_rating"],
        ["rt.driver_rating + rt.conductor_rating + rt.condition_rating", "rt.driver_rating + rt.conductor_rating + rt.bus_condition_rating"]
    ],
    'index.php' => [
        ["r.driver_rating + r.conductor_rating + r.condition_rating", "r.driver_rating + r.conductor_rating + r.bus_condition_rating"]
    ],
    'bus.php' => [
        ["r.driver_rating + r.conductor_rating + r.condition_rating", "r.driver_rating + r.conductor_rating + r.bus_condition_rating"],
        ["r.driver_rating + r.conductor_rating + r.condition_rating", "r.driver_rating + r.conductor_rating + r.bus_condition_rating"],
        ["condition_rating", "bus_condition_rating"]
    ],
    'database/setup.php' => [
        ["`condition_rating` TINYINT NOT NULL CHECK (condition_rating BETWEEN 1 AND 5)", "`bus_condition_rating` TINYINT NOT NULL CHECK (bus_condition_rating BETWEEN 1 AND 5)"]
    ],
    'database/add_missing_columns.php' => [
        ["`condition_rating`", "`bus_condition_rating`"]
    ],
    'check_db_structure.php' => [
        ["'condition_rating'", "'bus_condition_rating'"]
    ]
];

// Process each file
foreach ($files as $file => $replacements) {
    if (!file_exists($file)) {
        echo "File not found: $file<br>";
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    foreach ($replacements as $replacement) {
        $content = str_replace($replacement[0], $replacement[1], $content);
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Updated: $file<br>";
    } else {
        echo "No changes needed: $file<br>";
    }
}

echo "<br>All references to 'condition_rating' have been updated to 'bus_condition_rating'.";
?>
