# VanShine 1.0.74 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.74.zip`

---

## 变更内容

### EdgeOne API 参数修复（2022-09-01）

- 数据分析：改用 `ZoneIds` + `Interval`，修复七层/四层/回源/DDoS 查询
- 站点管理：`DescribeIdentifications` 补充 `Filters`；`CheckCnameStatus` 自动取加速域名作为 `RecordNames`
- 七层加速：移除无效 `Entity` 参数
- 边缘函数：触发规则仅传 `ZoneId`；移除需 `FunctionId` 的子查询
- 安全策略：模板查询改用 `ZoneIds`；移除需 `TemplateId` 的绑定查询
- 四层/负载/配置：自动串联 `ProxyId` / `LBInstanceId` / `GroupId` / `EnvId`
- 内容标识符：不再误传 `ZoneId`
- 移除共享 CNAME 功能（无权限且不需要）

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.74/VanShine1.0.74.zip
