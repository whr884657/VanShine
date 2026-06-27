-- v1.0.53：文件/文件夹分享
CREATE TABLE IF NOT EXISTS `{prefix}file_share` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `token` varchar(64) NOT NULL,
    `share_type` enum('file','folder') NOT NULL DEFAULT 'file',
    `file_id` int(10) unsigned NOT NULL DEFAULT 0,
    `folder_id` int(10) unsigned NOT NULL DEFAULT 0,
    `title` varchar(255) NOT NULL DEFAULT '',
    `password_hash` char(32) NOT NULL DEFAULT '',
    `expires_at` datetime DEFAULT NULL,
    `max_downloads` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '0=不限',
    `download_count` int(10) unsigned NOT NULL DEFAULT 0,
    `view_count` int(10) unsigned NOT NULL DEFAULT 0,
    `allow_preview` tinyint(1) NOT NULL DEFAULT 1,
    `enabled` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_token` (`token`),
    KEY `idx_file_id` (`file_id`),
    KEY `idx_folder_id` (`folder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件分享';
