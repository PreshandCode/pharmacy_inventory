
CREATE TABLE `medicines` (
  `medicine_id`   INT(11)       NOT NULL AUTO_INCREMENT,
  `medicine_name` VARCHAR(100)  NOT NULL,
  `category`      VARCHAR(50)   NOT NULL,
  `brand`         VARCHAR(100)  DEFAULT NULL,
  `batch_number`  VARCHAR(50)   DEFAULT NULL,
  `quantity`      INT(11)       NOT NULL DEFAULT 0,
  `unit_price`    DECIMAL(10,2) NOT NULL,
  `expiry_date`   DATE          NOT NULL,
  `supplier`      VARCHAR(100)  DEFAULT NULL,
  PRIMARY KEY (`medicine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
