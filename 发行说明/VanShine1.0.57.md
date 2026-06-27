# VanShine 1.0.57 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.57.zip`

---

## 版本概述

分享链接与伪静态彻底简化：仅保留站点通用 `try_files`，分享走 `/d/index.php/{token}`，无需 query 参数与 PHP 版本专用配置。

---

## 变更内容

### 分享链接

| 用途 | 格式 |
|------|------|
| 安全提醒 | `{域名}/d/index.php` |
| 分享页 | `{域名}/d/index.php/{32位token}` |
| 下载流 | `{域名}/d/index.php/{token}/stream?file=ID` |

- 旧版 `?token=` 链接自动 **301** 跳转到 PATH 格式
- 不再依赖 `/d/{token}` 伪静态 rewrite

### 伪静态

Nginx **只需**通用规则（与全站一致，PHP 版本由面板自动处理）：

```nginx
location / {
    try_files $uri $uri/ $uri.php$is_args$args;
}
```

- 已删除文档中 `enable-php-82.conf` 等版本号配置
- 已删除 `/d/` 专用 location 示例
- 若曾添加 `/d/` 的 `try_files $uri` 导致源码下载，请删除该规则

---

## 升级说明

- **无数据库变更**
- 升级后请检查 Nginx：仅保留通用伪静态，删除之前为分享单独添加的 `/d/` 规则

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.57/VanShine1.0.57.zip
