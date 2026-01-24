CREATE TABLE IF NOT EXISTS `pump_calibration` (
    `id` int NOT NULL AUTO_INCREMENT,
    `pump_id` int NOT NULL,
    `calibration_factor` decimal(10,2) NOT NULL,
    `concentration_mg_per_ml` decimal(5,2) NOT NULL DEFAULT '5.00',
    `tubing_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `calibrated_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `calibrated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` text COLLATE utf8mb4_general_ci,
    `is_active` tinyint(1) DEFAULT '1',
    PRIMARY KEY (`id`),
    KEY `idx_pump_active` (`pump_id`,`is_active`),
    CONSTRAINT `pump_calibration_ibfk_1` FOREIGN KEY (`pump_id`) REFERENCES `pump_devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pump_calibration` VALUES('1', '1', '500.00', '5.00', NULL, 'System', '2026-01-24 07:25:54', 'Default calibration', '1');
