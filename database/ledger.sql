-- MySQL Script generated by MySQL Workbench
-- Po 9. duben 2018, 22:14:43 CEST
-- Model: New Model    Version: 1.0
-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema ledger
-- -----------------------------------------------------
DROP SCHEMA IF EXISTS `ledger` ;

-- -----------------------------------------------------
-- Schema ledger
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `ledger` DEFAULT CHARACTER SET utf8 ;
USE `ledger` ;

-- -----------------------------------------------------
-- Table `ledger`.`users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ledger`.`users` ;

CREATE TABLE IF NOT EXISTS `ledger`.`users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NULL,
  `password` CHAR(128) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC),
  UNIQUE INDEX `username_UNIQUE` (`username` ASC))
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_czech_ci;


-- -----------------------------------------------------
-- Table `ledger`.`types`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ledger`.`types` ;

CREATE TABLE IF NOT EXISTS `ledger`.`types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `valid` TINYINT(1) NOT NULL DEFAULT 1,
  `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_types_users_idx` (`user_id` ASC),
  CONSTRAINT `fk_types_users`
    FOREIGN KEY (`user_id`)
    REFERENCES `ledger`.`users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_czech_ci;


-- -----------------------------------------------------
-- Table `ledger`.`operations`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ledger`.`operations` ;

CREATE TABLE IF NOT EXISTS `ledger`.`operations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_czech_ci;


-- -----------------------------------------------------
-- Table `ledger`.`types_operations`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ledger`.`types_operations` ;

CREATE TABLE IF NOT EXISTS `ledger`.`types_operations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `operation_id` INT UNSIGNED NOT NULL,
  `type_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_type_operation_idx` (`type_id` ASC),
  INDEX `fk_operation_type_idx` (`operation_id` ASC),
  CONSTRAINT `fk_types_operations`
    FOREIGN KEY (`type_id`)
    REFERENCES `ledger`.`types` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_operations_types`
    FOREIGN KEY (`operation_id`)
    REFERENCES `ledger`.`operations` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_czech_ci;


-- -----------------------------------------------------
-- Table `ledger`.`points`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ledger`.`points` ;

CREATE TABLE IF NOT EXISTS `ledger`.`points` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lat` FLOAT NOT NULL,
  `long` FLOAT NOT NULL,
  `alt` FLOAT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_czech_ci;


-- -----------------------------------------------------
-- Table `ledger`.`moves`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ledger`.`moves` ;

CREATE TABLE IF NOT EXISTS `ledger`.`moves` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `operation_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `cost` INT NOT NULL,
  `description` VARCHAR(255) NULL,
  `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `due_date` TIMESTAMP NULL,
  `point_id` INT UNSIGNED NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valid` TINYINT(1) NOT NULL DEFAULT 1,
  `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`, `point_id`),
  INDEX `fk_move_operation_idx` (`operation_id` ASC),
  INDEX `fk_moves_users_idx` (`user_id` ASC),
  INDEX `fk_moves_point_idx` (`point_id` ASC),
  UNIQUE INDEX `point_id_UNIQUE` (`point_id` ASC),
  CONSTRAINT `fk_moves_operations`
    FOREIGN KEY (`operation_id`)
    REFERENCES `ledger`.`operations` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_moves_users`
    FOREIGN KEY (`user_id`)
    REFERENCES `ledger`.`users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_moves_point`
    FOREIGN KEY (`point_id`)
    REFERENCES `ledger`.`points` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_czech_ci;


-- -----------------------------------------------------
-- Table `ledger`.`types_moves`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `ledger`.`types_moves` ;

CREATE TABLE IF NOT EXISTS `ledger`.`types_moves` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type_id` INT UNSIGNED NOT NULL,
  `move_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_types_moves_idx` (`type_id` ASC),
  INDEX `fk_moves_types_idx` (`move_id` ASC),
  CONSTRAINT `fk_types_moves`
    FOREIGN KEY (`type_id`)
    REFERENCES `ledger`.`types` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_moves_types`
    FOREIGN KEY (`move_id`)
    REFERENCES `ledger`.`moves` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_czech_ci;

USE `ledger`;

DELIMITER $$

USE `ledger`$$
DROP TRIGGER IF EXISTS `ledger`.`moves_BEFORE_INSERT` $$
USE `ledger`$$
CREATE DEFINER = CURRENT_USER TRIGGER `ledger`.`moves_BEFORE_INSERT` BEFORE INSERT ON `moves` FOR EACH ROW
BEGIN
DECLARE msg varchar(128);

IF NOT (NEW.`operation_id` IN (4,5) OR NEW.`due_date` IS NULL) THEN
	SET msg = concat('Error: Column due_date is not valid for operation with id: ', cast(NEW.`operation_id` as char));
    SIGNAL SQLSTATE '45000' SET message_text = msg;
END IF;

