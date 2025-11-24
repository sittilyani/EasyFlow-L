
CREATE TABLE IF NOT EXISTS `temp_updates` (
  `p_id` int NOT NULL,
  `new_clientName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`p_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




CREATE TABLE IF NOT EXISTS `toxicology_results` (
  `tox_id` int NOT NULL AUTO_INCREMENT,
  `visitDate` datetime NOT NULL,
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clientName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mode_drug_use` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `amphetamine` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `metamphetamine` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `morphine` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `barbiturates` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cocaine` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `codeine` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `benzodiazepines` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `marijuana` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `amitriptyline` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `opiates` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phencyclidine` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `methadone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `buprenorphine` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nicotine` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_tca` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tramadol` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ketamine` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fentanyl` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `oxycodone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `propoxyphene` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ecstacy` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_drugs` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lab_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `date_of_test` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `next_appointment` date NOT NULL,
  `lab_officer_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tox_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `toxicology_results` VALUES('5', '2025-10-01 00:00:00', '23368MAT00005', 'JAMAL JIND KHAN', 'PWID', 'yes', 'no', 'no', 'no', 'yes', 'no', 'no', 'yes', 'no', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Testing software', '2025-10-01 00:00:00', '2025-10-31', 'User Admin', '2025-10-01 23:05:06');



CREATE TABLE IF NOT EXISTS `tpt_regimens` (
  `tpt_id` int NOT NULL AUTO_INCREMENT,
  `tpt_regimen_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`tpt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




CREATE TABLE IF NOT EXISTS `transaction_types` (
  `id` int NOT NULL,
  `transactionType` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transaction_types` VALUES('1', 'Purchase');
INSERT INTO `transaction_types` VALUES('2', 'Promotion');
INSERT INTO `transaction_types` VALUES('3', 'Donation');
INSERT INTO `transaction_types` VALUES('4', 'Returns');
INSERT INTO `transaction_types` VALUES('5', 'Exchange');



CREATE TABLE IF NOT EXISTS `treatment_stage` (
  `stage_id` int NOT NULL AUTO_INCREMENT,
  `stage_of_rx_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`stage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




CREATE TABLE IF NOT EXISTS `userroles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descr` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `userroles` VALUES('1', 'Admin', 'Overall system user');
INSERT INTO `userroles` VALUES('2', 'Clinician', 'prescribes to patients drugs');
INSERT INTO `userroles` VALUES('3', 'Receptionist', 'Registers patients in the system');
INSERT INTO `userroles` VALUES('4', 'Pharmacist', 'Dispenses drugs to patients');
INSERT INTO `userroles` VALUES('5', 'Psychologist', 'Counsels Clients');
INSERT INTO `userroles` VALUES('6', 'Laboratory Scientist', 'Tests Clients');
INSERT INTO `userroles` VALUES('7', 'Peer Educator', 'Check daily roll for clients');
INSERT INTO `userroles` VALUES('8', 'HRIO', 'HRIO');
INSERT INTO `userroles` VALUES('9', 'Psychiatrist', 'Psychiatrist');
INSERT INTO `userroles` VALUES('10', 'Data Manager', 'Data Manager');
INSERT INTO `userroles` VALUES('11', 'Guest', 'Guest');
INSERT INTO `userroles` VALUES('12', 'Super Admin', 'Administrator of everything');



CREATE TABLE IF NOT EXISTS `viral_load` (
  `vl_id` int NOT NULL AUTO_INCREMENT,
  `mat_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clientName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dob` date NOT NULL,
  `reg_date` date NOT NULL,
  `sex` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `hiv_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `art_regimen` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `regimen_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinical_notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `last_vlDate` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `results` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `next_appointment` date NOT NULL,
  `comp_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vl_id`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `viral_load` VALUES('107', '2', 'TEST', '1985-01-02', '2018-11-05', 'Male', 'Positive', 'AF2E - TDF + 3TC + DTG', 'First Line', '', '', '', 'Super Admin', '2018-11-23', '2025-10-01 08:35:11');
INSERT INTO `viral_load` VALUES('108', '2', 'TEST', '1985-01-02', '2018-11-05', 'Male', 'Positive', 'AF2E - TDF + 3TC + DTG', 'First Line', '', '', '', 'Super Admin', '2025-11-26', '2025-10-01 08:36:15');


DELIMITER ;;
CREATE TRIGGER `update_patient_next_appointment` AFTER INSERT ON `medical_history` FOR EACH ROW BEGIN
    -- Update next_appointment in patients table only if new value is not empty
    IF NEW.next_appointment IS NOT NULL AND NEW.next_appointment != '' THEN
        UPDATE patients 
        SET next_appointment = NEW.next_appointment 
        WHERE mat_id = NEW.mat_id;
    END IF;
END;;
DELIMITER ;


DELIMITER ;;
CREATE TRIGGER `update_tca_dates` BEFORE UPDATE ON `patients` FOR EACH ROW BEGIN
    IF NEW.next_appointment IS NOT NULL THEN
        SET NEW.psycho_social_tca = NEW.next_appointment;
        SET NEW.psychiatric_tca = NEW.next_appointment;
        SET NEW.nursing_tca = NEW.next_appointment;
        SET NEW.nutrition_tca = NEW.next_appointment;
        SET NEW.laboratory_tca = NEW.next_appointment;
        SET NEW.records_tca = NEW.next_appointment;
        SET NEW.peer_tca = NEW.next_appointment;
        SET NEW.admin_tca = NEW.next_appointment;
    END IF;
END;;
DELIMITER ;


DELIMITER ;;
CREATE TRIGGER `before_patients_delete` BEFORE DELETE ON `patients` FOR EACH ROW BEGIN
    INSERT INTO deleted_patients
    SELECT * FROM patients WHERE mat_id = OLD.mat_id;
END;;
DELIMITER ;


DELIMITER ;;
CREATE TRIGGER `trg_sync_stores_balance` AFTER INSERT ON `stock_movements` FOR EACH ROW BEGIN
    -- Check if an entry for this drugID already exists in the summary table
    DECLARE inventory_exists INT DEFAULT 0;

    SELECT COUNT(*) INTO inventory_exists
    FROM stores_inventory
    WHERE drugID = NEW.drugID;

    IF inventory_exists > 0 THEN
        -- If it exists, update the stores_balance with the latest total_qty
        UPDATE stores_inventory
        SET 
            stores_balance = NEW.total_qty,
            -- Optionally update non-balance fields with the latest movement info
            supplier_name = NEW.received_from,
            received_by_full_name = NEW.received_by
        WHERE drugID = NEW.drugID;
    ELSE
        -- If it does not exist, insert a new record
        INSERT INTO stores_inventory (
            drugID, 
            drugname, 
            stores_balance,
            supplier_name,          
            from_supplier,          
            received_by_full_name,  
            to_dispensing           
        )
        VALUES (
            NEW.drugID, 
            NEW.drugName, 
            NEW.total_qty, 
            NEW.received_from,      
            NEW.qty_in,             
            NEW.received_by,
            0                      
        );
    END IF;
END;;
DELIMITER ;


DELIMITER ;;
CREATE TRIGGER `trg_populate_full_name` BEFORE INSERT ON `tblusers` FOR EACH ROW BEGIN
    SET NEW.full_name = CONCAT(NEW.first_name, ' ', NEW.last_name);
END;;
DELIMITER ;


DELIMITER ;;
CREATE TRIGGER `trg_populate_full_name_insert` BEFORE INSERT ON `tblusers` FOR EACH ROW BEGIN
    SET NEW.full_name = CONCAT(NEW.first_name, ' ', NEW.last_name);
END;;
DELIMITER ;


DELIMITER ;;
CREATE TRIGGER `trg_populate_full_name_update` BEFORE UPDATE ON `tblusers` FOR EACH ROW BEGIN
    SET NEW.full_name = CONCAT(NEW.first_name, ' ', NEW.last_name);
END;;
DELIMITER ;

