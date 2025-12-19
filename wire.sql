USE wire_db; SET NAMES utf8mb4;
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Jan 10, 2025 at 02:19 AM
-- Server version: 5.7.36
-- PHP Version: 7.2.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wire`
--

-- --------------------------------------------------------

--
-- Table structure for table `b_calc_day`
--

CREATE TABLE `b_calc_day` (
  `id` int(10) UNSIGNED NOT NULL,
  `day` int(10) UNSIGNED NOT NULL COMMENT '日期',
  `snap` mediumtext COMMENT '数据快照',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='日数据统计表';

-- --------------------------------------------------------

--
-- Table structure for table `b_dev`
--

CREATE TABLE `b_dev` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL COMMENT '设备名称',
  `serial` varchar(16) NOT NULL COMMENT '编号',
  `addr` varchar(64) DEFAULT NULL COMMENT '地址',
  `feature` text NOT NULL COMMENT '设备功能',
  `gid` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '网关ID',
  `proto` tinyint(3) UNSIGNED NOT NULL DEFAULT '1' COMMENT '协议版本',
  `star` tinyint(4) NOT NULL DEFAULT '0' COMMENT '星标',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID ',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：0停用 1启用 9删除',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备�?;

-- --------------------------------------------------------

--
-- Table structure for table `b_dev_log`
--

CREATE TABLE `b_dev_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `did` int(10) UNSIGNED NOT NULL COMMENT '设备ID',
  `raw` text NOT NULL COMMENT '原始数据',
  `content` text NOT NULL COMMENT '日志内容',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='硬件日志�?;

-- --------------------------------------------------------

--
-- Table structure for table `b_dev_warn`
--

