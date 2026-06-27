# VanShine 1.0.49 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.49.zip`

---

## 版本概述

预览能力全面本地化；账号与登录体验优化；修复代码类文件上传问题。

---

## 变更内容

### 在线更新

- **修复 v1.0.48 起在线更新无法下载云端资源包的问题**（PHP 兼容、分步更新 session 与 JSON 响应）

### 安装向导

- 数据库配置步骤**移除数据表前缀输入**，系统固定使用 `vs_` 前缀

### 文件预览

- 预览库内置至 `assets/vendor/preview/`（marked、highlight.js、pdf.js、docx-preview、ExcelJS）
- **图片 / 音频 / 视频**：原生 `<img>` / `<audio>` / `<video>` 渲染，不依赖外部 CDN
- **Word(.docx) / Excel(.xlsx) / Markdown / PDF**：本地 JS 库渲染
- 播放按钮改为 SVG 图标（无中文文字）
- 音频波形动画**仅在播放时**显示
- 预览区右上角按钮改为**浏览器全屏**（Fullscreen API）

### 账号与登录

- 登录支持**用户名或邮箱**
- 账号设置支持**修改用户名**
- 电脑端账号设置改为左右布局；头像说明简化为「输入图片 URL，留空则使用默认头像」

### 文件上传

- 扩展 MIME 类型映射（html、css、js、php、md 等）
- 已知扩展名优先于 `mime_content_type`，避免误判
- `upload/.htaccess` 禁止 upload 目录内脚本执行（不影响上传写入）
- 上传失败时返回更明确的错误信息

---

## 升级说明

- **无数据库变更**，可从 v1.0.48 直接在线升级

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.49/VanShine1.0.49.zip
