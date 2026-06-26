# 七牛云 Kodo 储存（QiniuKodo）

## 基本信息

| 项目 | 值 |
|------|-----|
| 策略 KEY | `5` |
| 驱动类 | `QiniuKodoDriver` |
| 配置类 | `QiniuKodoOptions` |

## 实现原理

1. 使用 **overtrue/flysystem-qiniu** 的 `QiniuAdapter` 对接七牛云
2. 底层通过 **qiniu/php-sdk** 完成上传、读取、删除
3. 构造适配器时传入 `access_key`、`secret_key`、`bucket`，以及 `url` 作为 CDN 域名
4. 公开访问 URL 使用绑定的 CDN 域名拼接

```
VanShine → QiniuKodoDriver → QiniuAdapter → qiniu/php-sdk → 七牛云 Kodo
外链     → configs.url + pathname
```

## 依赖包

| Composer 包 | 用途 |
|-------------|------|
| `overtrue/flysystem-qiniu` | 七牛 Flysystem 适配器 |
| `qiniu/php-sdk`（间接依赖） | 七牛官方 SDK |

```bash
cd core/Storage/QiniuKodo && composer install
```

## 需要填写的配置

| 配置键 | 常量 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| `url` | `QiniuKodoOptions::URL` | 是 | 空间绑定的 CDN 加速域名 | `https://cdn.example.com` |
| `access_key` | `QiniuKodoOptions::ACCESS_KEY` | 是 | 七牛 AccessKey | `xxx...` |
| `secret_key` | `QiniuKodoOptions::SECRET_KEY` | 是 | 七牛 SecretKey | `xxx...` |
| `bucket` | `QiniuKodoOptions::BUCKET` | 是 | 存储空间名称 | `my-kodo-bucket` |
| `queries` | — | 否 | URL 后缀参数 | `?imageView2/2/w/200` |

### 配置示例

```php
$configs = array(
    'url'        => 'https://cdn.example.com',
    'access_key' => 'your-access-key',
    'secret_key' => 'your-secret-key',
    'bucket'     => 'my-kodo-bucket',
);
```

## 对接代码示例

```php
require VS_ROOT . '/core/Storage/QiniuKodo/QiniuKodoOptions.php';
require VS_ROOT . '/core/Storage/QiniuKodo/QiniuKodoDriver.php';

$driver = QiniuKodoDriver::fromConfigs($configs);
$driver->writeStream('2024/01/photo.jpg', $fileHandle);
$url = QiniuKodoDriver::buildUrl($configs, '2024/01/photo.jpg');
```

## 公开方法一览

| 方法 | 说明 |
|------|------|
| `fromConfigs($configs)` | 创建驱动实例 |
| `writeStream($pathname, $handle)` | 流式上传至七牛 |
| `write($pathname, $contents)` | 字符串上传 |
| `read($pathname)` | 从七牛读取 |
| `delete($pathname)` | 从七牛删除 |
| `exists($pathname)` | 判断文件是否存在 |
| `buildUrl($configs, $pathname)` | 拼接 CDN 外链 |
| `loadVendor()` | 加载本目录 Composer 依赖 |

## 注意事项

- `url` 必须是已在七牛控制台绑定到该存储空间的加速域名
- AccessKey/SecretKey 在七牛「密钥管理」中获取
- 存储空间需与 `bucket` 名称完全一致（区分区域）
