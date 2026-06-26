# 腾讯云 COS 储存（TencentCos）

## 基本信息

| 项目 | 值 |
|------|-----|
| 策略 KEY | `4` |
| 驱动类 | `TencentCosDriver` |
| 配置类 | `TencentCosOptions` |

## 实现原理

1. 使用 **overtrue/flysystem-cos** 的 `CosAdapter` 对接腾讯云 COS
2. 底层通过 **overtrue/qcloud-cos-client** 调用 COS API
3. 配置项包括 app_id、secret_id、secret_key、region、bucket
4. 外链由 `url` + 文件路径拼接

```
VanShine → TencentCosDriver → CosAdapter → qcloud-cos-client → 腾讯云 COS
外链     → configs.url + pathname
```

## 依赖包

| Composer 包 | 用途 |
|-------------|------|
| `overtrue/flysystem-cos` | COS Flysystem 适配器 |
| `overtrue/qcloud-cos-client`（间接依赖） | 腾讯云 COS 客户端 |

```bash
cd core/Storage/TencentCos && composer install
```

## 需要填写的配置

| 配置键 | 常量 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| `url` | `TencentCosOptions::URL` | 是 | 对外访问域名（CDN/自定义域名） | `https://cos.example.com` |
| `app_id` | `TencentCosOptions::APP_ID` | 是 | 腾讯云 AppId | `1250000000` |
| `secret_id` | `TencentCosOptions::SECRET_ID` | 是 | 密钥 SecretId | `AKID...` |
| `secret_key` | `TencentCosOptions::SECRET_KEY` | 是 | 密钥 SecretKey | `xxx...` |
| `region` | `TencentCosOptions::REGION` | 是 | 所属地域 | `ap-guangzhou` |
| `bucket` | `TencentCosOptions::BUCKET` | 是 | 储存桶名称（不含 AppId 后缀） | `my-bucket` |
| `queries` | — | 否 | URL 后缀参数 | — |

### 配置示例

```php
$configs = array(
    'url'        => 'https://cos.example.com',
    'app_id'     => '1250000000',
    'secret_id'  => 'AKIDxxxxxxxx',
    'secret_key' => 'xxxxxxxxxxxxxxxx',
    'region'     => 'ap-guangzhou',
    'bucket'     => 'my-bucket',
);
```

## 对接代码示例

```php
require VS_ROOT . '/core/Storage/TencentCos/TencentCosOptions.php';
require VS_ROOT . '/core/Storage/TencentCos/TencentCosDriver.php';

$driver = TencentCosDriver::fromConfigs($configs);
$driver->write('2024/01/photo.jpg', $fileContents);
$url = TencentCosDriver::buildUrl($configs, '2024/01/photo.jpg');
```

## 公开方法一览

| 方法 | 说明 |
|------|------|
| `fromConfigs($configs)` | 创建驱动实例 |
| `writeStream($pathname, $handle)` | 流式上传至 COS |
| `write($pathname, $contents)` | 字符串上传 |
| `read($pathname)` | 从 COS 读取 |
| `delete($pathname)` | 从 COS 删除 |
| `exists($pathname)` | 判断对象是否存在 |
| `buildUrl($configs, $pathname)` | 拼接 CDN 外链 |
| `loadVendor()` | 加载本目录 Composer 依赖 |

## 注意事项

- `region` 需与 Bucket 创建地域一致，参见[腾讯云地域列表](https://cloud.tencent.com/document/product/436/6224)
- 子账号密钥需具备 COS 相应存储桶读写权限
- 完整 Bucket 名称通常为 `{bucket}-{app_id}`，适配器内部会按 SDK 规则处理
