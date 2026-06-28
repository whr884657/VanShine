# VanShine 1.0.73 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.73.zip`

---

## 变更内容

### EdgeOne 清理

- 删除 `admin/cdn/edgeone/monitor.php`（不再保留跳转兼容页）
- 删除 `admin/cdn/edgeone.php`（冗余重定向入口，侧边栏已直连 `index.php`）
- 套餐计费请使用 `/admin/cdn/edgeone/billing.php`

---

## 升级说明

- **无数据库变更**：覆盖文件即可，保留 `config/database.php`

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.73/VanShine1.0.73.zip
