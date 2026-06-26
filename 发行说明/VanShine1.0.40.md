# VanShine 1.0.40 发行说明

**发行日期：** 2026-06-26  
**压缩包名称：** `VanShine1.0.40.zip`

---

## 版本概述

本地储存分享链接改回 **upload 目录直链**，移除随机 slug 伪装网关及相关代码。

---

## 主要变更

### 本地储存外链

- **新格式：** `https://你的域名/upload/文件夹/文件名`
- 不再使用 `/{随机字母}/{文件名}` 伪装路径
- 可在储存配置中自定义访问 URL 前缀

### 已删除

- `public-file.php`
- `core/LocalFileServe.php`
- `core/Storage/LocalStorage/public-gateway.php`
- `install/nginx-local-storage.conf.example`
- 配置项 `storage_local_public_slug`
- 根目录 `.htaccess` 中的 slug 转发规则

### 升级处理

- 迁移 `1.0.40.sql` 删除 slug 配置
- 自动清理旧 slug 网关目录（如 `/u/`）
- 批量刷新已有本地文件 `public_url`
- 建议保存一次 **系统设置 → 储存配置 → 本地储存**

---

## 升级说明

- **有数据库结构变更**：`1.0.40.sql`
- 升级后可手动删除站点根目录残留的 `public-file.php`

---

https://gitee.com/xunjinlu/VanShine
