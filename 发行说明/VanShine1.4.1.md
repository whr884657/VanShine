# VanShine 1.4.1 发行说明

**发行日期：** 2026-06-30  
**压缩包名称：** `VanShine1.4.1.zip`  
**版本类型：** 小版本（EdgeOne 域名管理页 UI 与 HTTPS 配置优化）

---

## 变更内容

### 域名管理

- **站点板块**：接入方式、加速区域、DDoS 防护、站点状态与站点选择合并为同一卡片
- **手机布局**：第一行站点下拉；第二行「切换站点」「添加域名」左右各占一半
- **CNAME**：列表中点击即可复制，便于到 DNS 平台解析
- **HTTPS**：对齐官方「已部署 + 配置」；抽屉支持 EdgeOne 免费证书申请（HTTP/DNS 验证）、验证部署、关闭 HTTPS
- **DDoS 防护**：`protect_specified_domains` 等 API 值显示为中文
- **表单**：IPv6 改为分段选择；源站类型改为静态「IP / 域名」；创建域名源站必填
- **UI**：板块间距、字体颜色、电脑端抽屉居中宽度统一优化

### API 对接

- `ApplyFreeCertificate` / `CheckFreeCertificateVerification` / `ModifyHostsCertificate`

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.4.1/VanShine1.4.1.zip
