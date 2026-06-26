-- ============================================================
-- 文件：install/database.sql
-- 作用：VanShine 数据库结构定义（安装时执行）
-- 版本：1.0.30
-- 说明：{prefix} 为表前缀占位符，安装时自动替换
-- ============================================================

-- 管理员表
CREATE TABLE IF NOT EXISTS `{prefix}admin` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password` char(32) NOT NULL COMMENT 'password hash',
    `email` varchar(100) NOT NULL,
    `avatar_url` varchar(500) NOT NULL DEFAULT '' COMMENT '自定义头像链接',
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

-- 文件管理文件夹
CREATE TABLE IF NOT EXISTS `{prefix}file_folder` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `parent_id` int(10) unsigned NOT NULL DEFAULT 0,
    `name` varchar(255) NOT NULL,
    `storage_key` tinyint(3) unsigned NOT NULL COMMENT '储存策略 KEY',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件管理文件夹';

-- 文件管理记录
CREATE TABLE IF NOT EXISTS `{prefix}file_item` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `folder_id` int(10) unsigned NOT NULL,
    `storage_key` tinyint(3) unsigned NOT NULL,
    `pathname` varchar(500) NOT NULL COMMENT '储存内路径',
    `original_name` varchar(255) NOT NULL,
    `stored_name` varchar(255) NOT NULL,
    `mime_type` varchar(100) NOT NULL DEFAULT '',
    `file_size` bigint(20) unsigned NOT NULL DEFAULT 0,
    `public_url` varchar(1000) NOT NULL DEFAULT '',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_folder_id` (`folder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件管理记录';

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
('mail_from_name', 'VanShine'),
('upload_naming_mode', 'sequence'),
('upload_name_sequence', '0'),
('upload_date_sequence', ''),
('storage_local_enabled', '0'),
('storage_local_url', ''),
('storage_local_root', ''),
('storage_local_queries', ''),
('storage_local_public_slug', ''),
('storage_s3_enabled', '0'),
('storage_s3_url', ''),
('storage_s3_access_key_id', ''),
('storage_s3_secret_access_key', ''),
('storage_s3_endpoint', ''),
('storage_s3_region', ''),
('storage_s3_bucket', ''),
('storage_s3_queries', ''),
('storage_oss_enabled', '0'),
('storage_oss_url', ''),
('storage_oss_access_key_id', ''),
('storage_oss_access_key_secret', ''),
('storage_oss_endpoint', ''),
('storage_oss_bucket', ''),
('storage_oss_queries', ''),
('storage_cos_enabled', '0'),
('storage_cos_url', ''),
('storage_cos_app_id', ''),
('storage_cos_secret_id', ''),
('storage_cos_secret_key', ''),
('storage_cos_region', ''),
('storage_cos_bucket', ''),
('storage_cos_queries', ''),
('storage_qiniu_enabled', '0'),
('storage_qiniu_url', ''),
('storage_qiniu_access_key', ''),
('storage_qiniu_secret_key', ''),
('storage_qiniu_bucket', ''),
('storage_qiniu_queries', ''),
('storage_ilanzou_enabled', '0'),
('storage_ilanzou_url', ''),
('storage_ilanzou_username', ''),
('storage_ilanzou_password', ''),
('storage_ilanzou_folder_id', '0'),
('storage_ilanzou_uuid', ''),
('storage_ilanzou_token', ''),
('storage_ilanzou_ip', ''),
('storage_ilanzou_queries', ''),
('storage_webdav_enabled', '0'),
('storage_webdav_url', ''),
('storage_webdav_base_uri', ''),
('storage_webdav_username', ''),
('storage_webdav_password', ''),
('storage_webdav_queries', '');
