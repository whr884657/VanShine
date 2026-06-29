# VanShine 1.1.0 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.1.0.zip`  
**版本类型：** 大版本（EdgeOne 概览全面改版）

---

## 变更内容

### 自定义筛选 Filters.N

参考 [DescribeTimingL7AnalysisData](https://cloud.tencent.com/document/api/1552/80648) 与 [指标分析筛选条件说明](https://cloud.tencent.com/document/product/1552/98219)，概览页新增「+ 添加筛选」：

- 支持国家/地区、状态码、HTTP 协议、运营商、TLS、URL Path、Referer、资源类型、设备/浏览器/OS、缓存状态、客户端 IP、User-Agent 等
- 多条件「且」关系，同条件多值「或」关系
- 站点、域名仍使用上方专用字段（对应 ZoneIds / domain）

### 概览布局（参考腾讯云控制台）

| 区域 | 内容 |
|------|------|
| **左侧栏** | 总流量、响应流量、请求流量、缓存命中率 |
| **主区域** | L7 访问流量折线图 |
| **区域分布** | 国家/地区 Top 列表 |
| **排行网格** | Host、IP、Referer、URL、状态码、浏览器、设备、OS 等 Top 数据 |

### 查询条件

- 时间范围改为 **Tab 快捷按钮**（30 分钟～30 天）
- 站点、域名、查询按钮与自定义筛选同面板

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.1.0/VanShine1.1.0.zip
