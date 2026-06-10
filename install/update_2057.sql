-- 数据库升级脚本 v2057
-- 多管理员账户 + 细粒度权限系统

-- 创建管理员表
CREATE TABLE IF NOT EXISTS `pre_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '登录用户名',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '登录密码哈希 (bcrypt)',
  `pay_password` varchar(255) NOT NULL DEFAULT '' COMMENT '支付密码哈希 (bcrypt)',
  `role_id` int(11) NOT NULL DEFAULT '1' COMMENT '角色ID',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用, 1=启用',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `login_count` int(11) NOT NULL DEFAULT '0' COMMENT '登录次数',
  `totp_secret` varchar(32) DEFAULT NULL COMMENT 'TOTP密钥',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员账户表';

-- 创建角色表
CREATE TABLE IF NOT EXISTS `pre_admin_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '角色名称',
  `code` varchar(50) NOT NULL COMMENT '角色代码标识',
  `permissions` text NOT NULL COMMENT '权限列表JSON',
  `description` varchar(255) DEFAULT NULL COMMENT '角色描述',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统内置角色(不可删除)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员角色表';

-- 插入默认角色（已存在则跳过）
INSERT IGNORE INTO `pre_admin_role` (`name`, `code`, `permissions`, `description`, `is_system`) VALUES
('超级管理员', 'superadmin', '["all"]', '拥有全部权限', 1),
('财务管理员', 'finance', '["order.view","order.export","settle","transfer","transfer.view","transfer_batch","profitview","record","buyerstat","blacklist"]', '可管理订单、结算、转账、资金明细', 1),
('运营管理员', 'operator', '["order.view","order.export","buyerstat","user.manage","channel","paytype","plugin","domain","invitecode","anounce"]', '可管理订单、支付通道、商户、公告', 1),
('审计管理员', 'auditor', '["order.view","record","log","risk","blacklist","buyerstat","transfer.view"]', '只读审计，查看日志和风控', 1);

-- 标记已迁移（PHP 侧迁移逻辑运行后会更新此值）
INSERT INTO `pre_config` (`k`, `v`) VALUES ('admin_migrated', '0')
ON DUPLICATE KEY UPDATE `v` = `v`;
