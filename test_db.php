<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

header('Content-Type: text/plain');

try {
    echo "Testing database connection...\n";
    $pdo->query('SELECT 1');
    echo "✓ Database connection successful\n\n";
    
    // Check bus_reports table
    echo "Checking bus_reports table...\n";
    $stmt = $pdo->query("SHOW CREATE TABLE bus_reports");
    $reportsTable = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reportsTable) {
        echo "✓ bus_reports table exists\n";
        
        // Display table structure
        echo "\nbus_reports table structure:\n";
        echo $reportsTable['Create Table'] . "\n";
        
        // Check for sample data
        echo "\nChecking for reports data...\n";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM bus_reports");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "Found $count reports in the database\n";
        
        if ($count > 0) {
            echo "\nSample report data:\n";
            $reports = $pdo->query("SELECT * FROM bus_reports ORDER BY created_at DESC LIMIT 3")->fetchAll();
            print_r($reports);
        }
    } else {
        echo "✗ bus_reports table does not exist\n";
        
        // Try to create the table
        echo "\nAttempting to create bus_reports table...\n";
        $sql = file_get_contents(__DIR__ . '/database/create_reports_table.sql');
        $pdo->exec($sql);
        echo "✓ Created bus_reports table\n";
    }
    
    // Check buses table
    echo "\nChecking buses table...\n";
    $stmt = $pdo->query("SHOW CREATE TABLE buses");
    $busesTable = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Buses table exists\n";
    
    // Check ratings table
    echo "\nChecking ratings table...\n";
    $stmt = $pdo->query("SHOW CREATE TABLE ratings");
    $ratingsTable = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Ratings table exists\n";
    
    // Check for required columns
    echo "\nChecking for required columns in buses table...\n";
    $requiredColumns = [
        'registration_number', 'route_number', 'route_description',
        'is_approved', 'created_by', 'created_at', 'updated_at'
    ];
    
    $stmt = $pdo->query("DESCRIBE buses");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "✓ All required columns exist in buses table\n";
    } else {
        echo "✗ Missing columns in buses table: " . implode(', ', $missingColumns) . "\n";
    }
    
    // Test insert
    echo "\nTesting insert into buses table...\n";
    $testReg = 'TEST' . time();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO buses (registration_number, is_approved) VALUES (?, 1)");
        $stmt->execute([$testReg]);
        $busId = $pdo->lastInsertId();
        echo "✓ Successfully inserted test bus (ID: $busId, Reg: $testReg)\n";
        
        // Clean up
        $pdo->query("DELETE FROM buses WHERE registration_number = '$testReg'");
        $pdo->commit();
        echo "✓ Test record cleaned up\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "✗ Failed to insert test bus: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    if (isset($pdo)) {
        echo "Error info: " . print_r($pdo->errorInfo(), true) . "\n";
    }
}

echo "\nTest complete.\n";
?>
