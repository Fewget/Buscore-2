-- Create premium_features table
CREATE TABLE IF NOT EXISTS premium_features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feature_name VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample premium feature
INSERT INTO premium_features (feature_name, description, start_date, end_date, is_active)
VALUES (
    'Premium Bus Listings',
    'Highlighted bus listings with priority placement',
    NOW(),
    DATE_ADD(NOW(), INTERVAL 30 DAY),
    TRUE
);
