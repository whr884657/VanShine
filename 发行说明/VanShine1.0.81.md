# VanShine 1.0.81 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.0.81.zip`

---

## 变更内容

### 修复概览统计图「无效的参数」

- **根因**：`DescribeTimingL7AnalysisData` 一次传入多个 `MetricNames` 会触发腾讯云返回「无效的参数」
- **修复**：与数据分析页一致，改为**按站点 × 指标逐条查询**后合并图表
- 新增 `vs_edgeone_clamp_analytics_interval()`，按时间范围限制粒度，避免 Interval 与查询跨度不匹配

### 套餐配额板块调整

- 概览页移除「套餐与配额」区块（减轻首屏 API 压力）
- **套餐计费**页底部新增「套餐配额与内容配额」，展示全部站点的套餐流量/请求配额及刷新预热配额

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.81/VanShine1.0.81.zip
