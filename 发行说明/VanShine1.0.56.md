# VanShine 1.0.56 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.56.zip`

---

## 版本概述

分享链接全面改为 query 参数格式，修复 Nginx 下 `/d/index.php` 被当作静态文件下载的严重问题；优化文件管理分享入口。

---

## 变更内容

### 分享链接机制（重要）

- **新标准格式**：`https://域名/d/index.php?token={32位token}`
- **下载/预览流**：`/d/index.php?token=...&stream=1&file=ID&download=1`
- 旧版 `/d/{token}` 自动 **301** 跳转到带 `?token=` 的链接
- 公开分享页统一由 `d/index.php` 渲染

### 防止 PHP 源码被下载

- 更新 [`伪静态配置.md`](../伪静态配置.md)：Nginx 必须为 `^/d/.+\.php$` 配置 PHP 解释器
- 若访问 `/d/index.php` 触发下载而非 HTML 页面，请按文档添加 FastCGI 规则

### 文件管理 UI

- 移除顶部「分享此文件夹」按钮
- 文件夹悬停操作：默认显示 **图标库分享图标**，悬停显示「分享」文字

---

## 升级说明

- **无数据库变更**
- **Nginx 用户必做**：按 `伪静态配置.md` 添加 `/d/.+\.php$` 的 PHP 配置块
- 已创建的分享链接仍可用（旧路径会自动跳转）

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.56/VanShine1.0.56.zip
