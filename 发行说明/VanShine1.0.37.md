# VanShine 1.0.37 发行说明

**发行日期：** 2026-06-26  
**压缩包名称：** `VanShine1.0.37.zip`

---

## 版本概述

修复本地储存对外直链 404，新增上传进度浮层，完善 slug 与 Nginx 配置文档。

---

## 主要变更

### 本地直链 404 修复

- 新增站点根 `public-file.php` 作为统一入口
- 根目录 `.htaccess` 增加规则：`/{单字母}/{文件名}` → `public-file.php`
- **Nginx 环境**必须添加转发规则，示例见 `install/nginx-local-storage.conf.example`
- 升级后请在 **系统设置 → 储存配置 → 本地储存** 重新保存一次

### 上传进度

- 拖拽或选择文件后，右下角弹出上传卡片
- 每个文件独立进度条与状态（上传中 / 完成 / 失败）
- 支持多文件同时上传

### 文档

- `core/Storage/LocalStorage/README.md` 说明 slug 规则：
  - **1 个字符**，从 **a–z** 随机抽取
  - 写入配置后**固定不变**，非循环轮换

---

## 升级说明

- **无数据库结构变更**
- Nginx 站点升级后请配置：

```nginx
location ~ ^/([a-z])/([^/]+)$ {
    rewrite ^/([a-z])/([^/]+)$ /public-file.php?slug=$1&name=$2 last;
}
```

---

https://gitee.com/xunjinlu/VanShine
