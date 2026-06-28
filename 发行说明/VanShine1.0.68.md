# VanShine 1.0.68 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.68.zip`

---

## 变更内容

### 后台主题色全框架生效

- **顶栏**、**侧边栏**、**主内容区** 统一绑定 CSS 变量 `--page-bg`
- 与登录/注册/忘记密码页共用 `localStorage` 键 `login_page_bg`
- 修复 v1.0.67 仅内容区背景变色、顶栏与侧边栏仍为白色的问题

### 技术说明

- `--vs-sidebar-bg`、`--vs-topbar-bg`、`--vs-content-bg` 均回退为 `var(--page-bg, 默认值)`
- 保存/重置主题时仅维护 `--page-bg`，各区域自动联动

---

## 升级说明

- **无数据库变更**，覆盖文件即可（保留 `config/`）

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.68/VanShine1.0.68.zip
