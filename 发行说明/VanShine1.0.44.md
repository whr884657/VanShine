# VanShine 1.0.44 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.44.zip`

---

## 版本概述

Flyfish Viewer 预览组件由远程 CDN 改为**项目内置自托管**，内网/离线环境无需外网即可预览 150+ 种格式。

---

## 主要变更

### 本地自托管 Flyfish Viewer

- 内置 `@file-viewer/web-full@2.1.3` 至 `assets/vendor/flyfish-viewer/`
- 主脚本：`flyfish-file-viewer-web-full.iife.js`
- 含 PDF/Office/压缩包/CAD/Typst 等所需的 worker、wasm、字体、vendor 资源
- **移除 jsDelivr CDN 依赖**

### 说明

- 发行包体积因内置预览资源而增大（约 +130MB 解压后）
- 升级后 **Ctrl+F5** 强刷后台即可

---

## 升级说明

- **无数据库结构变更**
- 在线更新或下载 ZIP 覆盖（保留 `config/database.php`）

---

https://gitee.com/xunjinlu/VanShine
