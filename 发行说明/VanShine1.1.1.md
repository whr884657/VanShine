# VanShine 1.1.1 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.1.1.zip`  
**版本类型：** 小版本（修复与 UI 微调）

---

## 变更内容

### 修复 Top 排行「无效的参数」

按腾讯云 API 文档修正 `DescribeTopL7AnalysisData` 的 `MetricName`，例如：

| 排行 | 正确 MetricName |
|------|-----------------|
| Host | `l7Flow_outFlux_domain` |
| 客户端 IP | `l7Flow_outFlux_sip` |
| Referer | `l7Flow_outFlux_referers` |
| 浏览器/设备/OS | `l7Flow_outFlux_ua_*` |
| 区域分布 | `l7Flow_outFlux_country` |

### 查询条件 UI

- 时间范围恢复为 **下拉选择**（与站点/域名一致）
- 自定义筛选移至 **查询按钮前方**
- 去掉冗长说明文字，改为标题旁 **? 悬停提示**

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.1.1/VanShine1.1.1.zip
