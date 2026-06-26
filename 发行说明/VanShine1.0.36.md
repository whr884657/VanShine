# VanShine 1.0.36 发行说明

**发行日期：** 2026-06-26  
**压缩包名称：** `VanShine1.0.36.zip`

---

## 版本概述

修复 1.0.35 升级时数据库结构更新失败的问题（`config` 表未加表前缀）。

---

## 问题说明

1.0.35 迁移脚本 `1.0.35.sql` 错误地使用了裸表名 `` `config` ``，未替换为 `` `{prefix}config` ``。  
VanShine 安装时通常配置了表前缀（如 `vs_`），因此执行迁移时会报错：

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'xxx.config' doesn't exist
```

---

## 主要变更

- 修正 `install/migrations/1.0.35.sql`，使用 `{prefix}config`
- `DatabaseMigrator` 执行前校验迁移脚本是否遗漏表前缀
- 若配置项 `storage_local_public_slug` 已存在，自动标记 1.0.35 迁移为已完成

---

## 升级说明

- **有数据库结构变更**：将重试未完成的 `1.0.35.sql`
- 若你已在 1.0.35 遇到「文件已更新，但数据库结构更新失败」，直接升级到 **1.0.36** 即可
- 升级成功后进入 **系统设置 → 储存配置 → 本地储存**，点击保存以部署直链网关

---

https://gitee.com/xunjinlu/VanShine
