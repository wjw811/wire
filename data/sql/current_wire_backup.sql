-- Wire数据库完整备份
-- 生成时间: 2025-10-12 15:25:22
-- 数据库: wire_db

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wire_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `b_calc_day`
--

CREATE TABLE `b_calc_day` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `day` int(10) unsigned NOT NULL COMMENT '日期',
  `snap` mediumtext COMMENT '数据快照',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='日数据统计表';

-- --------------------------------------------------------

--
-- Table structure for table `b_dev`
--

CREATE TABLE `b_dev` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '设备名称',
  `serial` varchar(16) NOT NULL COMMENT '编号',
  `addr` varchar(64) DEFAULT NULL COMMENT '地址',
  `feature` text NOT NULL COMMENT '设备功能',
  `gid` int(10) unsigned NOT NULL DEFAULT '0',
  `proto` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `star` tinyint(4) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_dev_serial` (`serial`),
  KEY `idx_gid` (`gid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='设备表';

--
-- Dumping data for table `b_dev`
--

INSERT INTO `b_dev` (`id`, `name`, `serial`, `addr`, `feature`, `gid`, `proto`, `star`, `uid`, `status`, `created`, `updated`) VALUES
(1, '温度传感器1', 'T001', '现场A区', '[\"temperature\",\"humidity\"]', 1, 1, 0, 0, 1, '2025-10-12 15:25:16', '2025-10-12 15:25:16'),
(2, '温度传感器2', 'T002', '现场A区', '[\"temperature\"]', 1, 1, 0, 0, 1, '2025-10-12 15:25:16', '2025-10-12 15:25:16'),
(3, '压力传感器1', 'P001', '现场B区', '[\"pressure\"]', 2, 1, 0, 0, 1, '2025-10-12 15:25:16', '2025-10-12 15:25:16'),
(4, '流量计1', 'F001', '现场B区', '[\"flow\"]', 2, 1, 0, 0, 1, '2025-10-12 15:25:16', '2025-10-12 15:25:16');

-- --------------------------------------------------------

--
-- Table structure for table `b_dev_log`
--

