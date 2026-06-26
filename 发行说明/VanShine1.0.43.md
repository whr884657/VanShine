# VanShine 1.0.43 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.43.zip`

---

## 版本概述

文件管理全面接入 **Flyfish Viewer**，在后台弹窗内纯前端在线预览 150+ 种文件格式，无需服务端转码。

---

## 主要变更

### Flyfish Viewer 在线预览

- 点击或**双击**文件 → 预览弹窗内直接打开
- 集成 `@file-viewer/web-full@2.1.3`（懒加载，按需拉取 renderer）
- 支持 Office（Word/Excel/PPT/PDF/OFD）、CAD、压缩包嵌套预览、Markdown、代码、音视频、PSD、邮件、EPUB 等
- 预览区加宽，工具栏支持缩放、搜索、打印等（由 Flyfish 提供）

### 文件访问

- 同源文件使用 `public_url` 直连（PDF 渐进加载）
- 跨域云储存经 `admin/file-stream.php` 后台鉴权代理
- 本地储存代理支持 **HTTP Range**，大 PDF 体验更好

### 文档

- 新增 `docs/flyfish-viewer.md`（接入说明与内网自托管指引）

---

## 升级说明

- **无数据库结构变更**
- 升级后 **Ctrl+F5** 强刷后台
- 预览组件默认从 jsDelivr 加载；内网环境请参考文档自托管静态资源

---

https://gitee.com/xunjinlu/VanShine
