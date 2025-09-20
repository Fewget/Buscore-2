<?php
require_once '../includes/config.php';

header('Content-Type: text/plain');
echo "Starting premium management setup...\n\n";

try {
    // Add columns to buses table if they don't exist
    $pdo->exec("
        ALTER TABLE buses 
        ADD COLUMN IF NOT EXISTS premium_status ENUM('active', 'expired', 'pending', 'inactive') DEFAULT 'inactive',
        ADD COLUMN IF NOT EXISTS premium_expiry DATETIME DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS premium_requested_at DATETIME DEFAULT NULL
    ");
    
    echo "✓ Added premium columns to buses table\n";
    
    // Create premium_requests table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS premium_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bus_id INT NOT NULL,
            requested_by INT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            expiry_date DATETIME DEFAULT NULL,
            notes TEXT,
            processed_by INT DEFAULT NULL,
            processed_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
            FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    echo "✓ Created premium_requests table\n";
    
    // Create activity_logs table if it doesn't exist (for logging actions)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    echo "✓ Created activity_logs table\n";
    
    // Create a stored procedure for approving premium requests
    $pdo->exec("
        DROP PROCEDURE IF EXISTS approve_premium_request;
    ");
    
    $pdo->exec("
        CREATE PROCEDURE approve_premium_request(
            IN p_request_id INT,
            IN p_processed_by INT,
            IN p_expiry_date DATETIME,
            IN p_notes TEXT
        )
        BEGIN
            DECLARE v_bus_id INT;
            
            -- Get the bus ID from the request
            SELECT bus_id INTO v_bus_id FROM premium_requests WHERE id = p_request_id;
            
            -- Update the request status
            UPDATE premium_requests 
            SET status = 'approved',
                expiry_date = p_expiry_date,
                processed_by = p_processed_by,
                processed_at = NOW(),
                notes = CONCAT_WS('\n', IFNULL(notes, ''), p_notes)
            WHERE id = p_request_id;
            
            -- Update the bus premium status
            UPDATE buses 
            SET premium_status = 'active',
                premium_expiry = p_expiry_date,
                is_premium = 1
            WHERE id = v_bus_id;
            
            -- Log this action
            INSERT INTO activity_logs (user_id, action, details)
            VALUES (p_processed_by, 'premium_approved', 
                   CONCAT('Approved premium request #', p_request_id, ' for bus #', v_bus_id));
        END;
    ");
    
    echo "✓ Created approve_premium_request stored procedure\n";
    
    // Create a stored procedure for rejecting premium requests
    $pdo->exec("
        DROP PROCEDURE IF EXISTS reject_premium_request;
    ");
    
    $pdo->exec("
        CREATE PROCEDURE reject_premium_request(
            IN p_request_id INT,
            IN p_processed_by INT,
            IN p_notes TEXT
        )
        BEGIN
            DECLARE v_bus_id INT;
            
            -- Get the bus ID from the request
            SELECT bus_id INTO v_bus_id FROM premium_requests WHERE id = p_request_id;
            
            -- Update the request status
            UPDATE premium_requests 
            SET status = 'rejected',
                processed_by = p_processed_by,
                processed_at = NOW(),
                notes = CONCAT_WS('\n', IFNULL(notes, ''), p_notes)
            WHERE id = p_request_id;
            
            -- Update the bus premium status
            UPDATE buses 
            SET premium_status = 'inactive',
                is_premium = 0
            WHERE id = v_bus_id;
            
            -- Log this action
            INSERT INTO activity_logs (user_id, action, details)
            VALUES (p_processed_by, 'premium_rejected', 
                   CONCAT('Rejected premium request #', p_request_id, ' for bus #', v_bus_id));
        END;
    ");
    
    echo "✓ Created reject_premium_request stored procedure\n";
    
    // Create a stored procedure to check and expire premium statuses
    $pdo->exec("
        DROP PROCEDURE IF EXISTS check_expired_premiums;
    ");
    
    $pdo->exec("
        CREATE PROCEDURE check_expired_premiums()
        BEGIN
            -- Update buses where premium has expired
            UPDATE buses 
            SET premium_status = 'expired',
                is_premium = 0
            WHERE premium_status = 'active'
            AND premium_expiry < NOW();
            
            -- Log the number of expired premiums
            SET @expired_count = ROW_COUNT();
            
            IF @expired_count > 0 THEN
                INSERT INTO activity_logs (action, details)
                VALUES ('premiums_expired', 
                       CONCAT('Expired premium status for ', @expired_count, ' buses'));
            END IF;
        END;
    ");
    
    echo "✓ Created check_expired_premiums stored procedure\n";
    
    // Create an event to check for expired premiums daily
    $pdo->exec("
        DROP EVENT IF EXISTS daily_premium_check;
    ");
    
    $pdo->exec("
        CREATE EVENT IF NOT EXISTS daily_premium_check
        ON SCHEDULE EVERY 1 DAY
        STARTS TIMESTAMP(CURRENT_DATE, '00:05:00')
        DO
            CALL check_expired_premiums();
    ");
    
    echo "✓ Created daily_premium_check event\n";
    
    // Enable the event scheduler if not already enabled
    $pdo->exec("SET GLOBAL event_scheduler = ON");
    
    echo "\n✓ Premium management setup completed successfully!\n";
    
} catch (PDOException $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    
    // Show the last query error if available
    if (isset($pdo)) {
        $errorInfo = $pdo->errorInfo();
        if (!empty($errorInfo[2])) {
            echo "PDO Error: " . $errorInfo[2] . "\n";
        }
    }
    
    echo "\nPremium management setup failed. Please check the errors above and try again.\n";
}
?>