CREATE TABLE `b_dev_warn` (
  `id` int(10) UNSIGNED NOT NULL,
  `did` int(10) UNSIGNED NOT NULL COMMENT '设备ID',
  `day` int(10) UNSIGNED NOT NULL COMMENT '日期',
  `content` text NOT NULL COMMENT '日志内容',
  `status` tinyint(4) DEFAULT '0' COMMENT '状态：0未处�?1误报 2已处�?3忽略',
  `note` varchar(64) DEFAULT NULL COMMENT '处理结果',
  `deal_date` timestamp NULL DEFAULT NULL COMMENT '处置时间',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='硬件报警�?;

-- --------------------------------------------------------

--
-- Table structure for table `b_fireware`
--

CREATE TABLE `b_fireware` (
  `id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL COMMENT '名称',
  `ver` int(11) UNSIGNED NOT NULL COMMENT '版本�?,
  `file` varchar(64) NOT NULL COMMENT '文件',
  `size` int(11) NOT NULL COMMENT '文件大小',
  `checksum` varchar(64) NOT NULL COMMENT '校验�?,
  `status` tinyint(4) NOT NULL COMMENT '状态：0停用 1启用 9删除',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='固件�?;

-- --------------------------------------------------------

--
-- Table structure for table `b_gateway`
--

CREATE TABLE `b_gateway` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL COMMENT '网关名称',
  `serial` varchar(16) NOT NULL COMMENT '编号',
  `xy` varchar(64) DEFAULT NULL COMMENT '经纬�?,
  `addr` varchar(64) DEFAULT NULL COMMENT '地址',
  `fid` int(11) DEFAULT '0' COMMENT '固件ID',
  `upgrade` int(11) DEFAULT NULL COMMENT '更新状态：0未更 -1已更 其他更新�?,
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：0停用 1启用 9删除',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网关�?;

-- --------------------------------------------------------

--
-- Table structure for table `s_log`
--

CREATE TABLE `s_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(16) NOT NULL COMMENT '操作代号',
  `content` varchar(255) NOT NULL COMMENT '操作内容',
  `uid` int(10) UNSIGNED NOT NULL COMMENT '操作�?,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台操作日志�?;

-- --------------------------------------------------------

--
-- Table structure for table `s_module`
--

CREATE TABLE `s_module` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(16) NOT NULL COMMENT '模块编码',
  `name` varchar(32) NOT NULL COMMENT '模块名称',
  `url` varchar(50) NOT NULL COMMENT '模块URL'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模块�?;

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
  `id` int(11) NOT NULL,
  `module_code` varchar(16) NOT NULL COMMENT '模块编号',
  `privilege_code` varchar(16) NOT NULL COMMENT '权限编码'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模块权限�?;

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
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(16) NOT NULL COMMENT '编码',
  `value` text NOT NULL COMMENT '配置内容KV',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置�?;

--
-- Dumping data for table `s_option`
--

INSERT INTO `s_option` (`id`, `code`, `value`, `created`, `updated`) VALUES
(1, 'feature', '[{\"key\":\"f01\",\"zh_CN\":\"系统A相有功\",\"en\":\"active power of system A\",\"unit\":\"kW\"},{\"key\":\"f02\",\"zh_CN\":\"系统A相无功\",\"en\":\"System A-phase reactive power\",\"unit\":\"kvar\"},{\"key\":\"f03\",\"zh_CN\":\"系统A相功率因数\",\"en\":\"system A-phase power factor\",\"unit\":\"\"},{\"key\":\"f04\",\"zh_CN\":\"系统B相有功\",\"en\":\"system B-phase active power\",\"unit\":\"kW\"},{\"key\":\"f05\",\"zh_CN\":\"系统B相无功\",\"en\":\"system B-phase reactive power\",\"unit\":\"kvar\"},{\"key\":\"f06\",\"zh_CN\":\"系统B相功率因数\",\"en\":\"system B-phase power factor\",\"unit\":\"\"},{\"key\":\"f07\",\"zh_CN\":\"系统C相有功\",\"en\":\"system C-phase active power\",\"unit\":\"kW\"},{\"key\":\"f08\",\"zh_CN\":\"系统C相无功\",\"en\":\"system C-phase reactive power\",\"unit\":\"kvar\"},{\"key\":\"f09\",\"zh_CN\":\"系统C相功率因数\",\"en\":\"system C-phase power factor\",\"unit\":\"\"},{\"key\":\"f10\",\"zh_CN\":\"输出A相有功\",\"en\":\"output A-phase active power\",\"unit\":\"kW\"},{\"key\":\"f11\",\"zh_CN\":\"输出A相无功\",\"en\":\"output A-phase reactive power\",\"unit\":\"kvar\"},{\"key\":\"f12\",\"zh_CN\":\"输出A相功率因数\",\"en\":\"output A-phase power factor\",\"unit\":\"\"},{\"key\":\"f13\",\"zh_CN\":\"输出B相有功\",\"en\":\"output B-phase active power\",\"unit\":\"kW\"},{\"key\":\"f14\",\"zh_CN\":\"输出B相无功\",\"en\":\"output B-phase reactive power\",\"unit\":\"kvar\"},{\"key\":\"f15\",\"zh_CN\":\"输出B相功率因数\",\"en\":\"output B-phase power factor\",\"unit\":\"\"},{\"key\":\"f16\",\"zh_CN\":\"输出C相有功\",\"en\":\"output C-phase active power\",\"unit\":\"kW\"},{\"key\":\"f17\",\"zh_CN\":\"输出C相无功\",\"en\":\"output C-phase reactive power\",\"unit\":\"kvar\"},{\"key\":\"f18\",\"zh_CN\":\"输出C相功率因数\",\"en\":\"output C-phase power factor\",\"unit\":\"\"},{\"key\":\"f19\",\"zh_CN\":\"FPGA版本号\",\"en\":\"FPGA version number\",\"unit\":\"\"},{\"key\":\"f20\",\"zh_CN\":\"状态\",\"en\":\"state\",\"unit\":\"\"},{\"key\":\"f21\",\"zh_CN\":\"预留\",\"en\":\"reserve\",\"unit\":\"\"},{\"key\":\"f22\",\"zh_CN\":\"DSPB版本号\",\"en\":\"DSPB version number\",\"unit\":\"\"},{\"key\":\"f23\",\"zh_CN\":\"A相电网电压\",\"en\":\"A-phase grid voltage\",\"unit\":\"V\"},{\"key\":\"f24\",\"zh_CN\":\"B相电网电压\",\"en\":\"B-phase grid voltage\",\"unit\":\"V\"},{\"key\":\"f25\",\"zh_CN\":\"C相电网电压\",\"en\":\"C-phase grid voltage\",\"unit\":\"V\"},{\"key\":\"f26\",\"zh_CN\":\"DSPA版本号\",\"en\":\"DSPA version number\",\"unit\":\"\"},{\"key\":\"f27\",\"zh_CN\":\"电网电流不平衡度\",\"en\":\"grid current imbalance\",\"unit\":\"%\"},{\"key\":\"f28\",\"zh_CN\":\"电网频率\",\"en\":\"grid frequency\",\"unit\":\"Hz\"},{\"key\":\"f29\",\"zh_CN\":\"A相温度\",\"en\":\"A-phase temperature\",\"unit\":\"℃\"},{\"key\":\"f30\",\"zh_CN\":\"电网电压不平衡度\",\"en\":\"grid voltage imbalance\",\"unit\":\"%\"},{\"key\":\"f31\",\"zh_CN\":\"直流母线电压\",\"en\":\"DC bus voltage\",\"unit\":\"V\"},{\"key\":\"f32\",\"zh_CN\":\"上分裂电容电压\",\"en\":\"upper split capacitor voltage\",\"unit\":\"V\"},{\"key\":\"f33\",\"zh_CN\":\"下分裂电容电压\",\"en\":\"lower split capacitor voltage\",\"unit\":\"V\"},{\"key\":\"f34\",\"zh_CN\":\"B相温度\",\"en\":\"B-phase temperature\",\"unit\":\"℃\"},{\"key\":\"f35\",\"zh_CN\":\"C相温度\",\"en\":\"C-phase temperature\",\"unit\":\"℃\"},{\"key\":\"f36\",\"zh_CN\":\"A相装置电流\",\"en\":\"A-phase device current\",\"unit\":\"A\"},{\"key\":\"f37\",\"zh_CN\":\"B相装置电流\",\"en\":\"B-phase device current\",\"unit\":\"A\"},{\"key\":\"f38\",\"zh_CN\":\"C相装置电流\",\"en\":\"C-phase device current\",\"unit\":\"A\"},{\"key\":\"f39\",\"zh_CN\":\"A相负载电流\",\"en\":\"A-phase load current\",\"unit\":\"A\"},{\"key\":\"f40\",\"zh_CN\":\"B相负载电流\",\"en\":\"B-phase load current\",\"unit\":\"A\"},{\"key\":\"f41\",\"zh_CN\":\"C相负载电流\",\"en\":\"C-phase load current\",\"unit\":\"A\"},{\"key\":\"f42\",\"zh_CN\":\"A相网侧电流\",\"en\":\"A-phase grid side current\",\"unit\":\"A\"},{\"key\":\"f43\",\"zh_CN\":\"B相网侧电流\",\"en\":\"B-phase grid side current\",\"unit\":\"A\"},{\"key\":\"f44\",\"zh_CN\":\"C相网侧电流\",\"en\":\"C-phase grid side current\",\"unit\":\"A\"},{\"key\":\"f45\",\"zh_CN\":\"A相设备电流\",\"en\":\"A-phase equipment current\",\"unit\":\"A\"},{\"key\":\"f46\",\"zh_CN\":\"B相设备电流\",\"en\":\"B-phase equipment current\",\"unit\":\"A\"},{\"key\":\"f47\",\"zh_CN\":\"C相设备电流\",\"en\":\"C-phase equipment current\",\"unit\":\"A\"},{\"key\":\"f48\",\"zh_CN\":\"故障及状态信�?\",\"en\":\"Fault and status information 5\",\"unit\":\"\"},{\"key\":\"f49\",\"zh_CN\":\"故障及状态信�?\",\"en\":\"Fault and status information 4\",\"unit\":\"\"},{\"key\":\"f50\",\"zh_CN\":\"故障及状态信�?\",\"en\":\"Fault and status information 3\",\"unit\":\"\"},{\"key\":\"f51\",\"zh_CN\":\"故障及状态信�?\",\"en\":\"Fault and status information 2\",\"unit\":\"\"},{\"key\":\"f52\",\"zh_CN\":\"EXTSTATE0\",\"en\":\"EXTSTATE0\",\"unit\":\"\"},{\"key\":\"f53\",\"zh_CN\":\"EXTSTATE2\",\"en\":\"EXTSTATE2\",\"unit\":\"\"},{\"key\":\"f54\",\"zh_CN\":\"负载A相有功\",\"en\":\"Load A-phase active power\",\"unit\":\"KW\"},{\"key\":\"f55\",\"zh_CN\":\"负载A相无功\",\"en\":\"Load A-phase reactive power\",\"unit\":\"kvar\"},{\"key\":\"f56\",\"zh_CN\":\"负载B相有功\",\"en\":\"Load B-phase active power\",\"unit\":\"KW\"},{\"key\":\"f57\",\"zh_CN\":\"负载B相无功\",\"en\":\"Load B-phase reactive power\",\"unit\":\"kvar\"},{\"key\":\"f58\",\"zh_CN\":\"负载C相有功\",\"en\":\"Load C-phase active power\",\"unit\":\"KW\"},{\"key\":\"f59\",\"zh_CN\":\"负载C相无功\",\"en\":\"Load C-phase reactive power\",\"unit\":\"kvar\"},{\"key\":\"f60\",\"zh_CN\":\"工作模式\",\"en\":\"Working mode\",\"unit\":\"\"}]', NULL, NULL),
(2, 'proto', '[{\"key\":1, \"val\":\"V1.0\"},{\"key\":2, \"val\":\"V2.0\"}]', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `s_privilege`
--

CREATE TABLE `s_privilege` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(16) NOT NULL COMMENT '权限编码',
  `name` varchar(32) NOT NULL COMMENT '权限名称'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限�?;

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
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(16) NOT NULL COMMENT '代号',
  `name` varchar(32) NOT NULL COMMENT '名称',
  `preset` tinyint(1) NOT NULL DEFAULT '0' COMMENT '预置状�? 1预置 0非预�?,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色�?;

--
-- Dumping data for table `s_role`
--

INSERT INTO `s_role` (`id`, `code`, `name`, `preset`, `created`, `updated`) VALUES
(1, 'super', '超级管理�?, 1, '2018-10-17 16:00:00', '2018-10-17 16:00:00'),
(2, 'admin', '管理�?, 1, '2018-10-17 16:00:00', '2018-10-17 16:00:00'),
(3, 'dealer', '代理�?, 0, '2024-12-25 06:36:38', '2024-12-25 08:14:10');

-- --------------------------------------------------------

--
-- Table structure for table `s_role_privilege`
--

CREATE TABLE `s_role_privilege` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_code` varchar(16) NOT NULL,
  `module_privilege_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT=' 角色权限�?;

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
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(16) NOT NULL COMMENT '登录名称',
  `name` varchar(16) NOT NULL COMMENT '显示名称',
  `pwd` char(32) NOT NULL COMMENT '加密后的密码',
  `email` varchar(64) NOT NULL,
  `phone` varchar(16) DEFAULT NULL COMMENT '手机�?,
  `login_time` timestamp NULL DEFAULT NULL COMMENT '最后登录时�?,
  `status` tinyint(3) UNSIGNED NOT NULL COMMENT '状态：0停用 1启用 9删除',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账号�?;

--
-- Dumping data for table `s_user`
--

INSERT INTO `s_user` (`id`, `code`, `name`, `pwd`, `email`, `phone`, `login_time`, `status`, `created`, `updated`) VALUES
(1, 'super', '超级管理�?, '561783f57bb6209c2052dba221f93a95', 'fred@api4.me', '13270827996', '2025-01-10 01:09:40', 1, '2018-10-17 16:00:00', '2025-01-10 01:09:40'),
(2, 'admin', '管理�?, '561783f57bb6209c2052dba221f93a95', 'fred@api4.me', '13270827996', '2024-10-26 17:38:19', 1, '2018-10-17 16:00:00', '2024-10-26 17:38:19'),
(3, 'dealer1', '代理�?', '2e0db02fdaca727a73bcb1fa9b11c7a5', 'libei@njstandard.net', '17507405335', '2025-01-10 00:51:11', 1, '2024-12-25 06:35:13', '2025-01-10 00:51:11'),
(4, 'dealer2', '代理�?', '2f5567cabc63ad10f85256a50cc92b7b', 'lijiabao@njstandard.net', '17726375431', '2025-01-09 08:12:49', 1, '2025-01-08 07:26:39', '2025-01-09 08:12:49');

-- --------------------------------------------------------

--
-- Table structure for table `s_user_role`
--

CREATE TABLE `s_user_role` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_code` varchar(16) NOT NULL COMMENT '用户代号',
  `role_code` varchar(16) NOT NULL COMMENT '角色代号'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账号角色�?;

--
-- Dumping data for table `s_user_role`
--

INSERT INTO `s_user_role` (`id`, `user_code`, `role_code`) VALUES
(1, 'super', 'super'),
(2, 'admin', 'admin'),
(3, 'dealer1', 'dealer'),
(5, 'dealer2', 'dealer');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `b_calc_day`
--
ALTER TABLE `b_calc_day`
  ADD PRIMARY KEY (`id`),
  ADD KEY `day` (`day`);

--
-- Indexes for table `b_dev`
--
ALTER TABLE `b_dev`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nid` (`gid`);

--
-- Indexes for table `b_dev_log`
--
ALTER TABLE `b_dev_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `did` (`did`);

--
-- Indexes for table `b_dev_warn`
--
ALTER TABLE `b_dev_warn`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `b_fireware`
--
ALTER TABLE `b_fireware`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `b_gateway`
--
ALTER TABLE `b_gateway`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `s_log`
--
ALTER TABLE `s_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `s_module`
--
ALTER TABLE `s_module`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `s_module_privilege`
--
ALTER TABLE `s_module_privilege`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `s_option`
--
ALTER TABLE `s_option`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `s_privilege`
--
ALTER TABLE `s_privilege`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `s_role`
--
ALTER TABLE `s_role`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `s_role_privilege`
--
ALTER TABLE `s_role_privilege`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `s_user`
--
ALTER TABLE `s_user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `s_user_role`
--
ALTER TABLE `s_user_role`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `b_calc_day`
--
ALTER TABLE `b_calc_day`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `b_dev`
--
ALTER TABLE `b_dev`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `b_dev_log`
--
ALTER TABLE `b_dev_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `b_dev_warn`
--
ALTER TABLE `b_dev_warn`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `b_fireware`
--
ALTER TABLE `b_fireware`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `b_gateway`
--
ALTER TABLE `b_gateway`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `s_log`
--
ALTER TABLE `s_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `s_module`
--
ALTER TABLE `s_module`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `s_module_privilege`
--
ALTER TABLE `s_module_privilege`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `s_option`
--
ALTER TABLE `s_option`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `s_privilege`
--
ALTER TABLE `s_privilege`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `s_role`
--
ALTER TABLE `s_role`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `s_role_privilege`
--
ALTER TABLE `s_role_privilege`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `s_user`
--
ALTER TABLE `s_user`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `s_user_role`
--
ALTER TABLE `s_user_role`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

