-- ============================================================
-- 文件：install/migrations/1.0.35.sql
-- 作用：本地储存对外直链随机 slug
-- 说明：{prefix} 为表前缀占位符
-- ============================================================

INSERT INTO `{prefix}config` (`key`, `value`) VALUES
('storage_local_public_slug', '')
ON DUPLICATE KEY UPDATE `key` = `key`;
