-- =====================================================================
--  PHARMACY INVENTORY SYSTEM - COMPLETE DATABASE SCHEMA & SAMPLE DATA
--  Course: Web Systems Technologies (PHP and MySQL Development)
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- 1. Table structure for table `users`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` DATETIME DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default sample admin account:
-- Username: admin
-- Password: password123
INSERT INTO `users` (`user_id`, `username`, `full_name`, `password_hash`, `role`, `created_at`, `last_login`) VALUES
(1, 'admin', 'Precious G. Porlas (Admin)', '$2y$10$wE0v2uG5E/H5bF4PjJ00tefD5Xl1r0j7tQzH1G4sA1z8J4xK6j.Ce', 'admin', NOW(), NOW());

-- -------------------------------------------------------------
-- 2. Table structure for table `medicines`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `medicines`;
CREATE TABLE `medicines` (
  `medicine_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT 1,
  `medicine_name` VARCHAR(100) NOT NULL,
  `product_type` VARCHAR(40) NOT NULL DEFAULT 'Medicine',
  `requires_rx` TINYINT(1) NOT NULL DEFAULT 0,
  `category` VARCHAR(50) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `brand` VARCHAR(100) DEFAULT NULL,
  `batch_number` VARCHAR(50) DEFAULT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 0,
  `unit` VARCHAR(20) DEFAULT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `expiry_date` DATE DEFAULT NULL,
  `supplier` VARCHAR(100) DEFAULT NULL,
  `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`medicine_id`),
  KEY `idx_medicines_user` (`user_id`),
  KEY `idx_medicine_name` (`medicine_name`),
  KEY `idx_category` (`category`),
  KEY `idx_product_type` (`product_type`),
  KEY `idx_expiry_date` (`expiry_date`),
  UNIQUE KEY `uq_user_medicine_batch` (`user_id`, `medicine_name`(100), `batch_number`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample product records
INSERT INTO `medicines` (`medicine_id`, `user_id`, `medicine_name`, `product_type`, `requires_rx`, `category`, `description`, `brand`, `batch_number`, `quantity`, `unit`, `unit_price`, `expiry_date`, `supplier`, `date_added`) VALUES
(1, 1, 'Paracetamol 500mg Tablet', 'Medicine', 0, 'Analgesic', 'Fast relief for fever and minor headaches.', 'Biogesic', 'BIO-2026A', 120, 'tablet', 7.50, DATE_ADD(CURDATE(), INTERVAL 365 DAY), 'Unilab Distribution', NOW()),
(2, 1, 'Amoxicillin 500mg Capsule', 'Medicine', 1, 'Antibiotic', 'Prescription antibiotic for bacterial infections.', 'Himox', 'AMX-8821', 45, 'capsule', 12.00, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'United Laboratories', NOW()),
(3, 1, 'Ascorbic Acid + Zinc 500mg', 'Vitamins & Supplements', 0, 'Vitamins', 'Immune health daily defense supplement.', 'Conzace', 'CNZ-1044', 85, 'capsule', 14.00, DATE_ADD(CURDATE(), INTERVAL 400 DAY), 'Unilab Distribution', NOW()),
(4, 1, 'Povidone Iodine 10% 60ml', 'First Aid', 0, 'Antiseptic', 'Topical wound antiseptic disinfectant.', 'Betadine', 'BTD-7701', 25, 'bottle', 125.00, DATE_ADD(CURDATE(), INTERVAL 500 DAY), 'Mundipharma', NOW()),
(5, 1, 'Digital Flexible Tip Thermometer', 'Medical Supplies & Devices', 0, 'Diagnostic Equipment', 'Quick-reading oral and underarm digital thermometer.', 'Omron', 'OMR-2026-T', 15, 'piece', 350.00, NULL, 'Omron Healthcare', NOW()),
(6, 1, 'Cetirizine 10mg Tablet', 'Medicine', 0, 'Antihistamine', 'Non-drowsy relief for allergies and rhinitis.', 'Alnix', 'ALN-3012', 8, 'tablet', 18.50, DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'Zuellig Pharma', NOW()),
(7, 1, 'Mefenamic Acid 500mg Capsule', 'Medicine', 1, 'Analgesic', 'Relief for toothache, pain, and dysmenorrhea.', 'Dolfenal', 'DLF-9904', 0, 'capsule', 15.00, DATE_ADD(CURDATE(), INTERVAL 180 DAY), 'United Laboratories', NOW()),
(8, 1, 'Expired Cough Syrup 60ml', 'Medicine', 0, 'Respiratory', 'Sample expired item for expiry disposal verification.', 'Robitussin', 'EXP-001', 12, 'bottle', 110.00, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Pfizer Consumer', NOW());

-- -------------------------------------------------------------
-- 3. Table structure for table `stock_movements`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `movement_id` INT(11) NOT NULL AUTO_INCREMENT,
  `medicine_id` INT(11) DEFAULT NULL,
  `medicine_name` VARCHAR(100) NOT NULL,
  `movement_type` ENUM('in', 'out', 'adjust') NOT NULL,
  `quantity` INT(11) NOT NULL,
  `quantity_before` INT(11) NOT NULL,
  `quantity_after` INT(11) NOT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`movement_id`),
  KEY `idx_movement_medicine` (`medicine_id`),
  KEY `idx_movement_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `stock_movements` (`medicine_id`, `medicine_name`, `movement_type`, `quantity`, `quantity_before`, `quantity_after`, `note`, `user_id`, `created_at`) VALUES
(1, 'Paracetamol 500mg Tablet', 'in', 120, 0, 120, 'Initial delivery from supplier', 1, NOW()),
(2, 'Amoxicillin 500mg Capsule', 'in', 50, 0, 50, 'Initial delivery', 1, NOW()),
(2, 'Amoxicillin 500mg Capsule', 'out', 5, 50, 45, 'Dispensed to customer prescription', 1, NOW()),
(6, 'Cetirizine 10mg Tablet', 'in', 20, 0, 20, 'Supplier stock delivery', 1, NOW()),
(6, 'Cetirizine 10mg Tablet', 'out', 12, 20, 8, 'Dispensed over-the-counter', 1, NOW());

-- -------------------------------------------------------------
-- 4. Table structure for table `login_attempts`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `ip_address` VARCHAR(45) NOT NULL,
  `attempts` TINYINT(4) NOT NULL DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- 5. Table structure for table `audit_logs`
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `username` VARCHAR(50) NOT NULL DEFAULT 'System',
  `action` VARCHAR(50) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_audit_created` (`created_at`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `audit_logs` (`user_id`, `username`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 'admin', 'Login', 'Initial admin login session', '127.0.0.1', NOW()),
(1, 'admin', 'Add Product', 'Added initial product catalog', '127.0.0.1', NOW());

SET FOREIGN_KEY_CHECKS = 1;
