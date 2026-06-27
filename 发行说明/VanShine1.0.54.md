# VanShine 1.0.54 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.54.zip`

---

## 版本概述

修复分享短链接 404（含 Nginx 伪静态文档）、分享/预览 UI 与移动端体验；后台文件模块目录结构调整。

---

## 变更内容

### 分享短链接 404 与伪静态

- 根目录 `.htaccess` 增加 `/d/{token}` 路由兜底
- 新增 `ShareRouter`：从 `REQUEST_URI` / `PATH_INFO` 解析 token
- **新增 [`伪静态配置.md`](../伪静态配置.md)**：Nginx 须在通用 `try_files` 前增加 `/d/` 专用规则
- README 增加伪静态配置说明
- **链接格式**：`https://域名/d/{32位token}`（预览/下载走 `/d/{token}/stream?file=ID`）

### 分享管理与预览 UX

- 操作按钮改为圆角矩形（`.vs-btn--rect`），不再使用圆形 pill
- 分享管理列表/卡片显示**储存来源**（如 `1. 本地储存`）
- 手机端分享卡片重新设计：短链、标签、全宽复制与编辑/删除
- 预览弹窗：电脑端可滚动查看直链、替换与下载；预览区限高避免遮挡
- 「文件信息与操作」标题改用 SVG 图标（移除 emoji）
- 预览元数据增加「储存」字段
- 创建分享弹窗 z-index 高于预览层；多条分享可展开查看

### 后台目录结构

- `admin/files/manage.php` — 文件管理
- `admin/files/shares.php` — 分享管理
- 已移除旧路径 `admin/files.php`、`admin/shares.php`（解压覆盖即可，无兼容跳转）

---

## 升级说明

- **无数据库变更**，可从 v1.0.53 直接在线升级
- **Nginx 用户**：升级后请按 `伪静态配置.md` 追加 `/d/` rewrite 规则，否则分享链接仍可能 404

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.54/VanShine1.0.54.zip
