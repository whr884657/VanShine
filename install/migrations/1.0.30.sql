-- ============================================================
-- 文件：install/migrations/1.0.30.sql
-- 作用：文件管理、储存配置与全局命名机制
-- 说明：{prefix} 为表前缀占位符
-- ============================================================

CREATE TABLE IF NOT EXISTS `{prefix}file_folder` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `parent_id` int(10) unsigned NOT NULL DEFAULT 0,
    `name` varchar(255) NOT NULL,
    `storage_key` tinyint(3) unsigned NOT NULL COMMENT '储存策略 KEY',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件管理文件夹';

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

INSERT IGNORE INTO `{prefix}config` (`key`, `value`) VALUES
('upload_naming_mode', 'sequence'),
('upload_name_sequence', '0'),
('upload_date_sequence', ''),
('storage_local_enabled', '0'),
('storage_local_url', ''),
('storage_local_root', ''),
('storage_local_queries', ''),
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
