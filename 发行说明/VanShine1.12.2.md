# VanShine 1.12.2 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.12.2.zip`  
**版本类型：** 小版本（缓存管理切换站点 SPA 修复）

---

## 变更内容

### 修复：切换站点整页刷新

- 缓存管理页切换站点时 **不再整页刷新**
- 通过 `fragment=1` 局部更新主内容，并同步刷新顶部 **配额条与站点选择器**
- 与 EdgeOne 其他页面的 SPA 无刷新体验一致

---

## 升级说明

- **数据库：** 无变更
- 覆盖升级后请 **Ctrl+F5** 强制刷新浏览器缓存

---

## 下载

- Gitee：https://gitee.com/xunjinlu/VanShine/releases/download/v1.12.2/VanShine1.12.2.zip
