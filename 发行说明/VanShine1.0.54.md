# VanShine 1.0.54 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.54.zip`

---

## 版本概述

修复分享短链接 404、弹窗遮挡与移动端分享管理体验；后台文件模块目录结构调整（与 CDN 命名方式一致）。

---

## 变更内容

### 分享短链接 404 修复

- 根目录 `.htaccess` 增加 `/d/{token}` 路由兜底
- `d/.htaccess` 跳过已存在文件，避免拦截 PHP 测试文件
- 新增 `ShareRouter`：从 `REQUEST_URI` / `PATH_INFO` 解析 token
- **链接格式**：`https://域名/d/{32位token}`（预览/下载走 `/d/{token}/stream?file=ID`）

### 预览与分享 UX

- 创建分享弹窗 z-index 高于预览层，不再被遮挡
- 同一文件多条分享：显示「已创建 N 条」可展开查看
- 过期时间：date + time 组合控件
- 「文件信息与操作」折叠区样式优化

### 分享管理（手机端）

- ≤768px 自动切换为卡片布局

### 后台目录结构

- `admin/files/manage.php` — 文件管理
- `admin/files/shares.php` — 分享管理
- 已移除旧路径 `admin/files.php`、`admin/shares.php`（解压覆盖即可，无兼容跳转）

---

## 升级说明

- **无数据库变更**，可从 v1.0.53 直接在线升级

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.54/VanShine1.0.54.zip
