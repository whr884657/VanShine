# 本地储存（LocalStorage）

## 基本信息

| 项目 | 值 |
|------|-----|
| 策略 KEY | `1` |
| 驱动类 | `LocalStorageDriver` |
| 配置类 | `LocalStorageOptions` |
| 默认物理目录 | 项目根目录 `upload/` |

## 常见疑问

### 已经是本地磁盘了，为什么还要「驱动」？

这里的「驱动」**不是** Windows 里的显卡驱动、打印机驱动，而是 VanShine 储存模块里的**一种对接方式**。

VanShine 同时支持本地、S3、OSS、COS、七牛、蓝奏优享、WebDAV 等多种储存。业务层上传文件时只调同一套接口：

```php
$driver->writeStream('2024/01/photo.jpg', $fileHandle);
$url = SomeDriver::buildUrl($configs, '2024/01/photo.jpg');
```

选「本地储存」就加载 `LocalStorageDriver`，选「阿里云 OSS」就加载 `AliyunOssDriver`，**上层代码不用改**。  
本地驱动做的事很简单：把文件写到服务器磁盘上的 `upload/` 目录，并拼出访问 URL。

### 符号链接（symlink）是干什么的？你的理解对不对？

本地储存涉及两个概念：

| 概念 | 作用 | 示例 |
|------|------|------|
| **物理目录**（`root`） | PHP 写入、读取、删除文件 | `upload/2024/photo.jpg` |
| **对外 URL**（`url`） | 浏览器里看到的图片地址 | `https://example.com/i/2024/photo.jpg` |

**你的理解有一半是对的：**

- 对的部分：对外 URL 的路径（如 `/i/`）可以和真实存放目录（`upload/`）**不是同一个名字**，中间靠符号链接桥接。
- 对的部分：`upload/` 里若还有其他不想公开的内容，只要**没有对应的 URL、也没有链到那个子目录**，用户从外链路径就访问不到。
- 需要补充：符号链接**不是**「假文件」或「假目录」，访问 `/i/photo.jpg` 时读到的仍是 `upload/` 里的真实文件；它**也不能**按单文件做权限开关——只要文件在已公开的 `root` 下且知道完整 URL，就能访问。

**典型场景：** 文件写在 `upload/`，外链却用 `/i/`（更短、更好记）：

```
网站根目录/i  ──symlink──►  upload/
```

保存储存策略时调用 `ensureSymlink()`，会在项目根创建名为 `i` 的链接（名称取自 `url` 的第一段路径）。

**若 `url` 已配置为 `https://example.com/upload`**，且物理目录就是 `upload/`，路径一致时**通常不必再建符号链接**，Web 服务器可直接访问 `/upload/...`。

## 实现原理

1. 使用 Flysystem 读写本地磁盘
2. 物理文件保存在 `root`（默认项目根 `upload/`）
3. 需要时 `ensureSymlink()` 把 `url` 第一段路径映射到 `root`
4. `buildUrl()` 用 `url + 文件相对路径` 生成外链

```
上传：PHP → LocalStorageDriver → 写入 upload/2024/abc.jpg
访问：浏览器 → https://example.com/i/2024/abc.jpg
              → Web 服务器经 /i 符号链接 → upload/2024/abc.jpg
```

## 依赖包

依赖安装在**本目录** `vendor/`，不在项目根目录。

| Composer 包 | 用途 |
|-------------|------|
| `league/flysystem` | 文件系统抽象层 |
| `league/flysystem-local` | 本地磁盘适配器 |

安装命令：

```bash
cd core/Storage/LocalStorage && composer install
```

## 需要填写的配置

| 配置键 | 常量 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| `url` | `LocalStorageOptions::URL` | 是 | 对外访问网址；路径第一段（如 `/i` 里的 `i`）在需要时作为符号链接名 | `https://example.com/i` |
| `root` | `LocalStorageOptions::ROOT` | 否 | 物理储存根目录，留空则使用项目根 `upload/` | 留空即可 |
| `queries` | — | 否 | URL 额外后缀参数 | `?v=1` |

### 配置示例

```php
$configs = array(
    'url'  => 'https://example.com/i',
    'root' => '',  // 默认 upload/
);
```

## 对接代码示例

```php
require VS_ROOT . '/core/Storage/LocalStorage/LocalStorageOptions.php';
require VS_ROOT . '/core/Storage/LocalStorage/LocalStorageDriver.php';

// url 路径与 upload 不一致时：创建符号链接（保存策略时执行一次）
LocalStorageDriver::ensureSymlink($configs);

// 上传
$driver = LocalStorageDriver::fromConfigs($configs);
$driver->writeStream('2024/01/photo.jpg', $fileHandle);

// 外链
$url = LocalStorageDriver::buildUrl($configs, '2024/01/photo.jpg');
// → https://example.com/i/2024/01/photo.jpg

// 删除策略时
LocalStorageDriver::removeSymlink($configs);
```

## 公开方法一览

| 方法 | 说明 |
|------|------|
| `fromConfigs($configs)` | 创建驱动实例 |
| `writeStream($pathname, $handle)` | 流式写入 |
| `write($pathname, $contents)` | 字符串写入 |
| `read($pathname)` | 读取内容 |
| `delete($pathname)` | 删除文件 |
| `exists($pathname)` | 是否存在 |
| `buildUrl($configs, $pathname)` | 拼接外链 |
| `ensureSymlink($configs, $oldUrl)` | 创建/更新符号链接 |
| `removeSymlink($configs)` | 移除符号链接 |
| `defaultRoot()` | 获取默认物理根目录（`upload/`） |

## 注意事项

- 本地上传文件默认保存在项目根 **`upload/`**
- 系统运行时数据（如在线更新临时文件、蓝奏优享元数据）保存在 **`data/`**，与上传目录分开
- 服务器需启用 `symlink` 函数（仅在 url 路径与 `upload` 不一致时需要）
- `url` 的路径段不可与系统保留目录冲突（如 `assets`、`admin`）
- 修改 `url` 时调用 `ensureSymlink($configs, $oldUrl)` 会自动清理旧链接
