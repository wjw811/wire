-- 为网关表添加 local_ip 字段
-- 用于存储设备在局域网内的 IP 地址

ALTER TABLE `b_gateway` 
ADD COLUMN `local_ip` VARCHAR(50) DEFAULT NULL COMMENT '局域网IP地址' AFTER `serial`;

-- 更新示例数据（如果有现有网关）
-- UPDATE `b_gateway` SET `local_ip` = '192.168.2.10' WHERE `id` = 1;


