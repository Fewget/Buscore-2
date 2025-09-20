<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // Check if columns exist
    $columns = [
        'last_engine_oil_change',
        'last_engine_oil_mileage',
        'last_brake_change',
        'last_brake_mileage'
    ];
    
    $pdo->beginTransaction();
    
    foreach ($columns as $column) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM information_schema.COLUMNS 
            WHERE 
                TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'buses' 
                AND COLUMN_NAME = ?
        ");
        $stmt->execute([DB_NAME, $column]);
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            $type = (strpos($column, 'mileage') !== false) ? 'INT NULL' : 'DATE NULL';
            $sql = "ALTER TABLE `buses` ADD COLUMN `$column` $type";
            $pdo->exec($sql);
            echo "<p>Added column: $column</p>";
        } else {
            echo "<p>Column '$column' already exists.</p>";
        }
    }
    
    $pdo->commit();
    echo "<p style='color:green;font-weight:bold;'>Service columns added successfully! <a href='../bus-owner/add-service-record.php?bus_id=30'>Try again</a></p>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    die("<h2>Database Error</h2><div style='color:red;'><p>" . $e->getMessage() . "</p><p>Error Code: " . $e->getCode() . "</p></div>");
}
?>
