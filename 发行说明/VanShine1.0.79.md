# VanShine 1.0.79 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.0.79.zip`

---

## 变更内容

### 无查询串筛选（Session）

- 概览、数据分析、套餐计费等页面的筛选条件写入 **Session**，不再拼接到地址栏
- 浏览器地址始终为 `/admin/cdn/edgeone/index.php` 等路径，无 `?range=…&filter_zone=…`
- 首次访问带旧版 GET 参数时会自动写入 Session 并清理 URL

### 概览页性能与 UX

- 统计图与配额改为 **AJAX 异步加载**（`overview_data`），首屏不再同步请求全部 API，避免 30 秒超时
- **电脑端（≥992px）** 统计图 **双列** 网格布局
- 多站点折线图右上角显示 **颜色图例**（站点分线 + 总计）
- 折线配色调整为蓝/橙/青等更易读色系

### 其他

- 修复 `vs_edgeone_is_fragment_request()` 重复定义
- SPA 内页切换后表单 POST 目标路径修正

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.79/VanShine1.0.79.zip
