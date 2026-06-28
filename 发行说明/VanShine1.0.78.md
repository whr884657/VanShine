# VanShine 1.0.78 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.0.78.zip`

---

## 变更内容

### 修复概览数据查询失败

- **根因**：`DescribeTimingL7AnalysisData` 的 `Filters` 误用 `Name`/`Values`，官方要求 `Key`/`Operator`/`Value`
- 域名筛选示例：`{ "Key": "domain", "Operator": "equals", "Value": ["www.example.com"] }`
- 移除无效指标 `l7Flow_bandwidth`；**访问总带宽**改为响应带宽 + 请求带宽本地汇总

### 概览页逻辑调整

- API **不支持**一次返回多站点分线时序数据：改为**按站点逐站查询**，后端合并为分线 + 总计图表
- 概览页**移除「当前站点」选择器**（仍可在筛选栏选单个站点/域名）
- **套餐与配额**按账号下全部站点分别展示（套餐流量/请求 + 刷新预热配额）

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.78/VanShine1.0.78.zip
