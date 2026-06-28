# VanShine 1.0.69 发行说明

**发行日期：** 2026-06-28  
**压缩包名称：** `VanShine1.0.69.zip`

---

## 变更内容

### EdgeOne CDN 后台

- CDN 菜单展开 8 个 EdgeOne 功能页：概览、站点管理、域名加速、内容刷新、安全证书、边缘函数、四层代理、监控日志
- 页内二级导航 + 站点 ZoneId 切换，对接 `core/cdn/edgeone` 全部 API 分类
- 系统设置新增 **CDN 配置** 折叠板块（腾讯云 EdgeOne + 阿里云 ESA 占位）

### 腾讯云通用密钥

- 新增 `tencent_secret_id` / `tencent_secret_key` / `tencent_app_id` / `tencent_region`
- COS 储存与 EdgeOne CDN **共用** API 密钥，任一入口保存后自动镜像到另一方
- 储存配置中所有云厂商密钥改为**明文可见**（不再「留空则不修改」）

### EdgeOne 后端

- API 接入：三主地域随机 + 五后备域名容错
- 凭证仅从数据库读取，`EdgeOne::create()` 为唯一入口

---

## 升级说明

- **有数据库变更**：更新后自动执行 `install/migrations/1.0.69.sql`（或覆盖后访问后台触发迁移）
- 首次使用 EdgeOne 请在 **系统设置 → CDN 配置** 填写腾讯云密钥并启用

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.69/VanShine1.0.69.zip
