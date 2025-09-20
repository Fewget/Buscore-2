-- Add service_records table
CREATE TABLE IF NOT EXISTS service_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT NOT NULL,
    service_type ENUM('engine_oil', 'brake_pads', 'tire_rotation', 'other') NOT NULL,
    service_date DATE NOT NULL,
    mileage INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add premium features settings to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS show_bus_name TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS show_company_name TINYINT(1) DEFAULT 0;

-- Update buses table to include service-related fields
ALTER TABLE buses
ADD COLUMN IF NOT EXISTS last_engine_oil_change DATE,
ADD COLUMN IF NOT EXISTS last_engine_oil_mileage INT,
ADD COLUMN IF NOT EXISTS last_brake_change DATE,
ADD COLUMN IF NOT EXISTS last_brake_mileage INT;
