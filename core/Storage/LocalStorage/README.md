# 本地储存（LocalStorage）

## 基本信息

| 项目 | 值 |
|------|-----|
| 策略 KEY | `1` |
| 驱动类 | `LocalStorageDriver` |
| 配置类 | `LocalStorageOptions` |
| 默认物理目录 | 项目根目录 `upload/` |
| 默认对外 URL | `{站点域名}/upload` |

## 对外链接

本地文件分享链接为**直链**，格式：

```
{访问 URL}/{文件夹相对路径}/{文件名}
```

示例：

```
https://example.com/upload/本地/1.png
```

- 物理文件位于 `upload/本地/1.png`
- 链接与物理路径一致，Web 服务器需允许直接访问 `upload/` 目录
- 可在储存配置中自定义 **访问 URL**（`storage_local_url`），留空则默认为 `{站点域名}/upload`

## 实现原理

1. 使用 Flysystem 读写本地磁盘（默认 `upload/`）
2. `buildUrl()` 拼接 `{url}/{pathname}` 生成外链
3. 无需符号链接或 PHP 网关

```
上传：PHP → LocalStorageDriver → 写入 upload/本地/1.png
访问：浏览器 → https://example.com/upload/本地/1.png
```

## 依赖包

```bash
cd core/Storage/LocalStorage && composer install
```

| Composer 包 | 用途 |
|-------------|------|
| `league/flysystem` | 文件系统抽象层 |
| `league/flysystem-local` | 本地磁盘适配器 |

## 配置项

| 配置键 | 必填 | 说明 |
|--------|------|------|
| `storage_local_enabled` | 是 | 勾选启用 |
| `storage_local_url` | 否 | 对外访问 URL 前缀，留空为 `{域名}/upload` |
| `storage_local_root` | 否 | 物理目录，留空为 `upload/` |

## 公开方法

| 方法 | 说明 |
|------|------|
| `buildUrl($configs, $pathname)` | 生成直链 URL |
| `defaultPublicUrl()` | 默认 `{域名}/upload` |
| `defaultRoot()` | 默认物理目录 |
| `refreshStoredPublicUrls()` | 批量刷新已有文件外链 |
| `cleanupLegacyGateway()` | 清理旧版 slug 网关（升级用） |

## 注意事项

- 确保 Web 服务器对 `upload/` 有读取权限且未被 PHP 禁止直接访问
- 升级自 1.0.35–1.0.39 后，保存一次本地储存设置可刷新全部外链
- 可手动删除站点根目录下的 `public-file.php` 及旧 slug 目录（如 `/u/`）
