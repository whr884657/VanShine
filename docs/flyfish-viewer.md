# Flyfish Viewer 在线预览

VanShine 文件管理集成 [Flyfish Viewer](https://doc.flyfish.dev/)，在后台弹窗内**纯前端**预览 150+ 种文件格式，无需服务端转码。

## 支持范围（节选）

| 分类 | 格式 |
|------|------|
| Office | DOCX / XLSX / PPTX / PDF / OFD / RTF / OpenDocument |
| 工程 | DWG / DXF / DWF / 3D / GIS / EDA |
| 知识 | Markdown / Code / Mermaid / PlantUML / Git patch |
| 媒体 | 图片 / HEIC / 音频 / 视频 / 字体 / PSD |
| 容器 | ZIP / RAR / 7Z / TAR / GZIP（含嵌套预览） |
| 协作 | EML / MSG / XMind / draw.io / EPUB |

完整格式矩阵见官方文档。

## 工作原理

1. 点击或双击文件 → 打开预览弹窗
2. 懒加载 `@file-viewer/web-full` IIFE（jsDelivr CDN，版本 **2.1.3**）
3. 按扩展名异步加载 renderer / worker / wasm
4. 同源 `public_url` 直连；跨域云储存经 `admin/file-stream.php` 鉴权代理

## 内网自托管

1. 安装 `@file-viewer/web-full@2.1.3`
2. 执行 `npx file-viewer-copy-assets ./assets/vendor/flyfish-viewer`
3. 修改 `admin/files.php` 中 `VS_FLYFISH_VIEWER.scriptUrl` 为自托管地址

## 相关文件

- `assets/js/flyfish-viewer.js`
- `admin/file-stream.php`
- `core/StorageManager.php` — `streamFileItem()`
