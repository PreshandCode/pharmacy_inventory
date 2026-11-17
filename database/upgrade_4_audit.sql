CREATE TABLE IF NOT EXISTS `audit_logs` (
  `log_id`      INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)      NULL,
  `username`    VARCHAR(50)  NOT NULL DEFAULT 'System',
  `action`      VARCHAR(50)  NOT NULL,
  `details`     TEXT         NULL,
  `ip_address`  VARCHAR(45)  NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_audit_created` (`created_at`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional: add last_updated tracking to medicines table if not present
ALTER TABLE `medicines`
  ADD COLUMN IF NOT EXISTS `last_updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `date_added`;

