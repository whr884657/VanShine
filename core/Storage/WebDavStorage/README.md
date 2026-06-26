# WebDAV 储存（WebDavStorage）

## 基本信息

| 项目 | 值 |
|------|-----|
| 策略 KEY | `7` |
| 驱动类 | `WebDavStorageDriver` |
| 配置类 | `WebDavStorageOptions` |

## 实现原理

1. 使用 **Sabre DAV** 的 `Client` 建立 WebDAV 连接
2. 通过 **league/flysystem-webdav** 的 `WebDAVAdapter` 封装 Flysystem 接口
3. 上传/读取/删除均通过 WebDAV 协议（PUT / GET / DELETE）与远程服务器通信
4. 公开 URL 由 `url` 配置拼接（需 Web 服务器或反向代理将 `url` 映射到 WebDAV 路径）

```
VanShine → WebDavStorageDriver → WebDAVAdapter → Sabre Client → WebDAV 服务器
外链     → configs.url + pathname
```

## 依赖包

| Composer 包 | 用途 |
|-------------|------|
| `league/flysystem-webdav` | WebDAV Flysystem 适配器 |
| `sabre/dav`（间接依赖） | WebDAV 客户端 |

```bash
cd core/Storage/WebDavStorage && composer install
```

## 需要填写的配置

| 配置键 | 常量 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| `url` | `WebDavStorageOptions::URL` | 是 | 对外访问网址（供浏览器直连） | `https://files.example.com` |
| `base_uri` | `WebDavStorageOptions::BASE_URI` | 是 | WebDAV 服务地址（API 连接用） | `https://dav.example.com/remote.php/dav/files/user/` |
| `username` | `WebDavStorageOptions::USERNAME` | 否 | WebDAV 用户名 | `admin` |
| `password` | `WebDavStorageOptions::PASSWORD` | 否 | WebDAV 密码 | `secret` |
| `queries` | — | 否 | URL 后缀参数 | — |

### 配置示例

```php
$configs = array(
    'url'      => 'https://files.example.com',
    'base_uri' => 'https://nextcloud.example.com/remote.php/dav/files/admin/',
    'username' => 'admin',
    'password' => 'your-password',
);
```

## 对接代码示例

```php
require VS_ROOT . '/core/Storage/WebDavStorage/WebDavStorageOptions.php';
require VS_ROOT . '/core/Storage/WebDavStorage/WebDavStorageDriver.php';

$driver = WebDavStorageDriver::fromConfigs($configs);
$driver->write('2024/01/photo.jpg', $fileContents);
$url = WebDavStorageDriver::buildUrl($configs, '2024/01/photo.jpg');
```

## 公开方法一览

| 方法 | 说明 |
|------|------|
| `fromConfigs($configs)` | 创建驱动实例 |
| `writeStream($pathname, $handle)` | 流式上传至 WebDAV |
| `write($pathname, $contents)` | 字符串上传 |
| `read($pathname)` | 从 WebDAV 读取 |
| `delete($pathname)` | 从 WebDAV 删除 |
| `exists($pathname)` | 判断文件是否存在 |
| `buildUrl($configs, $pathname)` | 拼接公开外链 |
| `loadVendor()` | 加载本目录 Composer 依赖 |

## 注意事项

- `base_uri` 是 PHP 连接 WebDAV 的 API 地址，`url` 是用户访问文件的公开地址，两者可以不同
- `base_uri` 通常以 `/` 结尾或不含多余路径，具体取决于 WebDAV 服务（Nextcloud、坚果云、Synology 等）
- 需确保 WebDAV 账号有目标目录的读写权限
- 部分 WebDAV 服务对单文件大小有限制，大文件上传需关注服务端配置

## 常见 WebDAV 服务 base_uri 参考

| 服务 | base_uri 示例 |
|------|---------------|
| Nextcloud | `https://cloud.example.com/remote.php/dav/files/用户名/` |
| 坚果云 | `https://dav.jianguoyun.com/dav/` |
