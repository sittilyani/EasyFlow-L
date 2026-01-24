CREATE TABLE pump_calibration (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pump_id INT NOT NULL,
    calibration_factor DECIMAL(10,2) NOT NULL,
    concentration_mg_per_ml DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    tubing_type VARCHAR(50),
    calibrated_by VARCHAR(100),
    calibrated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (pump_id) REFERENCES pump_devices(id) ON DELETE CASCADE
);

-- Optional: Add index for faster lookups
CREATE INDEX idx_pump_active ON pump_calibration(pump_id, is_active);