IF (NEW.`operation_id` IN (4,5) AND NEW.`due_date` IS NOT NULL) THEN
	IF (NEW.`due_date` < NEW.`date`) THEN
		SET msg = 'Error: date can not be greater, than due_date';
        SIGNAL SQLSTATE '45000' SET message_text = msg;
    END IF;
END IF;

IF NOT (NEW.`operation_id` = 1 OR NEW.`point_id` IS NULL) THEN
	SET msg = concat('Error: Column point_id is not valid for operation with id: ', cast(NEW.`operation_id` as char));
    SIGNAL SQLSTATE '45000' SET message_text = msg;
END IF;

END$$


USE `ledger`$$
DROP TRIGGER IF EXISTS `ledger`.`moves_BEFORE_UPDATE` $$
USE `ledger`$$
CREATE DEFINER = CURRENT_USER TRIGGER `ledger`.`moves_BEFORE_UPDATE` BEFORE UPDATE ON `moves` FOR EACH ROW
BEGIN
DECLARE msg varchar(128);

IF NOT (NEW.`operation_id` IN (4,5) OR NEW.`due_date` IS NULL) THEN
	SET msg = concat('Error: Column due_date is not valid for operation with id: ', cast(NEW.`operation_id` as char));
    SIGNAL SQLSTATE '45000' SET message_text = msg;
END IF;

IF (NEW.`operation_id` IN (4,5) AND NEW.`due_date` IS NOT NULL) THEN
	IF (NEW.`due_date` < NEW.`date`) THEN
		SET msg = 'Error: date can not be greater, than due_date';
        SIGNAL SQLSTATE '45000' SET message_text = msg;
    END IF;
END IF;

IF NOT (NEW.`operation_id` = 1 OR NEW.`point_id` IS NULL) THEN
	SET msg = concat('Error: Column point_id is not valid for operation with id: ', cast(NEW.`operation_id` as char));
    SIGNAL SQLSTATE '45000' SET message_text = msg;
END IF;

END$$


USE `ledger`$$
DROP TRIGGER IF EXISTS `ledger`.`types_moves_BEFORE_INSERT` $$
USE `ledger`$$
CREATE DEFINER = CURRENT_USER TRIGGER `ledger`.`types_moves_BEFORE_INSERT` BEFORE INSERT ON `types_moves` FOR EACH ROW
BEGIN
DECLARE move_operation_id INT UNSIGNED;
DECLARE msg varchar(128);
DECLARE move_new_operation_id INT UNSIGNED;
SET move_new_operation_id = NEW.`operation_id`;

SET move_operation_id = (SELECT `moves`.`operation_id` FROM `moves` WHERE `moves`.`id` = NEW.`move_id` LIMIT 1);
IF NOT (move_new_operation_id = move_operation_id) THEN
		SET msg = 'Error: Unable to add type for move which does not blong to the same operation';
        SIGNAL SQLSTATE '45000' SET message_text = msg;
END IF;
END;$$


USE `ledger`$$
DROP TRIGGER IF EXISTS `ledger`.`types_moves_BEFORE_UPDATE` $$
USE `ledger`$$
CREATE DEFINER = CURRENT_USER TRIGGER `ledger`.`types_moves_BEFORE_UPDATE` BEFORE UPDATE ON `types_moves` FOR EACH ROW
BEGIN
DECLARE move_operation_id INT UNSIGNED;
DECLARE msg varchar(128);
SET move_operation_id = (SELECT `moves`.`operation_id` FROM `moves` WHERE `moves`.`id` = NEW.move_id LIMIT 1);
IF NOT (NEW.operation_id = move_operation_id) THEN
		SET msg = 'Error: Unable to add type for move which does not blong to the same operation';
        SIGNAL SQLSTATE '45000' SET message_text = msg;
END IF;
END$$


DELIMITER ;
SET SQL_MODE = '';
GRANT USAGE ON *.* TO app_w6;
 DROP USER app_w6;
SET SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';
CREATE USER 'app_w6' IDENTIFIED BY '36585h79c8h9_c/-';

GRANT SELECT ON TABLE `ledger`.* TO 'app_w6';
GRANT SELECT, INSERT, TRIGGER ON TABLE `ledger`.* TO 'app_w6';

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `ledger`.`operations`
-- -----------------------------------------------------
START TRANSACTION;
USE `ledger`;
INSERT INTO `ledger`.`operations` (`id`, `name`) VALUES (DEFAULT, 'expenses');
INSERT INTO `ledger`.`operations` (`id`, `name`) VALUES (DEFAULT, 'long_term_expenses');
INSERT INTO `ledger`.`operations` (`id`, `name`) VALUES (DEFAULT, 'incomes');
INSERT INTO `ledger`.`operations` (`id`, `name`) VALUES (DEFAULT, 'debts');
INSERT INTO `ledger`.`operations` (`id`, `name`) VALUES (DEFAULT, 'claims');

COMMIT;
