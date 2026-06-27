-- ============================================================
-- 文件：install/migrations/1.0.47.sql
-- 作用：绑定域名迁入 config.bound_domains，删除 domain 表
-- 说明：domain 表数据迁移由 DatabaseMigrator::applyBoundDomainsMigration 先行执行
-- ============================================================

-- 确保 bound_domains 配置项存在（新安装/升级通用）
INSERT INTO `{prefix}config` (`key`, `value`)
SELECT 'bound_domains', '[]'
WHERE NOT EXISTS (
    SELECT 1 FROM `{prefix}config` WHERE `key` = 'bound_domains'
);

-- 删除已废弃的绑定域名表（数据已迁入 bound_domains）
DROP TABLE IF EXISTS `{prefix}domain`;
