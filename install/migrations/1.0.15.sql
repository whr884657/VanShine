-- 文件：install/migrations/1.0.15.sql
-- 作用：v1.0.15 数据库增量迁移（更新时自动执行）
-- 说明：{prefix} 为表前缀占位符

INSERT IGNORE INTO `{prefix}config` (`key`, `value`) VALUES ('site_logo', '');
