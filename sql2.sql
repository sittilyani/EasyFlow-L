CREATE TABLE IF NOT EXISTS `pump_devices` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `label` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `port` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `pump_reservoir_history` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `pump_id` INT NOT NULL,
    `milligrams` DECIMAL(10, 2) NOT NULL,
    `new_milligrams` DECIMAL(10, 2) NOT NULL,
    `topup_from` DATETIME NOT NULL,
    `topup_to` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY (`pump_id`),
    CONSTRAINT `pump_history_link` FOREIGN KEY (`pump_id`) REFERENCES `pump_devices` (`id`)
);

ALTER TABLE `pharmacy` ADD COLUMN `pump_id` INT NULL;
ALTER TABLE `pharmacy` ADD CONSTRAINT `pharmacy_pump_link` FOREIGN KEY (`pump_id`) REFERENCES `pump_devices`(`id`);

-- Updated scripts
ALTER TABLE `pump_reservoir_history` CHANGE COLUMN `from` `topup_from` DATETIME NOT NULL;
ALTER TABLE `pump_reservoir_history` CHANGE COLUMN `to` `topup_to` DATETIME NULL;
