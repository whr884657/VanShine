-- ============================================================
-- 文件：install/migrations/1.0.40.sql
-- 作用：移除本地储存随机 slug 直链配置，改回 upload 直链
-- ============================================================

DELETE FROM `{prefix}config` WHERE `key` = 'storage_local_public_slug';
