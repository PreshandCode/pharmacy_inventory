-- (A) IP-based login attempt tracking
--     Replaces the session-based counter so lockout cannot be bypassed
--     by clearing cookies or starting a new browser tab.
--     Rows are keyed by IP address (VARCHAR 45 covers both IPv4 & IPv6).

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `ip_address`    VARCHAR(45)  NOT NULL,
  `attempts`      TINYINT      NOT NULL DEFAULT 0,
  `locked_until`  DATETIME     NULL,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- (B) UNIQUE index on (medicine_name, batch_number)
--     Prevents two staff members from accidentally creating duplicate
--     records for the same product + batch at the same time.
--     NOTE: MySQL allows multiple NULL values in a unique index, so
--     products without a batch number are not affected.

ALTER TABLE `medicines`
  ADD UNIQUE KEY IF NOT EXISTS `uq_medicine_batch` (`medicine_name`(100), `batch_number`(50));
