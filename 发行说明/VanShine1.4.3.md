# VanShine 1.4.3 发行说明

**发行日期：** 2026-06-30  
**压缩包名称：** `VanShine1.4.3.zip`  
**版本类型：** 小版本（EdgeOne 弹窗尺寸与导航体验）

---

## 变更内容

### 电脑端弹窗尺寸

- 添加域名、编辑域名、HTTPS 配置、概览「添加筛选条件」等弹窗，尺寸对齐**文件管理预览弹窗**
- 全局 CSS 变量：`--vs-desktop-modal-width/height`（约 1120px × 92vh）

### 导航体验

- 修复 EdgeOne 页内 SPA 切换时短暂显示上一页（如先看到站点管理再进域名管理）
- 切换子页面前立即显示「页面加载中…」占位
- 修复 `?fragment=1` 响应重复包裹 `#edgeoneMainContent` 导致 DOM 嵌套
- SPA 加载后重新绑定域名页站点切换表单

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.4.3/VanShine1.4.3.zip
