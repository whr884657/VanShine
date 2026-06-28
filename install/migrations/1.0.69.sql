-- ============================================================
-- 文件：install/migrations/1.0.69.sql
-- 作用：腾讯云通用密钥、EdgeOne CDN 配置项
-- ============================================================

INSERT INTO `{prefix}config` (`key`, `value`)
SELECT 'tencent_secret_id', ''
WHERE NOT EXISTS (SELECT 1 FROM `{prefix}config` WHERE `key` = 'tencent_secret_id');

INSERT INTO `{prefix}config` (`key`, `value`)
SELECT 'tencent_secret_key', ''
WHERE NOT EXISTS (SELECT 1 FROM `{prefix}config` WHERE `key` = 'tencent_secret_key');

INSERT INTO `{prefix}config` (`key`, `value`)
SELECT 'tencent_app_id', ''
WHERE NOT EXISTS (SELECT 1 FROM `{prefix}config` WHERE `key` = 'tencent_app_id');

INSERT INTO `{prefix}config` (`key`, `value`)
SELECT 'tencent_region', 'ap-guangzhou'
WHERE NOT EXISTS (SELECT 1 FROM `{prefix}config` WHERE `key` = 'tencent_region');

INSERT INTO `{prefix}config` (`key`, `value`)
SELECT 'cdn_edgeone_enabled', '0'
WHERE NOT EXISTS (SELECT 1 FROM `{prefix}config` WHERE `key` = 'cdn_edgeone_enabled');

INSERT INTO `{prefix}config` (`key`, `value`)
SELECT 'cdn_edgeone_secret_id', ''
WHERE NOT EXISTS (SELECT 1 FROM `{prefix}config` WHERE `key` = 'cdn_edgeone_secret_id');

INSERT INTO `{prefix}config` (`key`, `value`)
SELECT 'cdn_edgeone_secret_key', ''
WHERE NOT EXISTS (SELECT 1 FROM `{prefix}config` WHERE `key` = 'cdn_edgeone_secret_key');

INSERT INTO `{prefix}config` (`key`, `value`)
SELECT 'cdn_edgeone_region', ''
WHERE NOT EXISTS (SELECT 1 FROM `{prefix}config` WHERE `key` = 'cdn_edgeone_region');

INSERT INTO `{prefix}config` (`key`, `value`)
SELECT 'cdn_edgeone_token', ''
WHERE NOT EXISTS (SELECT 1 FROM `{prefix}config` WHERE `key` = 'cdn_edgeone_token');

INSERT INTO `{prefix}config` (`key`, `value`)
SELECT 'cdn_edgeone_language', 'zh-CN'
WHERE NOT EXISTS (SELECT 1 FROM `{prefix}config` WHERE `key` = 'cdn_edgeone_language');
