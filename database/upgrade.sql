-- =====================================================================
--  PHARMACY INVENTORY SYSTEM - DATABASE UPGRADE (run ONCE)
-- =====================================================================
--  HOW TO RUN:
--    1. Open phpMyAdmin and click your database in the left panel
--       (InfinityFree: if0_43404545_pharmacyinventory_db).
--    2. Click the "SQL" tab, paste this whole file, click "Go".
--
--  SAFE: this script only ADDS things. It does not delete, rename or
--  change any of your existing columns or medicine records.
--  Run it only once. Running it a second time will show a
--  "Duplicate column name" error, and nothing else is changed.
-- =====================================================================


-- ---------------------------------------------------------------------
-- (A) New columns on the existing `medicines` table
--     description : optional notes about the medicine
--     date_added  : filled in automatically when a medicine is added
--                   (existing medicines get the date this script runs)
-- ---------------------------------------------------------------------
ALTER TABLE `medicines`
  ADD COLUMN `description` TEXT NULL AFTER `category`,
  ADD COLUMN `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `supplier`;

-- Indexes make searching, filtering and sorting faster
ALTER TABLE `medicines`
  ADD INDEX `idx_medicine_name` (`medicine_name`),
  ADD INDEX `idx_category` (`category`),
  ADD INDEX `idx_expiry_date` (`expiry_date`);


-- ---------------------------------------------------------------------
-- (B) Stock movement history
--     One row every time stock goes in, out, or is corrected.
--     medicine_name is copied so history stays readable even if the
--     medicine is deleted later.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `movement_id`     INT(11)      NOT NULL AUTO_INCREMENT,
  `medicine_id`     INT(11)      NULL,
  `medicine_name`   VARCHAR(100) NOT NULL,
  `movement_type`   ENUM('in','out','adjust') NOT NULL,
  `quantity`        INT(11)      NOT NULL,
  `quantity_before` INT(11)      NOT NULL,
  `quantity_after`  INT(11)      NOT NULL,
  `note`            VARCHAR(255) NULL,
  `user_id`         INT(11)      NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`movement_id`),
  KEY `idx_movement_medicine` (`medicine_id`),
  KEY `idx_movement_created` (`created_at`),
  CONSTRAINT `fk_movement_medicine`
    FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`medicine_id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Record the stock you already have as an "opening balance",
-- so the history and charts start from your real current quantities.
INSERT INTO `stock_movements`
  (`medicine_id`, `medicine_name`, `movement_type`, `quantity`,
   `quantity_before`, `quantity_after`, `note`)
SELECT `medicine_id`, `medicine_name`, 'in', `quantity`,
       0, `quantity`, 'Opening balance (system upgrade)'
FROM `medicines`
WHERE `quantity` > 0;


-- ---------------------------------------------------------------------
-- (C) User accounts for login
--     Passwords are stored as secure hashes, never as plain text.
--     The first admin account is created with setup_admin.php.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `full_name`     VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login`    DATETIME     NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
