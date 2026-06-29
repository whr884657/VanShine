# VanShine 1.4.5 发行说明

**发行日期：** 2026-06-30  
**压缩包名称：** `VanShine1.4.5.zip`  
**版本类型：** 小版本（HTTPS 证书状态修复）

---

## 变更内容

### 修复 HTTPS 全部显示「申请中」

v1.4.4 在 `eofreecert` 模式下，当 `Certificate.List` 为空（腾讯云 API 文档注明可能返回 null）且未能通过 CertId 匹配默认证书时，会一律显示「申请中」。

本版按 [腾讯云 EO API 数据结构](https://cloud.tencent.com/document/api/1552/80721) 修正：

1. **优先读取** `DescribeAccelerationDomains` 返回的 `Certificate.List[].Status`（`deployed` / `applying` / `processing` / `failed` 等）
2. **按域名匹配** [DescribeDefaultCertificates](https://cloud.tencent.com/document/api/1552/80603) 返回的 `SubjectAltName` / `CommonName`
3. **补充** `ZoneId` 参数与分页拉取默认证书列表
4. **兜底**：证书模式已开启且域名状态为「已生效」时显示「已部署」，与腾讯云控制台一致

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.4.5/VanShine1.4.5.zip
