# 阿里云 OSS 储存（AliyunOss）

## 基本信息

| 项目 | 值 |
|------|-----|
| 策略 KEY | `3` |
| 驱动类 | `AliyunOssDriver` |
| 配置类 | `AliyunOssOptions` |

## 实现原理

1. 使用 **阿里云 OSS PHP SDK** 的 `OssClient` 建立连接
2. 通过 **zing/flysystem-oss** 的 `OssAdapter` 封装 Flysystem 统一接口
3. 文件上传至指定 Bucket，路径由业务层 `pathname` 决定
4. 外链使用配置的 `url`（通常为 OSS 绑定域名或 CDN 域名）拼接

```
VanShine → AliyunOssDriver → OssAdapter → OssClient → 阿里云 OSS
外链     → configs.url + pathname
```

## 依赖包

| Composer 包 | 用途 |
|-------------|------|
| `zing/flysystem-oss` | OSS Flysystem 适配器 |
| `aliyuncs/oss-sdk-php`（间接依赖） | 阿里云官方 SDK |

```bash
cd core/Storage/AliyunOss && composer install
```

## 需要填写的配置

| 配置键 | 常量 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| `url` | `AliyunOssOptions::URL` | 是 | 对外访问域名（CDN/自定义域名） | `https://img.example.com` |
| `access_key_id` | `AliyunOssOptions::ACCESS_KEY_ID` | 是 | 阿里云 AccessKey ID | `LTAI...` |
| `access_key_secret` | `AliyunOssOptions::ACCESS_KEY_SECRET` | 是 | 阿里云 AccessKey Secret | `xxx...` |
| `endpoint` | `AliyunOssOptions::ENDPOINT` | 是 | OSS 地域节点 | `oss-cn-hangzhou.aliyuncs.com` |
| `bucket` | `AliyunOssOptions::BUCKET` | 是 | Bucket 名称 | `my-oss-bucket` |
| `queries` | — | 否 | URL 后缀（如图片处理参数） | `?x-oss-process=image/resize,w_200` |

### 配置示例

```php
$configs = array(
    'url'               => 'https://img.example.com',
    'access_key_id'     => 'LTAIxxxxxxxx',
    'access_key_secret' => 'xxxxxxxxxxxxxxxx',
    'endpoint'          => 'oss-cn-hangzhou.aliyuncs.com',
    'bucket'            => 'my-oss-bucket',
);
```

## 对接代码示例

```php
require VS_ROOT . '/core/Storage/AliyunOss/AliyunOssOptions.php';
require VS_ROOT . '/core/Storage/AliyunOss/AliyunOssDriver.php';

$driver = AliyunOssDriver::fromConfigs($configs);
$driver->writeStream('2024/01/photo.jpg', $fileHandle);
$url = AliyunOssDriver::buildUrl($configs, '2024/01/photo.jpg');
```

## 公开方法一览

| 方法 | 说明 |
|------|------|
| `fromConfigs($configs)` | 创建驱动实例 |
| `writeStream($pathname, $handle)` | 流式上传至 OSS |
| `write($pathname, $contents)` | 字符串上传 |
| `read($pathname)` | 从 OSS 读取 |
| `delete($pathname)` | 从 OSS 删除 |
| `exists($pathname)` | 判断对象是否存在 |
| `buildUrl($configs, $pathname)` | 拼接 CDN 外链 |
| `loadVendor()` | 加载本目录 Composer 依赖 |

## 注意事项

- `endpoint` 需与 Bucket 所在地域一致，可在阿里云控制台「Bucket 概览」查看
- RAM 子账号需授予 OSS 读写权限
- 若 Bucket 为私有，需在 CDN 或 `url` 侧配置回源鉴权；本模块不负责签名 URL
