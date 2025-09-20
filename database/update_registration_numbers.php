<?php
require_once '../includes/config.php';

header('Content-Type: text/plain');

try {
    // Get all buses with registration numbers
    $stmt = $pdo->query("SELECT id, registration_number FROM buses WHERE registration_number IS NOT NULL");
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($buses as $bus) {
        $original = $bus['registration_number'];
        $formatted = format_registration_number($original);
        
        // If the format changed, update the database
        if ($original !== $formatted) {
            if (validate_registration_number($formatted)) {
                $updateStmt = $pdo->prepare("UPDATE buses SET registration_number = ? WHERE id = ?");
                $updateStmt->execute([$formatted, $bus['id']]);
                $updated++;
                echo "Updated bus #{$bus['id']}: '$original' -> '$formatted'\n";
            } else {
                $skipped++;
                $errors[] = "Skipped bus #{$bus['id']}: Invalid format '$original'";
            }
        } else {
            $skipped++;
        }
    }
    
    // Create a trigger to automatically format registration numbers on insert/update
    $pdo->exec("
        DROP TRIGGER IF EXISTS before_buses_insert;
    
        CREATE TRIGGER before_buses_insert
        BEFORE INSERT ON buses
        FOR EACH ROW
        BEGIN
            IF NEW.registration_number IS NOT NULL THEN
                SET NEW.registration_number = 
                    UPPER(
                        CONCAT(
                            SUBSTRING(REGEXP_REPLACE(NEW.registration_number, '[^a-zA-Z0-9]', ''), 1, 2),
                            '-',
                            SUBSTRING(REGEXP_REPLACE(NEW.registration_number, '[^0-9]', ''), -4)
                        )
                    );
            END IF;
        END;
        
        DROP TRIGGER IF EXISTS before_buses_update;
        
        CREATE TRIGGER before_buses_update
        BEFORE UPDATE ON buses
        FOR EACH ROW
        BEGIN
            IF NEW.registration_number IS NOT NULL AND (NEW.registration_number != OLD.registration_number) THEN
                SET NEW.registration_number = 
                    UPPER(
                        CONCAT(
                            SUBSTRING(REGEXP_REPLACE(NEW.registration_number, '[^a-zA-Z0-9]', ''), 1, 2),
                            '-',
                            SUBSTRING(REGEXP_REPLACE(NEW.registration_number, '[^0-9]', ''), -4)
                        )
                    );
            END IF;
        END;
    ");
    
    echo "\nMigration complete!\n";
    echo "Updated: $updated registration numbers\n";
    echo "Skipped: $skipped (already formatted or no change needed)\n";
    
    if (!empty($errors)) {
        echo "\nErrors:\n" . implode("\n", $errors) . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (isset($pdo)) {
        $errorInfo = $pdo->errorInfo();
        if (!empty($errorInfo[2])) {
            echo "PDO Error: " . $errorInfo[2] . "\n";
        }
    }
}

echo "\nTo complete the migration, please run the following SQL in your database to add a check constraint:";
echo "\n\nALTER TABLE buses\nADD CONSTRAINT chk_registration_number\nCHECK (registration_number IS NULL OR registration_number REGEXP '^([A-Z]{2}|[0-9]{2})-[0-9]{4}$');";
?>
