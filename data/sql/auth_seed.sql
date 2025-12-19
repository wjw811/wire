CREATE DATABASE IF NOT EXISTS `wire_db` DEFAULT CHARACTER SET utf8mb4;
USE `wire_db`;

CREATE TABLE IF NOT EXISTS `s_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `password` varchar(128) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `s_user` (`code`,`name`,`password`,`status`,`created`,`updated`) VALUES
  ('super','Super Admin',NULL,1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`status`=VALUES(`status`),`updated`=NOW();

CREATE TABLE IF NOT EXISTS `s_option` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `s_option` (`code`,`value`,`created`,`updated`) VALUES
  ('feature','[{"key":"f48","en":"Over V","zh":"过压","unit":"V"},{"key":"f49","en":"Over I","zh":"过流","unit":"A"}]',NOW(),NOW())
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`),`updated`=NOW();

COMMIT;

