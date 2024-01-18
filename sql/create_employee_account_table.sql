CREATE TABLE IF NOT EXISTS `PREFIX_employee_account`
(
  id_employee_account INT AUTO_INCREMENT NOT NULL,
  id_employee INT NOT NULL,
  email VARCHAR(64) NOT NULL,
  uid VARCHAR(64) NOT NULL,
  date_add DATETIME NOT NULL,
  date_upd DATETIME NOT NULL,
  PRIMARY KEY(id_employee_account)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB;
