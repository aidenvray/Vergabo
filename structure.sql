-- Type: MySQL
-- Mostly denormalized for optimizations

-- PHASE I HERE --

-- accounts of all public users: authorization & system stuff, any client/supplier/partner goes here
-- NB: admin accounts are separate as should be admin cp! security reasons!!!
DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts` (
 `id` BIGINT UNSIGNED NOT NULL auto_increment, -- ordinal
 `ctime` INT UNSIGNED NOT NULL, -- mandatory
 `mtime` INT UNSIGNED NOT NULL, -- mandatory
 `role` VARCHAR(32) NOT NULL, -- mandatory, enum (client/supplier/partner/whatever)
 `email` VARCHAR(150) NOT NULL, -- mandatory
 `phone` VARCHAR(16) DEFAULT NULL, -- optional
 `password` VARCHAR(250) NOT NULL DEFAULT '',
 `autologin` VARCHAR(250) NOT NULL DEFAULT '',
 -- TODO: separate person/organization
 `first_name` VARCHAR(100) NOT NULL, -- mandatory
 `last_name` VARCHAR(100) NOT NULL DEFAULT '', -- optional
 `organization` VARCHAR(200) NOT NULL DEFAULT '', -- optional
 `tax_id` VARCHAR(50) NOT NULL DEFAULT '', -- optional
 `country` VARCHAR(100) NOT NULL, -- mandatory
 `city` VARCHAR(100) NOT NULL, -- mandatory
 `postal_code` VARCHAR(50) NOT NULL, -- mandatory
 `endpoint` VARCHAR(100) NOT NULL, -- mandatory, street/p.o.box
 --
 `email_verification` VARCHAR(16) DEFAULT NULL, -- both to verify and recover password
 `phone_verification` VARCHAR(16) DEFAULT NULL, -- both to verify and recover password
 `status` TINYINT UNSIGNED NOT NULL DEFAULT 0, -- enum: normal/banned/readonly/etc
 `email_ok` TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0/1, confirmed
 `phone_ok` TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0/1, confirmed
 PRIMARY KEY(`id`),
 UNIQUE KEY(`email`,`role`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;

-- sessions
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
 `id` VARCHAR(100) NOT NULL, -- mandatory
 `mtime` INT UNSIGNED NOT NULL, -- mandatory, last touch
 `account` BIGINT UNSIGNED NOT NULL, -- mandatory, foreign:accounts:id | 0
 `ip` VARCHAR(96) NOT NULL, -- mandatory, IPv4/IPv6
 `ua` VARCHAR(250) NOT NULL, -- mandatory, User-Agent
 `rf` VARCHAR(250) NOT NULL, -- mandatory, HTTP Referer
 `guard` VARCHAR(64) NOT NULL DEFAULT '', -- optional, CSRF guard token
 PRIMARY KEY(`id`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;

/* /!\ UNDER CONSTRUCTION /!\

-- client places requests for parts
CREATE TABLE `clients` (
 `id` BIGINT UNSIGNED NOT NULL, -- foreign:accounts:id
 ``
 PRIMARY KEY(`id`),
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
*/

-- supplier presents quotes to clients for their requests
-- organizations separated from their employees
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
 `id` BIGINT UNSIGNED NOT NULL auto_increment, -- ordinal
 `ctime` INT UNSIGNED NOT NULL, -- mandatory
 `mtime` INT UNSIGNED NOT NULL, -- mandatory
 `owner_id` BIGINT UNSIGNED NOT NULL, -- mandatory, foreign:accounts:id
 `name` VARCHAR(200) NOT NULL DEFAULT '', -- optional
 `tax_id` VARCHAR(50) NOT NULL DEFAULT '', -- optional
 `country` VARCHAR(100) NOT NULL, -- mandatory
 `city` VARCHAR(100) NOT NULL, -- mandatory
 `postal_code` VARCHAR(50) NOT NULL, -- mandatory
 `endpoint` VARCHAR(100) NOT NULL, -- mandatory, street/p.o.box
 `email` VARCHAR(150) NOT NULL, -- mandatory
 `phone` VARCHAR(16) NOT NULL DEFAULT '', -- optional
 PRIMARY KEY(`id`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;

DROP TABLE IF EXISTS `suppliers_employees`;
CREATE TABLE `suppliers_employees` (
 `supplier_id` BIGINT UNSIGNED NOT NULL, -- mandatory, foreign:accounts:id
 `employee_id` BIGINT UNSIGNED NOT NULL, -- mandatory, foreign:suppliers:id
 `role` VARCHAR(32) NOT NULL, -- mandatory, enum (ceo/cto/accountant/etc)
 UNIQUE KEY(`supplier_id`,`employee_id`) -- many-to-many potentially
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;

-- PHASE II HERE --
-- manufacturers list, to be determined
DROP TABLE IF EXISTS `manufacturers`;
CREATE TABLE `manufacturers` (
 `id` BIGINT UNSIGNED NOT NULL auto_increment, -- ordinal
 `ctime` INT UNSIGNED NOT NULL, -- mandatory
 `mtime` INT UNSIGNED NOT NULL, -- mandatory
 `ratio` INT UNSIGNED NOT NULL, -- mandatory, weight for ordering selection in form
 `active` TINYINT UNSIGNED NOT NULL, -- mandatory, boolean
 `name` VARCHAR(100) NOT NULL, -- mandatory
 `description` VARCHAR(250) NOT NULL, -- mandatory, comment/description, should be seen in acp
 PRIMARY KEY(`id`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;

-- login log, security reasons
DROP TABLE IF EXISTS `logins`;
CREATE TABLE `logins` (
 `account` BIGINT UNSIGNED NOT NULL, -- mandatory, foreign:accounts:id | null
 `time` INT UNSIGNED NOT NULL, -- mandatory, when
 `success` TINYINT UNSIGNED NOT NULL, -- mandatory, boolean, success/failure
 `ip` VARCHAR(96) NOT NULL, -- mandatory, IPv4/IPv6
 `ipex` VARCHAR(96) NOT NULL, -- mandatory, uncovered IPv4/IPv6
 `ua` VARCHAR(250) NOT NULL, -- mandatory, User-Agent
 `rf` VARCHAR(250) NOT NULL, -- mandatory, HTTP Referer
 UNIQUE KEY(`account`,`time`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;

-- admin area: admin accounts
-- there absolutely SHOULD be hardcoded recovery procedure in case of full admin lockout (no admins at all, all passwords lost etc)
-- standard recovery procedures SHOULD NOT apply here
DROP TABLE IF EXISTS `management`;
CREATE TABLE `management` (
 `id` BIGINT UNSIGNED NOT NULL auto_increment, -- ordinal
 `ctime` INT UNSIGNED NOT NULL, -- mandatory
 `mtime` INT UNSIGNED NOT NULL, -- mandatory, last touch
 `level` TINYINT UNSIGNED NOT NULL DEFAULT 0, -- optional, enum (root/admin/support/etc)
 `login` VARCHAR(64) NOT NULL, -- mandatory
 `password` VARCHAR(250) NOT NULL DEFAULT '',
 PRIMARY KEY(`id`),
 UNIQUE KEY(`login`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;

-- admin area: admin sessions
-- SHOULD be separated from public area!!
DROP TABLE IF EXISTS `management_sessions`;
CREATE TABLE `management_sessions` (
 `id` VARCHAR(100) NOT NULL, -- mandatory
 `mtime` INT UNSIGNED NOT NULL, -- mandatory, last touch
 `account` BIGINT UNSIGNED NOT NULL, -- mandatory, foreign:management:id | null
 `ip` VARCHAR(96) NOT NULL, -- mandatory, IPv4/IPv6
 `ua` VARCHAR(250) NOT NULL, -- mandatory, User-Agent
 `rf` VARCHAR(250) NOT NULL, -- mandatory, HTTP Referer
 PRIMARY KEY(`id`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;

-- admin area: admin login attempts log
-- SHOULD be separated from public area!!
DROP TABLE IF EXISTS `management_logins`;
CREATE TABLE `management_logins` (
 `account` BIGINT UNSIGNED NOT NULL, -- mandatory, foreign:management:id | null
 `time` INT UNSIGNED NOT NULL, -- mandatory, when
 `success` TINYINT UNSIGNED NOT NULL, -- mandatory, boolean, success/failure
 `ip` VARCHAR(96) NOT NULL, -- mandatory, IPv4/IPv6
 `ipex` VARCHAR(96) NOT NULL, -- mandatory, uncovered IPv4/IPv6
 `ua` VARCHAR(250) NOT NULL, -- mandatory, User-Agent
 `rf` VARCHAR(250) NOT NULL, -- mandatory, HTTP Referer
 UNIQUE KEY(`account`,`time`)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;

-- TBD





