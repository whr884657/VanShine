# VanShine 1.0.85 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.0.85.zip`

---

## 变更内容

### 修复腾讯云 COS 文件上传失败

**根因（1.0.69 共用密钥引入）：**

1. 保存 **CDN / EdgeOne** 设置时，会把 EdgeOne 的 Region **镜像覆盖** COS 桶地域，导致 COS API 请求地域与 Bucket 不一致，上传失败。
2. 保存设置时 **空 SecretId/SecretKey** 会写入数据库，可能清空已有密钥。
3. COS 驱动优先读 `storage_cos_secret_id` 旧字段，未始终使用通用密钥。

**修复：**

- **SecretId / SecretKey**：COS 与 EdgeOne 继续共用；空值不再覆盖已有密钥。
- **Region**：COS 使用 `storage_cos_region`（桶地域），EdgeOne 使用 `cdn_edgeone_region`（API 地域），**互不覆盖**。
- 文件上传、连接测试统一经 `TencentCloudConfig` 读取有效凭证。

---

## 升级说明

- **无数据库变更**：覆盖文件即可
- 升级后请在 **系统设置 → 储存配置 → 腾讯云 COS** 确认 **地域 Region** 与 Bucket 所在地域一致

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.85/VanShine1.0.85.zip
