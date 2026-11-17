-- =====================================================================
--  PHARMACY INVENTORY SYSTEM - UPGRADE 2: PRODUCTS (run ONCE)
-- =====================================================================
--  Run this AFTER database/upgrade.sql.
--
--  HOW TO RUN:
--    1. Open phpMyAdmin and click your database in the left panel.
--    2. Click the "SQL" tab, paste this whole file, click "Go".
--
--  SAFE: this script only ADDS columns and allows an empty expiry date.
--  No records are deleted or renamed. Every existing record becomes
--  product type "Medicine".
--  Run it only once. A second run shows "Duplicate column name" and
--  changes nothing.
-- =====================================================================

ALTER TABLE `medicines`
  -- Medicine, Vitamins & Supplements, Personal Care, ... (checked by the app)
  ADD COLUMN `product_type` VARCHAR(40) NOT NULL DEFAULT 'Medicine' AFTER `medicine_name`,
  -- piece, box, bottle, pack, tube, sachet, strip, tablet (empty = "units")
  ADD COLUMN `unit` VARCHAR(20) NULL AFTER `quantity`,
  -- 1 = prescription required (only used for type Medicine)
  ADD COLUMN `requires_rx` TINYINT(1) NOT NULL DEFAULT 0 AFTER `product_type`,
  -- Products such as thermometers have no expiry date
  MODIFY `expiry_date` DATE NULL,
  ADD INDEX `idx_product_type` (`product_type`);
