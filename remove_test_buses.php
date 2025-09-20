<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // First, show what we're about to delete
    $stmt = $pdo->query("SELECT id, registration_number FROM buses WHERE registration_number IN ('b (n)', 'c (n)')");
    $busesToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($busesToDelete)) {
        die("<p>No test buses found to delete.</p>");
    }
    
    echo "<h3>Removing the following test buses:</h3>";
    echo "<ul>";
    foreach ($busesToDelete as $bus) {
        echo "<li>ID: {$bus['id']} - Registration: {$bus['registration_number']}</li>";
    }
    echo "</ul>";
    
    // Delete the test buses
    $stmt = $pdo->prepare("DELETE FROM buses WHERE registration_number IN ('b (n)', 'c (n)')");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();
    
    // Commit transaction
    $pdo->commit();
    
    echo "<p style='color:green;'>✓ Successfully removed $deletedCount test buses.</p>";
    
    // Show remaining buses (if any)
    $remainingBuses = $pdo->query("SELECT COUNT(*) as count FROM buses")->fetch();
    echo "<p>Total buses remaining in database: " . $remainingBuses['count'] . "</p>";
    
    if ($remainingBuses['count'] > 0) {
        $buses = $pdo->query("SELECT id, registration_number FROM buses LIMIT 10")->fetchAll();
        echo "<h4>Sample of remaining buses (max 10):</h4>";
        echo "<ul>";
        foreach ($buses as $bus) {
            echo "<li>ID: {$bus['id']} - Registration: {$bus['registration_number']}</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><a href='admin/buses.php'>View All Buses</a> | <a href='admin/add-bus.php'>Add New Bus</a></p>
