# AWS S3 储存（AwsS3）

## 基本信息

| 项目 | 值 |
|------|-----|
| 策略 KEY | `2` |
| 驱动类 | `AwsS3Driver` |
| 配置类 | `AwsS3Options` |

## 实现原理

1. 使用 **AWS SDK for PHP** 的 `S3Client` 建立 S3 连接
2. 通过 **League Flysystem** 的 `AwsS3V3Adapter` 封装统一读写接口
3. 上传时调用 `Filesystem::writeStream()` 完成对象写入
4. 外链由管理员配置的 `url`（通常为 CloudFront 或桶域名）拼接，**不由 SDK 自动生成**

```
VanShine → AwsS3Driver → AwsS3V3Adapter → S3Client → AWS S3 Bucket
外链     → configs.url + pathname
```

## 依赖包

| Composer 包 | 用途 |
|-------------|------|
| `league/flysystem-aws-s3-v3` | S3 Flysystem 适配器 |
| `aws/aws-sdk-php`（间接依赖） | AWS 官方 SDK |

```bash
cd core/Storage/AwsS3 && composer install
```

## 需要填写的配置

| 配置键 | 常量 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| `url` | `AwsS3Options::URL` | 是 | 对外访问域名（CDN/桶域名） | `https://cdn.example.com` |
| `access_key_id` | `AwsS3Options::ACCESS_KEY_ID` | 是 | AWS Access Key ID | `AKIA...` |
| `secret_access_key` | `AwsS3Options::SECRET_ACCESS_KEY` | 是 | AWS Secret Access Key | `wJalr...` |
| `endpoint` | `AwsS3Options::ENDPOINT` | 否 | 自定义 Endpoint（兼容 S3 协议的服务） | 留空使用 AWS 默认 |
| `region` | `AwsS3Options::REGION` | 否 | AWS 区域 | `us-east-1` |
| `bucket` | `AwsS3Options::BUCKET` | 是 | 储存桶名称 | `my-bucket` |
| `queries` | — | 否 | URL 后缀参数 | — |

### 配置示例

```php
$configs = array(
    'url'               => 'https://cdn.example.com',
    'access_key_id'     => 'AKIAIOSFODNN7EXAMPLE',
    'secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'endpoint'          => '',
    'region'            => 'us-east-1',
    'bucket'            => 'my-bucket',
);
```

## 对接代码示例

```php
require VS_ROOT . '/core/Storage/AwsS3/AwsS3Options.php';
require VS_ROOT . '/core/Storage/AwsS3/AwsS3Driver.php';

$driver = AwsS3Driver::fromConfigs($configs);
$driver->write('2024/01/photo.jpg', $fileContents);
$url = AwsS3Driver::buildUrl($configs, '2024/01/photo.jpg');
```

## 公开方法一览

| 方法 | 说明 |
|------|------|
| `fromConfigs($configs)` | 创建驱动实例 |
| `writeStream($pathname, $handle)` | 流式上传至 S3 |
| `write($pathname, $contents)` | 字符串上传 |
| `read($pathname)` | 从 S3 读取 |
| `delete($pathname)` | 从 S3 删除 |
| `exists($pathname)` | 判断对象是否存在 |
| `buildUrl($configs, $pathname)` | 拼接 CDN 外链 |
| `loadVendor()` | 加载本目录 Composer 依赖 |

## 注意事项

- `url` 需指向已公开读权限的 CDN 或桶域名，并确保与 `bucket` 中文件路径一致
- 使用 MinIO 等 S3 兼容服务时，填写对应 `endpoint` 和 `region`
- IAM 用户需具备目标 Bucket 的 `s3:PutObject`、`s3:GetObject`、`s3:DeleteObject` 权限
