# Flyfish Viewer 在线预览

VanShine 文件管理集成 [Flyfish Viewer](https://doc.flyfish.dev/) **@file-viewer/web-full@2.1.3**，在后台弹窗内**纯前端**预览 150+ 种文件格式。

## 自托管（已内置）

预览组件**不再依赖外部 CDN**。发行包已包含完整静态资源：

```
assets/vendor/flyfish-viewer/
├── flyfish-file-viewer-web-full.iife.js   # 主入口
├── vendor/                                 # PDF、Office、压缩包等 vendor
├── wasm/                                   # CAD、Typst、SQLite 等 WASM
└── assets/                                 # Worker 脚本
```

脚本按自身 URL 自动定位同目录下的 worker / wasm / 字体，**内网离线可用**。

## 支持范围（节选）

| 分类 | 格式 |
|------|------|
| Office | DOCX / XLSX / PPTX / PDF / OFD / RTF |
| 工程 | DWG / DXF / DWF / 3D / GIS / EDA |
| 知识 | Markdown / Code / Mermaid / PlantUML |
| 媒体 | 图片 / HEIC / 音频 / 视频 / PSD |
| 容器 | ZIP / RAR / 7Z / TAR / GZIP（嵌套预览） |
| 协作 | EML / MSG / XMind / draw.io / EPUB |

## 文件来源

- **同源** `public_url`：直连 Flyfish（PDF 支持 Range）
- **跨域云储存**：`admin/file-stream.php?id=文件ID` 鉴权代理

## 升级 Flyfish 版本

1. `npm pack @file-viewer/web-full@{版本}` 解压 `package/dist/*`
2. 覆盖 `assets/vendor/flyfish-viewer/`
3. 更新 `admin/files.php` 与本文档中的版本号

## 相关文件

- `assets/js/flyfish-viewer.js`
- `admin/file-stream.php`
- `core/StorageManager.php` — `streamFileItem()`
