# VanShine 1.0.84 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.0.84.zip`

---

## 变更内容

### 修复缓存命中次数「无效的参数」

- API 无 `l7Flow_hitRequest` 指标
- 改为 `l7Flow_request` + 筛选 `cacheType=hit`（官方文档）
- 概览、数据分析、套餐计费页均已同步

### 概览页布局

- 筛选区独立为 **「查询条件」** 板块（不再混在「数据概览」下）
- **移除「站点概览」** 站点卡片板块
- 手机端四个筛选项 **2×2 网格**（一行两个，共两行）

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.84/VanShine1.0.84.zip
