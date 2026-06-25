-- ============================================================
-- 文件：install/database.sql
-- 作用：VanShine 数据库结构定义（安装时执行）
-- 版本：1.0.4
-- 说明：{prefix} 为表前缀占位符，安装时自动替换
-- ============================================================

-- 管理员表
CREATE TABLE IF NOT EXISTS `{prefix}admin` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password` char(32) NOT NULL COMMENT 'password hash',
    `email` varchar(100) NOT NULL,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 系统配置表
CREATE TABLE IF NOT EXISTS `{prefix}config` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `key` varchar(50) NOT NULL,
    `value` text,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- 密码重置令牌表
CREATE TABLE IF NOT EXISTS `{prefix}password_reset` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `admin_id` int(10) unsigned NOT NULL,
    `token` varchar(64) NOT NULL,
    `expire_at` datetime NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_token` (`token`),
    KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='密码重置令牌表';

-- 绑定域名表（子域名独立站点名称与备案信息）
CREATE TABLE IF NOT EXISTS `{prefix}domain` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `domain` varchar(255) NOT NULL COMMENT '绑定域名，不含协议与端口',
    `site_name` varchar(100) NOT NULL DEFAULT '',
    `icp_number` varchar(100) NOT NULL DEFAULT '' COMMENT 'ICP备案号',
    `gongan_number` varchar(100) NOT NULL DEFAULT '' COMMENT '公安备案号',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='绑定域名表';

-- 初始系统配置（首次安装写入数据库，不在代码中维护默认值）
INSERT INTO `{prefix}config` (`key`, `value`) VALUES
('site_name', 'VanShine'),
('site_description', '基于 PHP + MySQL 的轻量级 Web 管理系统'),
('site_keywords', 'VanShine,PHP,MySQL,管理系统'),
('site_favicon', ''),
('site_logo', ''),
('primary_domain', ''),
('site_icp', ''),
('site_gongan', ''),
('mail_enabled', '0'),
('mail_smtp_host', ''),
('mail_smtp_port', '465'),
('mail_smtp_user', ''),
('mail_smtp_pass', ''),
('mail_smtp_secure', 'ssl'),
('mail_from_email', ''),
('mail_from_name', 'VanShine');
