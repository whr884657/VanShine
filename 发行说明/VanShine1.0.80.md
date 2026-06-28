# VanShine 1.0.80 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.0.80.zip`

---

## 变更内容

### 修复概览统计图 / 配额加载失败

- **现象**：页面显示「加载失败：ok」，统计图与配额一直处于加载中
- **根因**：`overview_data`、`overview_domains` 接口返回的 `charts_html` 等字段在 JSON 顶层，前端按 `data.data.*` 读取，判定为失败
- **修复**：与项目其他 AJAX 接口一致，将业务数据包裹在 `data` 字段内

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.80/VanShine1.0.80.zip
