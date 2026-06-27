# VanShine 1.0.46 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.46.zip`

---

## 版本概述

清理废弃的「邮件链接 + token」密码重置机制，忘记密码**仅保留邮箱验证码**流程。

---

## 变更内容

- 删除数据库表 `password_reset`（新安装不再创建）
- 移除 `Auth::createResetToken`、`validateResetToken`、`resetPassword` 等 token 相关方法
- 删除已弃用入口 `admin/reset.php`
- 删除非项目文档 `docs/flyfish-viewer.md`（预览说明已合并至 README）

---

## 升级说明

- **含数据库结构变更**：在线升级或手动覆盖后，将自动执行 `install/migrations/1.0.46.sql` 删除 `password_reset` 表
- 若曾收藏旧版 `/admin/reset.php?token=...` 链接，请改用 `/admin/forgot.php` 通过验证码重置

---

https://gitee.com/xunjinlu/VanShine
