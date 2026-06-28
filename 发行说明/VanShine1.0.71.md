# VanShine 1.0.71 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.71.zip`

---

## 变更内容

### EdgeOne API 修复

- **DescribeContentQuota**：空参数请求体由 `[]` 修正为 `{}`，修复「请求内容不是合法的 Json 格式」错误
- **DescribeBillingData**：补充必填参数 `Interval`（day）与 `ZoneIds`（当前站点或 `*`）

---

## 升级说明

- **无数据库变更**：覆盖文件即可，保留 `config/database.php`

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.71/VanShine1.0.71.zip
