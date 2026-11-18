-- =====================================================================
--  ACCOUNT-BASED INVENTORY UPGRADE (Multi-User / Multi-Pharmacy)
--  TIP: Instead of running SQL manually, you can also simply visit:
--       http://precious.ifree.page/fix_db.php
--  which detects and applies any missing tables/columns automatically!
-- =====================================================================

-- 1. Add user_id column to medicines table (if not added yet)
ALTER TABLE `medicines`
  ADD COLUMN IF NOT EXISTS `user_id` INT(11) NULL AFTER `medicine_id`;

-- 2. Add index for user_id (ignore #1061 duplicate key if already created)
ALTER TABLE `medicines`
  ADD INDEX `idx_medicines_user` (`user_id`);

-- 3. Ensure stock_movements has user_id
ALTER TABLE `stock_movements`
  ADD COLUMN IF NOT EXISTS `user_id` INT(11) NULL AFTER `note`;

-- 4. Associate existing unassigned medicines with the first user
UPDATE `medicines`
SET `user_id` = (SELECT `user_id` FROM `users` ORDER BY `user_id` ASC LIMIT 1)
WHERE `user_id` IS NULL;

-- 5. Drop old single-user unique constraint (ignore if already dropped)
ALTER TABLE `medicines`
  DROP INDEX IF EXISTS `uq_medicine_batch`;

-- 6. Add multi-user unique constraint (ignore #1061 duplicate key if already created)
ALTER TABLE `medicines`
  ADD UNIQUE KEY `uq_user_medicine_batch` (`user_id`, `medicine_name`(100), `batch_number`(50));
