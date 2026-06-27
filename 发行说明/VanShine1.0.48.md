# VanShine 1.0.48 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.48.zip`

---

## 版本概述

全面重构文件在线预览，移除体积巨大的 Flyfish Viewer，改用 jsDelivr CDN 轻量方案；优化更新过程为分步进度展示；用户界面统一使用「云端」表述。

---

## 变更内容

### 文件在线预览

- **移除** `assets/vendor/flyfish-viewer/`（约 130MB）及 `assets/js/flyfish-viewer.js`
- **新增** `assets/js/file-preview.js`，按需从 jsDelivr 加载 pdf.js、mammoth、xlsx、marked、highlight.js
- **支持预览：** PDF、Word(.docx)、Excel、Markdown、HTML、PHP、CSS、JS、音频、视频
- **不支持预览：** 压缩包（zip/rar/7z 等）及其他格式 → 仅显示图标与提示
- 预览内容限制在框内，修复 MD/表格等横向溢出问题
- 手机端预览抽屉高度由 75vh 调整为 **80vh**
- 预览区右上角「放大」按钮：在弹窗内扩展预览区域
- 自定义音视频播放器（播放/暂停、进度条、音频波形动画）

### 在线更新

- 更新进行中弹窗改为**分步进度**：从云端下载 → 解压 → 覆盖 → 执行数据库（如有）
- 用户可见文案统一为「云端」，不再显示 Gitee
- **修复** 分步更新在部分环境下无法下载云端资源包、返回 JSON 解析失败的问题

---

## 升级说明

- **无数据库变更**，可从 v1.0.47 直接在线升级
- 升级后首次预览需联网加载 CDN 脚本（pdf.js 等）

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.48/VanShine1.0.48.zip