CREATE TABLE `b_dev_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `did` int(10) unsigned NOT NULL COMMENT '设备ID',
  `raw` text NOT NULL COMMENT '原始数据',
  `content` text NOT NULL COMMENT '日志内容',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `did` (`did`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='硬件日志表';

-- --------------------------------------------------------

--
-- Table structure for table `b_dev_warn`
--

CREATE TABLE `b_dev_warn` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `did` int(10) unsigned NOT NULL,
  `day` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `note` varchar(64) DEFAULT NULL,
  `deal_date` timestamp NULL DEFAULT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_did` (`did`),
  KEY `idx_day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `b_fireware`
--

CREATE TABLE `b_fireware` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL COMMENT '名称',
  `ver` int(11) unsigned NOT NULL COMMENT '版本号',
  `file` varchar(64) NOT NULL COMMENT '文件',
  `size` int(11) NOT NULL COMMENT '文件大小',
  `checksum` varchar(64) NOT NULL COMMENT '校验值',
  `status` tinyint(4) NOT NULL COMMENT '状态：0停用 1启用 9删除',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='固件表';

-- --------------------------------------------------------

--
-- Table structure for table `b_gateway`
--

CREATE TABLE `b_gateway` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `serial` varchar(32) NOT NULL,
  `xy` varchar(64) DEFAULT NULL,
  `addr` varchar(64) DEFAULT NULL,
  `fid` int(11) DEFAULT '0',
  `upgrade` int(11) DEFAULT '0',
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_serial` (`serial`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `b_gateway`
--

INSERT INTO `b_gateway` (`id`, `name`, `serial`, `xy`, `addr`, `fid`, `upgrade`, `uid`, `status`, `created`, `updated`) VALUES
(1, '现场网关1', 'gw1001', NULL, '现场A区', 0, 0, 0, 1, '2025-10-12 15:25:16', '2025-10-12 15:25:16'),
(2, '现场网关2', 'gw1002', NULL, '现场B区', 0, 0, 0, 1, '2025-10-12 15:25:16', '2025-10-12 15:25:16');

-- --------------------------------------------------------

--
-- Table structure for table `s_log`
--

CREATE TABLE `s_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(16) NOT NULL COMMENT '操作代号',
  `content` varchar(255) NOT NULL COMMENT '操作内容',
  `uid` int(10) unsigned NOT NULL COMMENT '操作人',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台操作日志表';

-- --------------------------------------------------------

--
-- Table structure for table `s_module`
--

CREATE TABLE `s_module` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(16) NOT NULL COMMENT '模块编码',
  `name` varchar(32) NOT NULL COMMENT '模块名称',
  `url` varchar(50) NOT NULL COMMENT '模块URL',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='模块表';

--
-- Dumping data for table `s_module`
--

INSERT INTO `s_module` (`id`, `code`, `name`, `url`) VALUES
(1, 'sys', '系统管理', ''),
(2, 'dev', '设备管理', ''),
(3, 'dashboard', '面板', ''),
(4, 'gateway', '网关', '');

-- --------------------------------------------------------

--
-- Table structure for table `s_module_privilege`
--

CREATE TABLE `s_module_privilege` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_code` varchar(16) NOT NULL COMMENT '模块编号',
  `privilege_code` varchar(16) NOT NULL COMMENT '权限编码',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COMMENT='模块权限表';

--
-- Dumping data for table `s_module_privilege`
--

INSERT INTO `s_module_privilege` (`id`, `module_code`, `privilege_code`) VALUES
(1, 'sys', 'select'),
(2, 'sys', 'add'),
(3, 'sys', 'edit'),
(4, 'sys', 'del'),
(5, 'dev', 'select'),
(6, 'dev', 'add'),
(7, 'dev', 'edit'),
(8, 'dev', 'del'),
(9, 'gateway', 'select'),
(10, 'gateway', 'add'),
(11, 'gateway', 'edit'),
(12, 'gateway', 'del'),
(13, 'dashboard', 'select');

-- --------------------------------------------------------

--
-- Table structure for table `s_option`
--

CREATE TABLE `s_option` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `s_option`
--

INSERT INTO `s_option` (`id`, `code`, `value`, `created`, `updated`) VALUES
(1, 'feature', '[{\"key\":\"temperature\",\"en\":\"Temperature\",\"zh\":\"温度\",\"unit\":\"°C\"},{\"key\":\"humidity\",\"en\":\"Humidity\",\"zh\":\"湿度\",\"unit\":\"%\"},{\"key\":\"pressure\",\"en\":\"Pressure\",\"zh\":\"压力\",\"unit\":\"Pa\"},{\"key\":\"flow\",\"en\":\"Flow\",\"zh\":\"流量\",\"unit\":\"L/min\"}]', NULL, '2025-10-12 15:25:16'),
(2, 'proto', '[{\"key\":1, \"val\":\"V1.0\"},{\"key\":2, \"val\":\"V2.0\"}]', NULL, NULL),
(4, 'system_name', 'Wire监控系统', '2025-10-12 15:25:16', '2025-10-12 15:25:16'),
(5, 'system_version', '2.0.0', '2025-10-12 15:25:16', '2025-10-12 15:25:16');

-- --------------------------------------------------------

--
-- Table structure for table `s_privilege`
--

CREATE TABLE `s_privilege` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(16) NOT NULL COMMENT '权限编码',
  `name` varchar(32) NOT NULL COMMENT '权限名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='权限表';

--
-- Dumping data for table `s_privilege`
--

INSERT INTO `s_privilege` (`id`, `code`, `name`) VALUES
(1, 'select', '查询'),
(2, 'add', '增加'),
(3, 'edit', '编辑'),
(4, 'del', '删除');

-- --------------------------------------------------------

--
-- Table structure for table `s_role`
--

CREATE TABLE `s_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `s_role`
--

INSERT INTO `s_role` (`id`, `code`, `name`, `status`, `created`, `updated`) VALUES
(4, 'super', '超级管理员', 1, '2025-10-12 15:25:16', '2025-10-12 15:25:16'),
(5, 'admin', '管理员', 1, '2025-10-12 15:25:16', '2025-10-12 15:25:16'),
(6, 'dealer', '代理商', 1, '2025-10-12 15:25:16', '2025-10-12 15:25:16'),
(7, 'user', '普通用户', 1, '2025-10-12 15:25:16', '2025-10-12 15:25:16');

-- --------------------------------------------------------

--
-- Table structure for table `s_role_privilege`
--

CREATE TABLE `s_role_privilege` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_code` varchar(16) NOT NULL,
  `module_privilege_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COMMENT=' 角色权限表';

--
-- Dumping data for table `s_role_privilege`
--

INSERT INTO `s_role_privilege` (`id`, `role_code`, `module_privilege_id`) VALUES
(1, 'admin', 1),
(2, 'admin', 5),
(3, 'admin', 6),
(4, 'admin', 7),
(5, 'admin', 8),
(50, 'guest', 9),
(51, 'guest', 13),
(52, 'guest', 17),
(55, 'dealer', 5),
(56, 'dealer', 7),
(59, 'dealer', 6),
(60, 'dealer', 8),
(61, 'admin', 2),
(62, 'admin', 3),
(63, 'admin', 4),
(68, 'admin', 9),
(69, 'admin', 10),
(70, 'admin', 11),
(71, 'admin', 12),
(72, 'admin', 13),
(76, 'dealer', 13);

-- --------------------------------------------------------

--
-- Table structure for table `s_user`
--

CREATE TABLE `s_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL,
  `pwd` varchar(128) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `login_time` datetime DEFAULT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `s_user`
--

INSERT INTO `s_user` (`id`, `code`, `name`, `pwd`, `email`, `phone`, `login_time`, `status`, `created`, `updated`) VALUES
(1, 'super', '超级管理员', '6ab729e97caf4f53d96eb8f39a55f517', 'fred@api4.me', 13270827996, '2025-01-10 01:09:40', 1, '2018-10-17 16:00:00', '2025-10-12 15:23:13'),
(2, 'admin', '管理员', '561783f57bb6209c2052dba221f93a95', 'fred@api4.me', 13270827996, '2024-10-26 17:38:19', 1, '2018-10-17 16:00:00', '2024-10-26 17:38:19'),
(3, 'dealer1', '代理商1', '2e0db02fdaca727a73bcb1fa9b11c7a5', 'libei@njstandard.net', 17507405335, '2025-01-10 00:51:11', 1, '2024-12-25 06:35:13', '2025-01-10 00:51:11'),
(4, 'dealer2', '代理商2', '2f5567cabc63ad10f85256a50cc92b7b', 'lijiabao@njstandard.net', 17726375431, '2025-01-09 08:12:49', 1, '2025-01-08 07:26:39', '2025-01-09 08:12:49');

-- --------------------------------------------------------

--
-- Table structure for table `s_user_role`
--

CREATE TABLE `s_user_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_code` varchar(32) NOT NULL,
  `role_code` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `s_user_role`
--

INSERT INTO `s_user_role` (`id`, `user_code`, `role_code`) VALUES
(1, 'super', 'super'),
(2, 'admin', 'admin'),
(3, 'dealer1', 'dealer'),
(5, 'dealer2', 'dealer');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
