CREATE DATABASE IF NOT EXISTS `wire_db` DEFAULT CHARACTER SET utf8mb4;
USE `wire_db`;

CREATE TABLE IF NOT EXISTS `b_gateway` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `serial` varchar(32) NOT NULL,
  `xy` varchar(64) DEFAULT NULL,
  `addr` varchar(64) DEFAULT NULL,
  `fid` int(11) DEFAULT 0,
  `upgrade` int(11) DEFAULT 0,
  `uid` int(10) unsigned NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_serial` (`serial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `b_dev` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `serial` varchar(32) NOT NULL,
  `addr` varchar(64) DEFAULT NULL,
  `feature` text NOT NULL,
  `gid` int(10) unsigned NOT NULL DEFAULT 0,
  `proto` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `star` tinyint(4) NOT NULL DEFAULT 0,
  `uid` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_dev_serial` (`serial`),
  KEY `idx_gid` (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `b_calc_day` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `day` int(10) unsigned NOT NULL,
  `snap` text NULL,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `b_dev_warn` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `did` int(10) unsigned NOT NULL,
  `day` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `note` varchar(64) DEFAULT NULL,
  `deal_date` timestamp NULL DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_did` (`did`),
  KEY `idx_day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `b_gateway` (`name`,`serial`,`uid`,`status`,`created`,`updated`) VALUES
  ('现场网关','gw1001',0,1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated`=NOW();

INSERT INTO `b_dev` (`name`,`serial`,`addr`,`feature`,`gid`,`proto`,`star`,`uid`,`status`,`created`,`updated`) VALUES
  ('设备A','01','现场','["f10","f11"]',1,1,1,0,1,NOW(),NOW()),
  ('设备B','02','现场','["f16","f17"]',1,1,0,0,1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`feature`=VALUES(`feature`),`updated`=NOW();

INSERT INTO `b_calc_day` (`day`,`snap`,`created`,`updated`) VALUES
  (DATE_FORMAT(NOW(),'%Y%m%d'), '{"k0":{"g":[5,2]}}', NOW(), NOW());

COMMIT;


