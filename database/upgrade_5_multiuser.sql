-- 1. Add user_id to medicines table
ALTER TABLE `medicines`
  ADD COLUMN IF NOT EXISTS `user_id` INT(11) NULL AFTER `medicine_id`,
  ADD INDEX `idx_medicines_user` (`user_id`);

-- 2. Associate existing medicines with the first admin user so no data is lost
UPDATE `medicines`
SET `user_id` = (SELECT `user_id` FROM `users` ORDER BY `user_id` ASC LIMIT 1)
WHERE `user_id` IS NULL;

-- 3. Update the unique index on medicine_name + batch_number to be per-user
-- (Different users/pharmacies can have the same medicine name or batch number)
ALTER TABLE `medicines`
  DROP INDEX IF EXISTS `uq_medicine_batch`;

ALTER TABLE `medicines`
  ADD UNIQUE KEY IF NOT EXISTS `uq_user_medicine_batch` (`user_id`, `medicine_name`(100), `batch_number`(50));
