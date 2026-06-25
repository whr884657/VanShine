# VanShine

**当前版本：1.0.26**

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

---

## 项目简介

VanShine 是一款基于 **PHP + MySQL** 的轻量级 Web 管理系统，采用**纯自定义 PHP 架构**开发，不依赖 Laravel、ThinkPHP 等第三方 PHP 框架。

**主要能力：**

- Web 五步安装向导，自动创建数据表与初始配置
- 分组侧边栏后台（控制台、文件管理、CDN、归档、系统设置等）
- 站点信息、多域名绑定、SMTP 邮箱发信与忘记密码
- 登录/注册/忘记密码独立认证页（角色动画 + 主题配色）
- **Gitee 在线更新**：登录后台自动检测新版本，一键下载安装
- **数据库结构更新**：有结构变更的版本更新后，自动执行 `install/migrations/` 中的 SQL（新增/修改字段等，非整库迁移）
- 简洁白色后台主题，纯 CSS 矢量图标，适配电脑端与手机端

---

## 发行下载

| 项目 | 说明 |
|------|------|
| 代码仓库 | [https://gitee.com/xunjinlu/VanShine](https://gitee.com/xunjinlu/VanShine) |
| 发行版本 | [Gitee Releases 发行页](https://gitee.com/xunjinlu/VanShine/releases) |
| 压缩包命名 | `VanShine` + 版本号，例如 **`VanShine1.0.26.zip`** |
| 发行说明 | 见仓库内 `发行说明/` 目录 |

---

## 功能列表

| 功能 | 路径 | 说明 |
|------|------|------|
| 前台首页 | `/` | 读取系统名称/描述配置 |
| Web 安装向导 | `/install` | 五步安装，执行 database.sql |
| 管理员登录 | `/admin/login.php` | 登录入口 |
| 管理员注册 | `/admin/register.php` | 注册界面（账号在安装时创建） |
| 管理控制台 | `/admin/index.php` | 后台首页 |
| 账号设置 | `/admin/account.php` | 修改邮箱、密码 |
| 系统设置 | `/admin/settings.php` | 站点信息、域名绑定、邮箱配置 |
| 系统升级 | `/admin/upgrade.php` | 手动检测更新、安装更新、查看更新记录 |
| 关于 | `/admin/about.php` | 系统与环境信息 |
| 忘记密码 | `/admin/forgot.php` | 邮箱验证码重置（需配置邮箱） |
| 重置密码 | `/admin/reset.php` | 已合并至忘记密码页（自动跳转） |

---

## 后台框架特性

- **自定义 PHP 架构**：无 Laravel / ThinkPHP 等框架
- **白色主题**：顶部栏 + 可收缩侧边栏
- **电脑端**：侧边栏默认展开，点击左上角三横线可收缩/展开
- **手机端**：侧边栏默认隐藏，顶部栏仅显示三横线，点击滑出菜单
- **会话超时**：长时间无操作自动退出（默认 30 分钟，可配置）
- **系统可配置**：名称、描述、关键词、Favicon、站点 Logo 可在后台修改

---

## 环境要求

- **PHP** 7.4 / 8.0 / 8.2
- **MySQL** 5.7+ 或 MariaDB 10.3+
- **PHP 扩展**：pdo、pdo_mysql、mbstring、json、session
- **目录权限**：`config/` 可写；上传 Favicon 需 `assets/img/site/` 可写

---

## 目录结构

```
VanShine/
├── README.md
├── index.php
├── admin/
│   ├── init.php              # 后台统一引导
│   ├── includes/layout.php   # 后台自定义布局
│   ├── login.php             # 登录
│   ├── index.php             # 控制台
│   ├── account.php           # 账号设置
│   ├── settings.php          # 系统设置
│   ├── forgot.php
│   └── reset.php
├── install/
│   ├── index.php
│   └── database.sql          # 数据库结构（独立维护）
├── config/
├── core/
│   ├── bootstrap.php
│   ├── version.php
│   ├── Config.php
│   ├── Auth.php
│   ├── Database.php
│   ├── DatabaseInstaller.php
│   ├── Mailer.php
│   └── helpers.php
└── assets/
    ├── css/（common, admin, icons, ...）
    ├── js/
    └── img/site/             # 站点 Favicon 上传目录
```

---

## 安装说明

1. 上传代码到 Web 服务器
2. 确保 `config/` 可写
3. 创建 MySQL 空数据库
4. 访问 `https://域名/install` 完成五步安装

---

## 在线更新

登录后台后会**自动**向 Gitee 检测最新版本（读取仓库根目录 `update.json`）。若本地 `core/version.php` 中的版本号**低于**远程版本，将弹出更新提示；若本地**高于**远程（开发测试环境），则不提示。

**发布新版本时需同步修改：**
1. `core/version.php` — `VS_VERSION`
2. `update.json` — 最新版本清单
3. `update-log.json` — 追加该版本更新记录（含 `db_changes` 是否含数据库变更）
4. 若数据库结构有变 — 在 `install/migrations/` 新增 SQL，并将对应版本 `db_changes` 设为 `true`

**更新过程：**
- 从 Gitee **发行版直链**下载（与浏览器相同）：  
  `https://gitee.com/xunjinlu/VanShine/releases/download/v{版本}/VanShine{版本}.zip`
- 出站 HTTPS 仅连接 Gitee 白名单域名，**不依赖**本地 CA 证书包
- 下载后校验 **ZIP 文件头**（`PK` 魔数），再解压覆盖
- 更新完成后自动清理 `storage/update/` 临时文件（ZIP 与解压目录）
- 覆盖项目文件，**绝不替换** `config/database.php`，**不覆盖** 运行时 `storage/` 目录
- **仅当**存在未执行的结构更新 SQL 时，才对数据库执行 ADD/ALTER 等命令（字段已存在则自动跳过）
- 更新前弹窗**二次确认**是否已备份数据

**若在线更新失败：** 请从 [Gitee 发行页](https://gitee.com/xunjinlu/VanShine/releases) 手动下载最新 `VanShine{版本}.zip` 覆盖（保留 `config/database.php`）。

**服务器要求：** PHP `ZipArchive` 扩展、可写项目目录、可访问 Gitee。

---

## 版本记录

### v1.0.26（2026-06-26）

**类型：** 在线更新临时文件清理与发行包优化

**变更说明：**
- 升级完成后自动清空 `storage/update/` 下的 ZIP 与解压目录
- 发行包排除 `storage/` 运行时目录；在线更新时不覆盖服务器 `storage/`

---

### v1.0.25（2026-06-26）

**类型：** 系统更新侧边栏角标

**变更说明：**
- 用户点击「稍后提醒」关闭更新弹窗后，侧边栏「系统」显示红色角标
- 展开「系统」大类后，角标移至「系统升级」菜单项旁

---

### v1.0.24（2026-06-26）

**类型：** 关于页信息布局优化

**变更说明：**
- 移除关于页「会话超时」显示
- 环境信息改为标签左、内容右的紧凑布局，短项多列并排；较长项独占整行

---

### v1.0.23（2026-06-26）

**类型：** 修复数据库结构更新重复字段报错

**涉及文件：**
- `core/DatabaseMigrator.php`、`install/migrations/1.0.20.sql`
- `core/Updater.php`、`assets/js/vs-update.js`、`admin/upgrade.php`

**变更说明：**
- 修复 `avatar_url` 字段已存在时更新报 Duplicate column 失败
- 结构更新前检测字段是否已存在；无害重复错误自动忽略
- 用户可见文案统一为「数据库结构更新」

---

### v1.0.22（2026-06-26）

**类型：** 在线更新 SSL 策略优化

**涉及文件：**
- `core/Updater.php`（移除 `core/cacert.pem`）
- `update.json`、`update-log.json`、`core/version.php`、`README.md`

**变更说明：**
- 更新包仅通过 Gitee 发行版直链下载，不再使用易过时的本地 CA 证书包
- 出站 HTTPS 限定 Gitee 白名单域名，下载后校验 ZIP 文件头

---

### v1.0.21（2026-06-26）

**类型：** 修复版本检测 open_basedir 报错

**涉及文件：**
- `core/Updater.php`、`admin/update.php`、`assets/js/vs-update.js`
- `update.json`、`update-log.json`、`core/version.php`

**变更说明：**
- 修复宝塔 `open_basedir` 下访问 `/etc/ssl/certs/` 触发 Warning，导致 AJAX 版本检测返回「网络异常」
- SSL 证书优先使用项目内 `core/cacert.pem`

---

### v1.0.20（2026-06-26）

**类型：** 用户头像 + 备案页脚优化

**涉及文件：**
- `core/UserAvatar.php`、`core/Auth.php`、`admin/account.php`
- `admin/includes/layout.php`、`assets/js/account.js`
- `core/SiteContext.php`、`core/helpers.php`、`index.php`
- `assets/css/admin.css`、`assets/css/common.css`
- `assets/img/gov.png`、`assets/img/avatar/`
- `install/migrations/1.0.20.sql`

**变更说明：**
- 顶部栏显示圆形头像（QQ 邮箱自动 QQ 头像 / 自定义链接 / 本地随机）
- 账号设置可填头像链接并预览；公安备案链接与页脚布局优化

---

### v1.0.19（2026-06-26）

**类型：** 修复在线更新 ZIP 下载与解压失败

**涉及文件：**
- `core/Updater.php`、`core/cacert.pem`
- `update.json`、`update-log.json`、`core/version.php`、`README.md`

**变更说明：**
- 在线更新改为优先从 Gitee 发行版附件下载，不再依赖易返回 HTML 的仓库快照链接
- 下载后校验 ZIP 文件头；修复 SSL 证书校验与发行包解压根目录识别

---

### v1.0.18（2026-06-26）

**类型：** 关于页版本展示优化

**涉及文件：**
- `admin/about.php`、`core/helpers.php`
- `assets/css/admin.css`
- `update.json`、`update-log.json`、`core/version.php`、`README.md`

**变更说明：**
- 关于页标题下方不再重复显示版本号，仅保留信息网格中的「系统版本」一行
- 打开关于页时检测 Gitee 仓库版本；若本地版本较低，显示 `v当前 → 新 v最新`，点击新版本号跳转系统升级页

---

### v1.0.17（2026-06-26）

**类型：** 系统升级页面 + 更新记录 + 二次确认

**涉及文件：**
- `admin/upgrade.php`、`assets/js/upgrade.js`、`assets/js/vs-update.js`
- `update-log.json`、`core/UpdateLog.php`
- `core/Updater.php`、`core/DatabaseMigrator.php`
- `admin/includes/layout.php`、`assets/css/admin.css`
- `update.json`、`core/version.php`、`README.md`

**变更说明：**
- 侧边栏「系统」下新增「系统升级」，支持手动检测、安装更新
- 新增 `update-log.json` 维护各版本更新记录，页面可查看
- 更新弹窗增加**备份二次确认**；无数据库变更的版本跳过 SQL 迁移

---

### v1.0.16（2026-06-26）

**类型：** 系统设置 · 绑定子域名卡片 UI

**涉及文件：**
- `admin/settings.php`、`assets/js/settings.js`、`assets/css/admin.css`
- `update.json`、`core/version.php`、`README.md`

**变更说明：**
- 绑定子域名卡片改为 **2×2 信息格**（域名 / 站点名称 / ICP / 公安备案）
- 手机端左右两列布局，四项信息同卡片内分组展示
- 编辑、删除按钮改为**胶囊样式**，尺寸更紧凑

---

### v1.0.15（2026-06-26）

**类型：** 更新数据库迁移 + 配置安全 + 文档脱敏

**涉及文件：**
- `core/DatabaseMigrator.php`、`install/migrations/1.0.15.sql`（新增）
- `core/Updater.php`、`core/DatabaseInstaller.php`
- `core/helpers.php`、`install/database.sql`、`README.md`
- `update.json`、`core/version.php`

**变更说明：**
- 在线更新完成后自动执行 `install/migrations/` 下未应用的 SQL 迁移
- `config/database.php` 列入不可覆盖名单，更新前后校验文件指纹
- 移除 README 中密码加密算法与示例哈希，降低被针对性爆破风险

---

### v1.0.14（2026-06-26）

**类型：** Gitee 在线更新机制

**涉及文件：**
- `update.json`（远程版本清单，新增）
- `core/Updater.php`、`admin/update.php`（新增）
- `assets/js/update-check.js`、`assets/js/modal.js`（扩展 HTML 弹窗）
- `assets/css/modal.css`、`admin/includes/layout.php`
- 在线更新时会**自动创建** `storage/update/` 临时目录（无需纳入仓库、无需手动上传）
- `core/bootstrap.php`、`core/version.php`、`README.md`

**变更说明：**
- 登录后台后自动检测 Gitee 最新版本并弹窗提示
- 展示更新标题、版本号与变更列表
- 支持一键下载更新包、覆盖安装并自动清理临时文件
- 本地版本高于仓库时不提示

---

### v1.0.13（2026-06-26）

**类型：** 浏览器标题去重 + 绑定域名卡片列表

**涉及文件：**
- `core/helpers.php`（`vs_page_title()` 统一构建标题）
- `admin/includes/auth_layout.php`、`admin/includes/layout.php`
- `admin/login.php`
- `admin/settings.php`、`assets/js/settings.js`、`assets/css/admin.css`
- `core/version.php`、`README.md`

**变更说明：**
- 修复浏览器标题重复（如「站点名 - 站点名」），登录页改为「登录 - 站点名」
- 全站 `<title>` 经 `vs_page_title()` 生成，页面名与站点名相同时不再拼接
- 系统设置「绑定子域名」由表格改为卡片列表，手机端无需横向滑动

---

### v1.0.12（2026-06-26）

**类型：** 退出登录修复 + 后台导航优化

**涉及文件：**
- `admin/login.php`（退出逻辑先于登录态跳转）
- `core/Auth.php`（完整销毁会话）
- `admin/includes/layout.php`（侧边栏退出入口）
- `assets/css/admin.css`、`assets/css/icons.css`
- `install/index.php`（数据库默认地址改为 localhost）
- `core/version.php`、`README.md`

**变更说明：**
- 修复点击「退出」无法登出（已登录时被 `redirectIfLoggedIn` 拦截）
- 侧边栏底部增加「退出登录」；手机端顶部栏保留退出按钮
- 电脑端侧边栏展开时隐藏顶部栏站点名称，避免与侧栏标题重复；侧栏收缩时顶部栏显示站点名
- 安装向导数据库地址默认值改为 `localhost`

---

### v1.0.11（2026-06-26）

**类型：** 统一弹窗组件 + 系统设置折叠板块

**涉及文件：**
- `core/helpers.php`（`vs_render_modal_shell()`）
- `assets/css/modal.css`、`assets/js/modal.js`（新增）
- `admin/includes/layout.php`（后台引入弹窗；`vs_admin_accordion_start/end`）
- `install/index.php`、`assets/js/install.js`（清空库确认、密码校验弹窗）
- `admin/settings.php`、`assets/js/settings.js`（域名删除确认；板块默认折叠）
- `assets/css/admin.css`
- `core/version.php`、`README.md`

**变更说明：**
- 全站统一自定义弹窗（`VsModal.alert` / `VsModal.confirm`），替代浏览器原生 `alert` / `confirm`
- 安装向导第三步「清空数据库并重新创建」使用主题一致确认弹窗
- 系统设置三个板块（站点信息、绑定子域名、邮箱发信）默认折叠，点击标题展开；编辑域名时自动展开子域名板块

---

### v1.0.10（2026-06-26）

**类型：** 安装/设置交互优化 + 侧边栏分组菜单

**涉及文件：**
- `install/index.php`、`assets/js/install.js`（数据库测试 AJAX，无整页刷新）
- `admin/settings.php`、`assets/js/settings.js`（保存/测试 AJAX）
- `admin/includes/layout.php`（分组侧边栏）
- `admin/files.php`、`admin/cdn/*`、`admin/archive/*`（占位页）
- `core/AjaxResponse.php`（新增）
- `assets/css/admin.css`、`assets/css/icons.css`、`assets/js/admin.js`
- `core/version.php`、`README.md`

**变更说明：**
- 安装向导「测试数据库连接」改为 AJAX，页面不刷新、不跳回顶部
- 系统设置各保存/测试操作改为 AJAX 静态提示
- 统一系统设置表单间距；测试邮箱输入框与按钮增加间距
- 侧边栏分组：控制台、文件管理、CDN（EdgeOne/ESA）、归档（文章/记事本/资料库/密钥管理）、系统（账号/设置/关于）

---

### v1.0.9（2026-06-26）

**类型：** 站点 Logo 可配置

**涉及文件：**
- `install/database.sql`（新增 `site_logo` 配置项）
- `core/SiteContext.php`、`core/helpers.php`
- `admin/settings.php`、`admin/includes/layout.php`、`index.php`
- `assets/css/common.css`、`assets/css/admin.css`
- `core/version.php`、`README.md`

**变更说明：**
- 新增「站点 Logo」配置，支持填写 URL 或站点路径（与 Favicon 相同方式）
- 前台页眉、后台侧栏不再使用系统名称首字作为黑色方块图标，改为展示自定义 Logo
- 未配置 Logo 时不显示图标，仅保留站点名称文字

**升级提示：** 已安装站点可在后台「系统设置」填写 Logo，或向 `vs_config` 插入 `site_logo` 配置项。

---

### v1.0.8（2026-06-26）

**类型：** 认证页安全防护

**涉及文件：**
- `core/AuthSecurity.php`（新增）
- `core/bootstrap.php`、`core/Auth.php`
- `admin/login.php`、`admin/register.php`、`admin/forgot.php`
- `admin/includes/auth_layout.php`
- `core/version.php`、`README.md`

**变更说明：**
- CSRF 令牌：所有认证 POST 必须携带令牌，并校验同源来源
- 发验证码频率限制：单 IP / 单邮箱每小时上限，同一邮箱 60 秒间隔（持久化到 `config/.security/`）
- 登录防暴力：单 IP / 单账号 15 分钟内失败次数限制
- Session 加固：HttpOnly、SameSite=Strict、HTTPS 下 Secure、登录后轮换 Session ID
- 认证页响应头：禁止缓存、nosniff、SAMEORIGIN

**部署提示：** 生产环境务必启用 HTTPS，否则明文传输下抓包仍可看到账号密码；HTTPS + 上述防护可有效抵御接口滥用与跨站攻击。

---

### v1.0.7（2026-06-26）

**类型：** 忘记密码改为邮箱验证码

**涉及文件：**
- `admin/forgot.php`、`admin/reset.php`
- `core/Auth.php`、`core/version.php`
- `README.md`

**变更说明：**
- 忘记密码改为：邮箱发送 6 位验证码，用户在页面填写验证码与新密码完成重置
- 验证码 5 分钟有效，60 秒内不可重复发送
- `reset.php` 重定向至 `forgot.php`（旧邮件链接入口已弃用）

---

### v1.0.6（2026-06-26）

**类型：** 认证页 UI 重构 + 后台主题统一

**涉及文件：**
- `admin/login.php`、`admin/register.php`（新增）、`admin/forgot.php`、`admin/reset.php`
- `admin/includes/auth_layout.php`（新增）
- `assets/css/auth-login.css`、`assets/css/theme-picker.css`（新增）
- `assets/js/auth-characters.js`、`assets/js/theme-picker.js`（新增）
- `assets/css/common.css`、`assets/css/admin.css`、`assets/css/index.css`
- `core/version.php`、`README.md`

**变更说明：**
- 登录、注册、忘记密码页按「登录界面参考」一比一还原（角色动画、调色盘、表单动效）
- 新增注册页 UI（管理员账号仍在安装时创建，页面提示不支持开放注册）
- 忘记密码保留邮件链接重置逻辑，界面与参考一致
- 后台主题调整为黑白极简风格，与认证页统一

---

### v1.0.5（2026-06-26）

**类型：** Bug 修复（配置读取与备案展示）

**涉及文件：**
- core/Config.php、core/Domain.php、core/SiteContext.php
- core/version.php
- admin/settings.php
- README.md

**变更说明：**
- 修复 `vs_config` 表 `key` 列在 PDO 读取时映射失败，导致系统设置保存后无法回显、前台备案号不显示的问题
- 站点设置保存后采用 PRG 重定向，刷新页面可看到成功提示与最新配置
- 域名匹配增加 `www` 前缀容错

---

### v1.0.4（2026-06-26）

**类型：** 配置架构 + 多域名绑定 + 关于页

**涉及文件：**
- install/database.sql（初始配置 INSERT、domain 表）
- core/Config.php、core/Domain.php、core/SiteContext.php、core/SystemInfo.php
- core/version.php（VS_SESSION_TIMEOUT 常量）
- core/bootstrap.php、core/helpers.php
- admin/settings.php、admin/about.php（新增）
- admin/includes/layout.php、admin/init.php
- index.php
- README.md 及全部版本号文件

**变更说明：**
- 系统默认配置移至 database.sql 初始数据，不再写在 PHP 代码中
- Favicon 改为填写 URL 或站点路径，取消上传
- 会话超时写死为 30 分钟（VS_SESSION_TIMEOUT），每次操作刷新计时
- 新增多域名绑定：主域名用系统设置，子域名独立名称与备案号
- 前台按访问域名自动展示对应站点名称与 ICP/公安备案（链接至官方查询页）
- 新增后台「关于」页面，展示 PHP/MySQL/服务器等信息
- 移除后台底部栏

**升级提示：** 已安装站点需重新执行安装第三步（清空重建）或手动执行 database.sql 中新增表与 INSERT 语句。

---

### v1.0.3（2026-06-26）

**类型：** 布局优化 + 滚动修复

**涉及文件：**
- README.md
- assets/css/common.css
- assets/css/admin.css
- assets/css/install.css
- assets/css/admin-login.css
- core/helpers.php
- admin/includes/layout.php
- install/index.php
- 全部版本号文件同步

**变更说明：**
- 修复全站页面无法上下滚动（移除后台 body overflow 锁死）
- 优化 viewport，移除 maximum-scale 限制
- 安装向导环境检测、表单改为两列布局（手机端单列）
- 登录页内容过高时可正常滚动

---

### v1.0.2（2026-06-26）

**类型：** 后台框架重构 + 系统可配置

**涉及文件：**
- README.md
- index.php
- admin/init.php（新增）
- admin/includes/layout.php（新增）
- admin/index.php
- admin/account.php（新增）
- admin/settings.php
- admin/login.php
- admin/forgot.php
- admin/reset.php
- core/version.php
- core/Config.php
- core/Auth.php
- core/helpers.php
- core/bootstrap.php
- core/Database.php
- core/InstallChecker.php
- core/Mailer.php
- core/DatabaseInstaller.php
- install/index.php
- install/database.sql
- assets/css/admin.css
- assets/css/icons.css（新增）
- assets/css/common.css
- assets/css/install.css
- assets/css/admin-login.css
- assets/css/index.css
- assets/js/admin.js
- assets/js/common.js
- assets/js/install.js
- assets/js/admin-login.js
- assets/img/site/.gitkeep（新增）

**变更说明：**
- 移除第三方 UI 框架 CDN 依赖，采用纯自定义 PHP + CSS 架构
- 新增专属 SVG 图标库（icons.css）
- 重构后台布局：顶部栏 + 可收缩侧边栏
- 电脑端侧边栏默认展开，手机端默认收缩，三横线切换
- 新增系统名称、描述、关键词、Favicon 可配置
- 拆分账号设置（account.php）与系统设置（settings.php）
- 登录态下可直接修改邮箱和密码
- 前台首页/登录页读取系统配置名称与描述

---

### v1.0.1（2026-06-26）

- 安装建表改为 database.sql
- 拆分 login.php 与 index.php
- 邮箱发信与忘记密码

---

### v1.0.0（2026-06-26）

- 初始版本

---

## 开源协议

本项目采用 **[MIT License（MIT 开源协议）](LICENSE)**。

### 您可以

- **学习研究**：阅读、学习本项目源码
- **个人使用**：在个人网站、项目中免费使用
- **商业使用**：在商业项目中免费使用
- **修改分发**：可修改代码并再发布（需保留版权声明与协议全文）

### 作者声明（免责）

- 本项目按 **「原样（AS IS）」** 提供，**不提供任何明示或暗示的担保**
- 因使用、无法使用或依赖本项目而产生的任何直接、间接、附带、特殊或后果性损害（包括但不限于数据丢失、业务中断、安全漏洞、法律纠纷等），**作者不承担任何责任**
- 使用者应自行评估安全风险，并在生产环境中做好备份、加固与合规审查

完整法律条文见仓库根目录 **[LICENSE](LICENSE)** 文件。

---

## 作者与仓库

- 仓库地址：[https://gitee.com/xunjinlu/VanShine](https://gitee.com/xunjinlu/VanShine)
- 问题反馈：请通过 Gitee Issues 提交

---
