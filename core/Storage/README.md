# VanShine 储存模块（core/Storage）

VanShine 文件储存模块采用**分驱动独立目录**架构：每种储存方式拥有独立文件夹，内含对接代码、配置键定义、Composer 依赖（`vendor/`）。业务层通过统一的驱动接口调用，底层基于 Flysystem 或各平台原生 API 完成读写。

## 已对接的七种储存

| 目录 | 策略 KEY | 名称 | 说明文档 |
|------|----------|------|----------|
| [LocalStorage](./LocalStorage/README.md) | `1` | 本地 | 服务器磁盘 + 符号链接 |
| [AwsS3](./AwsS3/README.md) | `2` | AWS S3 | Amazon S3 对象存储 |
| [AliyunOss](./AliyunOss/README.md) | `3` | 阿里云 OSS | 阿里云对象存储 |
| [TencentCos](./TencentCos/README.md) | `4` | 腾讯云 COS | 腾讯云对象存储 |
| [QiniuKodo](./QiniuKodo/README.md) | `5` | 七牛云 Kodo | 七牛对象存储 |
| [ILanZou](./ILanZou/README.md) | `6` | 蓝奏云优享版 | 蓝奏优享 App API + 七牛直传 |
| [WebDavStorage](./WebDavStorage/README.md) | `7` | WebDAV | WebDAV 协议远程存储 |

## 目录结构约定

每个驱动目录结构如下：

```
{DriverName}/
├── README.md              # 本驱动的对接说明（本文档体系）
├── {DriverName}Options.php   # 配置键常量
├── {DriverName}Driver.php    # 对接实现（Flysystem 读写）
├── composer.json          # 本驱动专属依赖声明
├── composer.lock
└── vendor/                # 依赖安装目录（不放在项目根目录）
```

## 统一对接接口

所有 `*Driver.php` 提供相同的方法签名，业务层可按策略类型统一调用：

| 方法 | 作用 |
|------|------|
| `fromConfigs(array $configs)` | 根据配置创建驱动实例 |
| `writeStream($pathname, $handle)` | 流式上传 |
| `write($pathname, $contents)` | 字符串上传 |
| `read($pathname)` | 读取文件 |
| `delete($pathname)` | 删除文件 |
| `exists($pathname)` | 判断是否存在 |
| `buildUrl($configs, $pathname)` | 拼接公开访问 URL |

**本地储存额外方法：**

| 方法 | 作用 |
|------|------|
| `ensureSymlink($configs, $oldUrl)` | 保存策略时创建 public 符号链接 |
| `removeSymlink($configs)` | 删除策略时移除符号链接 |

## 通用配置项

除各驱动专有凭证外，所有云储存/远程储存均支持：

| 配置键 | 必填 | 说明 |
|--------|------|------|
| `url` | 是 | 对外访问域名（CDN 或桶域名），用于生成文件外链 |
| `queries` | 否 | URL 后缀参数，如 `?x-oss-process=...` |

公开 URL 拼接规则：

```
{url}/{pathname}{queries}
```

示例：`https://cdn.example.com` + `2024/01/abc.jpg` → `https://cdn.example.com/2024/01/abc.jpg`

## 引入方式

按需 require 对应驱动的 Options 与 Driver，**无需**在项目根目录安装 Composer：

```php
require VS_ROOT . '/core/Storage/AwsS3/AwsS3Options.php';
require VS_ROOT . '/core/Storage/AwsS3/AwsS3Driver.php';

$driver = AwsS3Driver::fromConfigs($configs);
$driver->writeStream('2024/01/photo.jpg', $fileHandle);
$url = AwsS3Driver::buildUrl($configs, '2024/01/photo.jpg');
```

驱动实例化时会自动加载本目录下的 `vendor/autoload.php`。

## 依赖安装

首次部署或更新依赖时，在**各驱动目录内**分别执行：

```bash
cd core/Storage/LocalStorage   && composer install
cd core/Storage/AwsS3          && composer install
cd core/Storage/AliyunOss      && composer install
cd core/Storage/TencentCos     && composer install
cd core/Storage/QiniuKodo      && composer install
cd core/Storage/ILanZou        # 无需 composer install（纯 PHP）
cd core/Storage/WebDavStorage  && composer install
```

## 环境要求

- PHP **8.0+**（Flysystem 3.x 要求）
- PHP 扩展：`curl`（云储存 HTTP 请求）、`fileinfo`（MIME 检测）
- 本地储存额外需要：`readlink`、`symlink` 函数可用

## 实现原理概览

```
上传请求
    ↓
业务层选择策略 KEY + configs
    ↓
*Driver::fromConfigs($configs)
    ↓
loadVendor() → 加载本目录 vendor/autoload.php
    ↓
构建 Flysystem Adapter 或平台 API 客户端
    ↓
Filesystem::writeStream / read / delete
    ↓
buildUrl() 生成外链（url + pathname + queries）
```

## 维护说明

- 修改某驱动对接逻辑：仅编辑对应目录下的 `*Driver.php`
- 升级某驱动依赖：进入该目录修改 `composer.json` 后执行 `composer update`
- 新增储存驱动：在 `core/Storage/` 下新建目录，遵循相同文件结构，并在本 README 补充索引

## 各驱动详细文档

- [本地储存 LocalStorage](./LocalStorage/README.md)
- [AWS S3](./AwsS3/README.md)
- [阿里云 OSS](./AliyunOss/README.md)
- [腾讯云 COS](./TencentCos/README.md)
- [七牛云 Kodo](./QiniuKodo/README.md)
- [蓝奏云优享版 ILanZou](./ILanZou/README.md)
- [WebDAV](./WebDavStorage/README.md)
