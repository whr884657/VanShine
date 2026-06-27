# VanShine 1.0.47 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.47.zip`

---

## 版本概述

简化数据库结构：绑定子域名不再使用独立 `domain` 表，统一存入 `config.bound_domains` JSON 字段。同步优化登录页与储存配置体验。

---

## 变更内容

### 数据库

- 删除 `domain` 表
- 新增配置项 `bound_domains`（JSON 数组，每项含 domain / site_name / icp_number / gongan_number）
- 升级时自动迁移旧表数据并删表（`1.0.47.sql`）

### 登录

- 「保持登录」改为「记住账号密码」
- 勾选后账号密码保存在浏览器本地，下次访问自动填充，可直接点击登录

### 储存配置

- **AWS S3、蓝奏云优享版、WebDAV**：标题标注「暂时不可用」（仍可填写配置，不影响已有数据）
- **七牛云 / 腾讯云 COS / 阿里云 OSS**：增加「还没有账号？前往官网注册」外链

---

## 升级说明

- **含数据库结构变更**：在线升级将执行 `1.0.47` 迁移，原 `domain` 表记录会自动写入 `bound_domains`
- 升级后 **Ctrl+F5** 强刷后台

---

https://gitee.com/xunjinlu/VanShine
