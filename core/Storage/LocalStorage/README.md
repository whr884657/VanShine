# 本地储存（LocalStorage）

## 基本信息

| 项目 | 值 |
|------|-----|
| 策略 KEY | `1` |
| 驱动类 | `LocalStorageDriver` |
| 配置类 | `LocalStorageOptions` |
| 默认物理目录 | 项目根目录 `upload/` |

## 对外直链机制（重要）

本地文件**不会**把真实路径 `upload/子目录/文件名` 暴露给外部。对外 URL 固定为：

```
https://你的域名/{slug}/{文件名}
```

示例：`https://example.com/u/3.jpg`

| 部分 | 说明 |
|------|------|
| `{slug}` | **单个随机小写字母** `a`–`z`，首次启用/保存时生成并写入配置 `storage_local_public_slug`，之后保持不变 |
| `{文件名}` | 上传时按全局命名规则生成的 `stored_name`（如 `3.jpg`），**不含** upload 子目录 |

### slug 随机规则

- **长度：固定 1 个字符**
- **字符集：`a`–`z` 共 26 个字母**
- **生成方式：首次需要时从 26 个字母中随机抽取一个，写入数据库后持久保存**
- **不会循环轮换**：同一站点 slug 不变，除非手动清空配置并重新保存储存设置（会部署新网关并刷新外链）

### 访问流程

```
浏览器请求  /u/3.jpg
    ↓
Web 服务器转发到  public-file.php?slug=u&name=3.jpg
    ↓
LocalFileServe 校验 slug → 按 stored_name 查库 → 读取 upload/ 下真实文件并输出
```

物理文件仍在 `upload/某文件夹/3.jpg`，外链中**不出现**文件夹路径。

## Web 服务器配置

### Apache

项目根 `.htaccess` 已包含规则（需启用 `mod_rewrite` 且 `AllowOverride` 允许）：

```apache
RewriteRule ^([a-z])/([^/]+)$ public-file.php?slug=$1&name=$2 [L,QSA]
```

保存「本地储存」设置时，还会在 `/{slug}/` 部署备用网关 `index.php`（Apache 子目录访问时使用）。

### Nginx

**Nginx 不读取 `.htaccess`**，必须在站点配置中加入（示例见 `install/nginx-local-storage.conf.example`）：

```nginx
location ~ ^/([a-z])/([^/]+)$ {
    rewrite ^/([a-z])/([^/]+)$ /public-file.php?slug=$1&name=$2 last;
}
```

修改后执行 `nginx -t && nginx -s reload`。若站点 404，优先检查此项。

## 实现原理

1. 使用 Flysystem 读写本地磁盘（默认 `upload/`）
2. `buildUrl()` 生成 `{站点域名}/{slug}/{stored_name}`
3. `ensureSymlink()` 部署 `public-file.php` 路由所需网关，并写入 slug 配置
4. `LocalFileServe` 按 `stored_name` 查 `file_item` 表定位物理路径

```
上传：PHP → LocalStorageDriver → 写入 upload/本地/3.jpg
外链：https://example.com/u/3.jpg
访问：public-file.php → 查库 → 读取 upload/本地/3.jpg
```

## 依赖包

依赖安装在**本目录** `vendor/`：

```bash
cd core/Storage/LocalStorage && composer install
```

| Composer 包 | 用途 |
|-------------|------|
| `league/flysystem` | 文件系统抽象层 |
| `league/flysystem-local` | 本地磁盘适配器 |

## 需要填写的配置

| 配置键 | 必填 | 说明 |
|--------|------|------|
| `storage_local_enabled` | 是 | 勾选启用 |
| `storage_local_url` | 否 | 留空则自动为 `{域名}/{slug}` |
| `storage_local_root` | 否 | 物理目录，留空为 `upload/` |
| `storage_local_public_slug` | 自动 | 随机单字母，勿手动修改 |

## 对接代码示例

```php
require VS_ROOT . '/core/Storage/LocalStorage/LocalStorageOptions.php';
require VS_ROOT . '/core/Storage/LocalStorage/LocalStorageDriver.php';

$configs = StorageRegistry::loadDriverConfigs(1);

// 保存储存策略时：部署直链网关
LocalStorageDriver::ensureSymlink($configs);

// 上传
$driver = LocalStorageDriver::fromConfigs($configs);
$driver->writeStream('本地/3.jpg', $fileHandle);

// 外链（不含 upload 路径）
$url = LocalStorageDriver::buildUrl($configs, '本地/3.jpg');
// → https://example.com/u/3.jpg
```

## 公开方法一览

| 方法 | 说明 |
|------|------|
| `publicSlug($create)` | 获取/生成 slug（单字母 a–z） |
| `defaultPublicUrl()` | `{域名}/{slug}` |
| `buildUrl($configs, $pathname)` | 生成对外 URL |
| `ensureSymlink($configs, $oldSlug)` | 部署直链网关 |
| `ensureSymlinkIfMissing($configs)` | 上传前检查并部署 |
| `refreshStoredPublicUrls()` | 批量刷新已有文件外链 |
| `resolveRootPath($configs)` | 物理根目录 |

## 注意事项

- 升级或首次启用后，请在 **系统设置 → 储存配置 → 本地储存** 点一次 **保存**
- Nginx 环境必须配置 `public-file.php` 转发，否则外链 404
- `slug` 不可与系统目录冲突（如 `admin`、`assets`）；随机单字母概率极低
- 修改 slug 需重新保存储存设置，并会刷新已有本地文件外链
