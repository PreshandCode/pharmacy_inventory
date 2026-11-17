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
