-- ============================================================
-- 文件：install/migrations/1.0.31.sql
-- 作用：储存 KEY 统一为 1–7（WebDAV 由 9 调整为 7）
-- 说明：{prefix} 为表前缀占位符
-- ============================================================

UPDATE `{prefix}file_folder` SET `storage_key` = 7 WHERE `storage_key` = 9;
UPDATE `{prefix}file_item` SET `storage_key` = 7 WHERE `storage_key` = 9;
