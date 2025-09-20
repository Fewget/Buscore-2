<?php
require_once 'includes/config.php';

try {
    $stmt = $pdo->query("SHOW CREATE TABLE buses");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<h2>Buses Table Structure</h2>";
        echo "<pre>" . htmlspecialchars($result['Create Table']) . "</pre>";
    } else {
        echo "Could not retrieve table structure.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
