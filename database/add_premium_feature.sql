-- Add is_premium column to buses table
ALTER TABLE buses
ADD COLUMN is_premium TINYINT(1) NOT NULL DEFAULT 0
COMMENT 'Flag to indicate if premium features are enabled (1) or not (0)'
AFTER route_description;

-- Add index for better performance on premium searches
CREATE INDEX idx_bus_premium ON buses(is_premium);
