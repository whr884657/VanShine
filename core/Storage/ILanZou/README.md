# 蓝奏云优享版储存（ILanZou）

## 基本信息

| 项目 | 值 |
|------|-----|
| 策略 KEY | `6` |
| 驱动类 | `ILanZouDriver` |
| 配置类 | `ILanZouOptions` |

## 实现原理

蓝奏云优享版采用 **App API + 七牛直传** 对接：

```
上传：登录 → /7n/getUpToken → upload.qiniup.com → /7n/results 确认
下载：/unproved/file/redirect（AES 签名）→ 302 CDN 直链
删除：/proved/file/delete
```

文件实际上传至蓝奏云在七牛的 bucket `wpanstore-lanzou`，凭证由蓝奏云 API 下发，**无需自备七牛账号**。

```
VanShine → ILanZouDriver → ILanZouClient → apis.ilanzou.com
                              ↓
                        upload.qiniup.com（七牛直传）
外链     → configs.url + pathname（建议配合 VanShine 代理路由）
临时直链 → getTemporaryUrl() 实时获取
```

## 依赖

| 依赖 | 说明 |
|------|------|
| PHP `curl` | HTTP 请求 |
| PHP `openssl` | AES-128-ECB 请求签名 |

无需 `composer install`，无 `vendor/` 目录。

## 需要填写的配置

| 配置键 | 常量 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| `username` | `ILanZouOptions::USERNAME` | 是 | 蓝奏云优享版账号 | `user@example.com` |
| `password` | `ILanZouOptions::PASSWORD` | 是 | 登录密码 | `***` |
| `url` | `ILanZouOptions::URL` | 是 | 对外访问域名 | `https://img.example.com` |
| `folder_id` | `ILanZouOptions::FOLDER_ID` | 否 | 上传目录 ID，根目录为 `0` | `0` |
| `uuid` | `ILanZouOptions::UUID` | 否 | 设备 UUID，留空自动获取 | |
| `token` | `ILanZouOptions::TOKEN` | 否 | appToken，留空自动登录 | |
| `ip` | `ILanZouOptions::IP` | 否 | X-Forwarded-For | |
| `queries` | — | 否 | URL 后缀参数 | |

### 配置示例

```php
$configs = array(
    'username'  => 'your@email.com',
    'password'  => 'your-password',
    'url'       => 'https://img.example.com',
    'folder_id' => '0',
);
```

## 对接代码示例

```php
require VS_ROOT . '/core/Storage/ILanZou/ILanZouOptions.php';
require VS_ROOT . '/core/Storage/ILanZou/ILanZouException.php';
require VS_ROOT . '/core/Storage/ILanZou/ILanZouCrypto.php';
require VS_ROOT . '/core/Storage/ILanZou/ILanZouClient.php';
require VS_ROOT . '/core/Storage/ILanZou/ILanZouDriver.php';

$driver = ILanZouDriver::fromConfigs($configs);
$driver->writeStream('2024/01/photo.jpg', $fileHandle);

// 获取 ILanZou 远程 fileId
$fileId = $driver->getFileId('2024/01/photo.jpg');

// 获取带鉴权的临时 CDN 直链
$directUrl = $driver->getTemporaryUrl('2024/01/photo.jpg');

$url = ILanZouDriver::buildUrl($configs, '2024/01/photo.jpg');
```

## 公开方法一览

| 方法 | 说明 |
|------|------|
| `fromConfigs($configs)` | 创建驱动实例 |
| `writeStream($pathname, $handle)` | 流式上传（含七牛直传 + 秒传） |
| `write($pathname, $contents)` | 字符串上传 |
| `read($pathname)` | 下载文件内容 |
| `delete($pathname)` | 删除远程文件 |
| `exists($pathname)` | 判断本地映射是否存在 |
| `getFileId($pathname)` | 获取 ILanZou fileId |
| `getTemporaryUrl($pathname)` | 获取临时 CDN 直链 |
| `buildUrl($configs, $pathname)` | 拼接对外 URL |

## pathname 与 fileId 映射

上传成功后，驱动在 `data/ilanzou-meta/` 下保存 pathname → fileId 映射。

也支持 pathname 直接使用 `{fileId}.ext` 格式（纯数字文件名）。

## 注意事项

- 上传走七牛 `upload.qiniup.com`，大文件（>8MB）自动分片
- 支持 MD5 秒传（`upToken === -1`）
- `buildUrl()` 返回配置的静态 URL；蓝奏云 CDN 直链带时效，浏览器直连建议使用 `getTemporaryUrl()` 或 VanShine 代理
- `token` 过期时客户端会自动重新登录
