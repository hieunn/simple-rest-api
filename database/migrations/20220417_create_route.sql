CREATE TABLE IF NOT EXISTS `routes` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(100) NULL DEFAULT NULL,
	`parent_id` INT(11) NULL DEFAULT NULL,
	`lft` TINYINT(5) NULL DEFAULT NULL,
	`rgt` TINYINT(5) NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `name` (`name`),
	INDEX `parent_id` (`parent_id`),
	INDEX `lft_rgt` (`lft`, `rgt`)
)
ENGINE=InnoDB;
