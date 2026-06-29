# VanShine 1.0.83 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.0.83.zip`

---

## 变更内容

### 修复手机端导航大类不显示

- **根因**：旧版 CSS `.vs-edgeone-nav__group { display: none }` 误隐藏了手机端大类
- 手机端大类改用 `vs-edgeone-nav__section`，与 legacy 类名隔离
- **总览** 也改为中折叠结构，展开后显示「概览」子项
- 大折叠展开后可见全部 7 个大类，中折叠再展开小类

### 统计图悬停数据

- 鼠标移到折线图上显示 **时间 + 各线数值** 浮层
- 显示竖向指示线与数据点圆点
- 支持触摸滑动查看（手机端）

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.83/VanShine1.0.83.zip
