# VanShine

**当前版本：1.0.85**

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

---

## 项目简介

VanShine 是一款基于 **PHP + MySQL** 的轻量级 Web 管理系统，采用**纯自定义 PHP 架构**开发，不依赖 Laravel、ThinkPHP 等第三方 PHP 框架。

**主要能力：**

- Web 五步安装向导，自动创建数据表与初始配置
- 分组侧边栏后台（控制台、文件、七种储存、CDN、归档、AI、系统设置等）
- **文件分享**：`{域名}/d/?token=...`，下载/预览经 `/d/stream.php` 签名 URL（可选密码）
- 站点信息、多域名绑定、SMTP 邮箱发信与忘记密码
- 登录/注册/忘记密码独立认证页（角色动画 + 主题配色）
- **云端在线更新**：登录后台自动检测新版本，分步进度一键安装
- **数据库结构更新**：有结构变更的版本更新后，自动执行 `install/migrations/` 中的 SQL（新增/修改字段等，非整库迁移）
- 简洁白色后台主题，纯 CSS 矢量图标，适配电脑端与手机端

---

## 发行下载

| 项目 | 说明 |
|------|------|
| 代码仓库 | [https://gitee.com/xunjinlu/VanShine](https://gitee.com/xunjinlu/VanShine) |
| 发行版本 | [Gitee Releases 发行页](https://gitee.com/xunjinlu/VanShine/releases) |
| 压缩包命名 | `VanShine` + 版本号，例如 **`VanShine1.0.58.zip`** |
| 发行说明 | 见仓库内 `发行说明/` 目录 |
| **推送与发版流程** | 见 [`Gitee推送与发行流程.md`](Gitee推送与发行流程.md)、[`发布检查清单.md`](发布检查清单.md) |

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
| 系统设置 | `/admin/settings.php` | 站点信息、域名绑定、邮箱、储存、CDN 配置 |
| 文件管理 | `/admin/files/manage.php` | 文件夹绑定储存、批量/拖拽上传、三种视图、创建分享 |
| 分享管理 | `/admin/files/shares.php` | 分享短链接列表、密码/过期/下载统计 |
| 系统升级 | `/admin/upgrade.php` | 手动检测更新、安装更新、查看更新记录 |
| 关于 | `/admin/about.php` | 系统与环境信息 |
| 忘记密码 | `/admin/forgot.php` | 邮箱验证码重置（需配置邮箱） |
| AI · DeepSeek | `/admin/ai/deepseek.php` | 占位（开发中） |
| AI · ChatGPT | `/admin/ai/chatgpt.php` | 占位（开发中） |
| AI · Claude | `/admin/ai/claude.php` | 占位（开发中） |
| AI · Gemini | `/admin/ai/gemini.php` | 占位（开发中） |

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
├── 伪静态配置.md              # Nginx / Apache 分享短链接 rewrite 说明
├── LICENSE
├── update.json                 # 远程版本清单（在线更新检测）
├── update-log.json             # 版本更新记录
├── index.php                   # 前台首页
├── .gitattributes
├── admin/                      # 后台
│   ├── init.php                # 后台统一引导
│   ├── includes/
│   │   ├── layout.php          # 侧边栏布局
│   │   ├── auth_layout.php     # 登录/注册布局
│   │   └── storage_settings.php
│   ├── index.php               # 控制台
│   ├── files/
│   │   ├── manage.php          # 文件管理
│   │   └── shares.php          # 分享管理
│   ├── settings.php            # 系统设置
│   ├── account.php             # 账号设置
│   ├── upgrade.php             # 系统升级
│   ├── about.php
│   ├── login.php / register.php / forgot.php
│   ├── ai/                     # AI 子菜单（占位）
│   ├── archive/                # 归档（占位）
│   └── cdn/                    # CDN（占位）
├── assets/
│   ├── css/                    # common, admin, modal, icons, files …
│   ├── js/                     # file-preview.js, vs-update.js …
│   ├── vendor/preview/         # 在线预览本地库（marked、pdf.js 等）
│   └── img/site/               # 站点 Favicon 等
├── config/
│   └── database.php            # 安装后生成（勿覆盖）
├── core/
│   ├── bootstrap.php
│   ├── version.php
│   ├── Config.php / Auth.php / Database.php …
│   ├── StorageRegistry.php     # 七种储存注册（KEY 1–7）
│   ├── StorageManager.php
│   ├── FileFolder.php / FileItem.php
│   ├── UploadNaming.php
│   └── Storage/                # 七种储存驱动（各自 vendor/）
│       ├── LocalStorage/       # KEY 1
│       ├── AwsS3/              # KEY 2
│       ├── AliyunOss/          # KEY 3
│       ├── TencentCos/         # KEY 4
│       ├── QiniuKodo/          # KEY 5
│       ├── ILanZou/            # KEY 6
│       └── WebDavStorage/      # KEY 7
├── data/                       # 运行时数据（在线更新临时文件等，自动创建）
├── upload/                     # 本地上传默认目录
├── install/
│   ├── index.php               # 五步安装向导
│   ├── database.sql            # 全新安装数据库结构
│   └── migrations/             # 在线升级增量 SQL（如 1.0.31.sql）
└── 发行说明/                   # 各版本发行说明 Markdown
```

**储存 KEY 对照：** 1 本地 · 2 AWS S3 · 3 阿里云 OSS · 4 腾讯云 COS · 5 七牛 Kodo · 6 蓝奏优享 · 7 WebDAV

---

## 安装说明

1. 上传代码到 Web 服务器
2. 确保 `config/` 可写
3. 创建 MySQL 空数据库
4. 访问 `https://域名/install` 完成五步安装

---

## 伪静态 / URL 重写

分享功能**不需要**单独 Nginx 规则，与全站 PHP 共用面板默认解释器（任意 PHP 版本均可）。

| 地址 | 说明 |
|------|------|
| `/d/` | 安全提醒页 |
| `/d/?token={token}` | 公开分享页 |
| `/d/stream.php?...` | 签名下载/预览流（须先访问分享页） |

**完整说明见：** [`伪静态配置.md`](伪静态配置.md)

站点只需保留通用伪静态：

```nginx
location / {
    try_files $uri $uri/ $uri.php$is_args$args;
}
```

切勿为 `/d/` 单独添加 `try_files $uri` 规则，否则可能导致 `index.php` 源码被下载。

---

## 在线更新

登录后台后会**自动**向云端检测最新版本（读取 `update.json`）。若本地 `core/version.php` 中的版本号**低于**远程版本，将弹出更新提示；若本地**高于**远程（开发测试环境），则不提示。

**发布新版本时需同步修改：**
1. `core/version.php` — `VS_VERSION`
2. `update.json` — 最新版本清单
3. `update-log.json` — 追加该版本更新记录（含 `db_changes` 是否含数据库变更）
4. 若数据库结构有变 — 在 `install/migrations/` 新增 SQL，并将对应版本 `db_changes` 设为 `true`

**更新过程（分步进度）：**
1. 从云端下载资源包
2. 解压更新包
3. 覆盖系统文件（**绝不替换** `config/database.php`，**不覆盖** 运行时 `data/`、`upload/`）
4. 若存在未执行的结构更新 SQL，则执行数据库迁移
5. 完成后自动清理 `data/update/` 临时文件

**若在线更新失败：** 请从 [发行页](https://gitee.com/xunjinlu/VanShine/releases) 手动下载最新 `VanShine{版本}.zip` 覆盖（保留 `config/database.php`）。发版维护者请参阅 [`Gitee推送与发行流程.md`](Gitee推送与发行流程.md)。

**服务器要求：** PHP `ZipArchive` 扩展、可写项目目录、可访问云端更新源。

---

## 版本记录

### v1.0.85（2026-06-29）

**类型：** 修复腾讯云 COS 上传失败

**变更说明：**
- COS 与 EdgeOne 仅共用 SecretId/SecretKey，Region 各自独立
- 修复保存 CDN 设置覆盖 COS 桶地域导致上传失败

**数据库：** 无变更

---

### v1.0.84（2026-06-29）

**类型：** 修复缓存命中指标与概览页布局调整

**变更说明：**
- 缓存命中改用 l7Flow_request + cacheType=hit；概览查询条件独立，移除站点概览
- 手机端筛选 2×2 网格

**数据库：** 无变更

---

### v1.0.83（2026-06-29）

**类型：** 修复手机端导航大类显示与统计图悬停提示

**变更说明：**
- 修复 legacy CSS 导致手机端仅显示总览；全部大类中折叠+小折叠
- 统计图支持鼠标/触摸悬停显示时间点数据

**数据库：** 无变更

---

### v1.0.82（2026-06-29）

**类型：** EdgeOne 手机端导航总折叠优化

**变更说明：**
- 手机端改为总折叠抽屉，默认仅一条；大类内嵌套子项；跳转后自动收起

**数据库：** 无变更

---

### v1.0.81（2026-06-29）

**类型：** 修复 EdgeOne 概览统计图查询与配额板块调整

**变更说明：**
- 概览按站点×指标逐条查询，修复「无效的参数」；限制 Interval 与时间范围匹配
- 套餐配额从概览移至套餐计费页底部

**数据库：** 无变更

---

### v1.0.80（2026-06-29）

**类型：** 修复 EdgeOne 概览异步加载响应格式

**变更说明：**
- 修复 `overview_data` / `overview_domains` 响应未包裹 `data` 字段，导致统计图与配额显示「加载失败：ok」

**数据库：** 无变更

---

### v1.0.79（2026-06-29）

**类型：** EdgeOne 无参 URL 查询与概览统计图 UX

**变更说明：**
- 筛选参数写入 Session，地址栏无查询串；概览/数据分析/套餐计费均适用
- 概览统计图异步加载，避免 30 秒超时；电脑端双列布局、多线右上角图例、优化配色

**数据库：** 无变更

---

### v1.0.78（2026-06-29）

**类型：** 修复 EdgeOne 概览 API 筛选参数与全站点配额

**变更说明：**
- 修复 `DescribeTimingL7AnalysisData` Filters 参数格式（Key/Operator/Value）
- 概览按站点逐站查询并后端汇总；访问总带宽本地合并
- 概览页移除当前站点选择器；展示全部站点套餐与刷新配额

**数据库：** 无变更

---

### v1.0.77（2026-06-29）

**类型：** EdgeOne 概览仪表盘与导航 UX 重构

**变更说明：**
- 概览：多指标统计图墙、统一时间/站点/域名筛选、全站点分线 + 总计
- 导航：电脑端横向大类下拉、手机端折叠分组
- 局部刷新：EdgeOne 内导航与查询不再整页 reload
- 修正站点运行状态误报；套餐流量/请求配额与刷新配额同页展示

**数据库：** 无变更

---

### v1.0.76（2026-06-28）

**类型：** EdgeOne 数据分析与套餐计费业务仪表盘

**变更说明：**
- 数据分析：七层/回源/四层、多指标、1 分钟~1 天粒度、2 小时~30 天范围、折线图
- 套餐计费：套餐详情卡片、绑定站点、今日/本月用量 KPI 与趋势
- 移除原始 JSON 展示

**数据库：** 无变更

---

### v1.0.75（2026-06-28）

**类型：** EdgeOne 后台 UI 可视化重构

**变更说明：**
- API 响应统一经 `data-view` 渲染为表格、卡片、配额进度与时序数据
- 概览当前站点高亮；内容刷新/预热记录表格化展示
- 各 section 可折叠查看原始 JSON

**数据库：** 无变更

---

### v1.0.74（2026-06-27）

**类型：** EdgeOne API 2022-09-01 参数全面修复

**变更说明：**
- 数据分析、站点验证、七层加速、边缘函数、安全策略等页面对齐最新 API 必填参数
- 四层/负载/配置页自动串联 ProxyId、LBInstanceId、GroupId、EnvId
- 移除共享 CNAME 及需 FunctionId/TemplateId 的无效查询

**数据库：** 无变更

---

### v1.0.73（2026-06-27）

**类型：** 移除 EdgeOne 冗余跳转页

**变更说明：**
- 删除 `monitor.php`、`edgeone.php`，不再保留任何跳转兼容页
- 套餐计费入口：`/admin/cdn/edgeone/billing.php`

**数据库：** 无变更

---

### v1.0.72（2026-06-27）

**类型：** EdgeOne 全模块导航与 API 页面扩展

**变更说明：**
- DescribeContentQuota 补充 ZoneId；移除 EdgeKV 模块
- EdgeOne 页内导航扩展为 7 组 18 个功能页（七层加速、DNS、证书、源站防护、负载均衡、数据分析、日志、套餐计费等）
- 预热/刷新空记录友好提示

**数据库：** 无变更

---

### v1.0.71（2026-06-27）

**类型：** EdgeOne 配额与计费 API 参数修复

**变更说明：**
- `DescribeContentQuota` 空请求体修正为 JSON 对象 `{}`
- `DescribeBillingData` 补充必填 `Interval` 与 `ZoneIds`

**数据库：** 无变更

---

### v1.0.70（2026-06-27）

**类型：** EdgeOne API 版本修复与 CDN 导航优化

**变更说明：**
- EdgeOne API 版本升级至 `2022-09-01`，修复多个 Describe 接口不可用
- 刷新/预热与计费查询补充 `StartTime` / `EndTime`
- CDN 侧边栏恢复 EdgeOne + ESA 两项，功能页内横向切换
- 站点备注（AliasZoneName）与中文状态展示；卡片间距优化

**数据库：** 无变更

---

### v1.0.69（2026-06-28）

**类型：** EdgeOne CDN 后台与腾讯云通用密钥

**变更说明：**
- 系统设置新增 **CDN 配置**（腾讯云 EdgeOne + 阿里云 ESA 占位）
- 腾讯云 API 密钥与 COS 储存共用（`tencent_secret_*`），COS / CDN 保存后自动同步
- 储存与邮箱密钥字段改为明文可见
- CDN 侧边栏：EO 概览、站点、域名、刷新、安全、边缘函数、四层、监控
- EdgeOne API：广州/上海/重庆随机主用 + 五后备接入点容错

**数据库：** `install/migrations/1.0.69.sql`（腾讯云通用密钥与 EdgeOne 配置项）

---

### v1.0.68（2026-06-27）

**类型：** 后台顶栏侧边栏随主题色同步

**变更说明：**
- 顶栏、侧边栏、主内容区统一使用 `--page-bg`（与登录页 `login_page_bg` 同步）
- 修复仅内容区变色、顶栏侧边栏仍为白色的问题

**数据库：** 无结构变更

---

### v1.0.67（2026-06-27）

**类型：** 后台顶栏主题调色盘

**变更说明：**
- 后台顶栏头像旁增加圆形调色盘按钮（与登录页同款圆形按钮）
- 主题色与登录/注册/忘记密码共用 `localStorage`，保存后双向同步
- 后台主区域背景随自定义主题色变化

**数据库：** 无结构变更

---

### v1.0.66（2026-06-27）

**类型：** 全站提示统一顶部 Toast

**变更说明：**
- 全局胶囊提示固定顶部显示，手机端不再在底部弹出
- 复制链接、保存、删除等操作提示统一为 VsToast
- 登录/注册/忘记密码、安装向导、系统升级、主题色等提示同步调整

**数据库：** 无结构变更

---

### v1.0.65（2026-06-27）

**类型：** 分享页点击预览与复制提示

**变更说明：**
- 分享链接打开后不再自动预览第一个文件，用户点击文件后才加载预览
- 文件夹分享初始仅显示文件列表，点击文件后预览框动态出现
- 分享管理页复制短链接：剪贴板失败时自动回退，按钮与顶部提示「已复制」

**数据库：** 无结构变更

---

### v1.0.64（2026-06-27）

**类型：** 文件管理弹窗预览滚动与限高

**变更说明：**
- 修复弹窗无法上下滑动查看底部文件信息与操作区
- 弹窗预览区限高，长文档在框内滚动；全屏按钮查看完整文档
- 视频/音频/图片/占位图标在预览框内居中

**数据库：** 无结构变更

---

### v1.0.63（2026-06-27）

**类型：** 分享页预览布局与滚动修复

**变更说明：**
- 桌面端左 2/3 预览、右 1/3 文件列表；移动端预览在上、列表在下
- 预览区白色背景；媒体与占位图标在框内居中
- 修复长文档/MD 无法在预览框内滚动
- 不可预览/压缩包占位图标改为 SVG

**数据库：** 无结构变更

---

### v1.0.62（2026-06-27）

**类型：** 分享页桌面全屏双栏

**变更说明：**
- 电脑端全屏：左 1/3 文件列表、右 2/3 预览（单文件/文件夹统一）
- 文档预览限高并在预览框内滚动

**数据库：** 无结构变更

---

### v1.0.61（2026-06-27）

**类型：** 分享管理、弹窗、预览全面修复

**变更说明：**
- 分享管理移动端卡片默认折叠，有效置顶、失效沉底
- 分享过期日期改手动输入 YYYY-MM-DD；修复分享弹窗无法弹出
- 分享页桌面双栏布局；预览限高内部滚动；加载 favicon
- 移除音频预览唱片图标

**数据库：** 无结构变更

---

### v1.0.60（2026-06-27）

**类型：** 分享页 UI 全面重设计

**变更说明：**
- 分享页/安全提醒页统一卡片布局，消除顶部空白与页脚割裂感
- 文件列表增加类型图标与图片缩略图；单文件默认展示预览区
- 备案信息整合进分享页页脚

**数据库：** 无结构变更

---

### v1.0.59（2026-06-27）

**类型：** 分享流安全加固、移除旧版兼容

**变更说明：**
- 唯一流入口 `/d/stream.php`，删除 `stream-handler.php` 与 `stream=1` 等旧路径
- 下载/预览使用 HMAC 签名短时 URL，URL 不含分享 token；须分享页 Session
- 预览 JS 使用 `shareStreams` 映射；流接口增加 no-store 安全头

**数据库：** 无结构变更

---

### v1.0.58（2026-06-27）

**类型：** 分享 URL 简化、预览滚动、文件分享按钮

**变更说明：**
- 分享链接 `/d/?token=`，无 index.php；分享/安全页增加备案页脚
- 预览弹窗整体可滚动；文件/文件夹操作按钮悬停显示文字
- 文件列表增加分享按钮

**数据库：** 无结构变更

---

### v1.0.57（2026-06-27）

**类型：** 分享链接与伪静态简化

**变更说明：**
- 分享链接改为 `/d/index.php/{token}`（PATH_INFO，无 `?token=`）
- 伪静态仅需站点通用 `try_files`，无需 `/d/` 专用规则或 PHP 版本号配置
- 移除根目录分享 rewrite；旧版 `?token=` 链接自动 301 跳转

**数据库：** 无结构变更

---

### v1.0.56（2026-06-27）

**类型：** 分享 query 链接、PHP 下载修复、文件管理 UI

**变更说明：**
- 分享链接改为 `/d/index.php?token=`；stream 使用 query 参数
- Nginx 须为 `/d/` 下 PHP 配置解释器，防止源码被下载
- 移除顶部「分享此文件夹」；文件夹悬停分享图标（悬停显示文字）

**数据库：** 无结构变更

---

### v1.0.55（2026-06-27）

**类型：** 分享入口修复、伪静态简化、文件管理/预览优化

**变更说明：**
- 修复 `d/guard.php` VS_ROOT 重复定义；安全提醒页扩充法规说明
- 伪静态统一为 `d/index.php` 入口（Nginx 仅需一条 `/d` fallback）
- 文件夹列表悬停增加「分享」；预览弹窗可滚动、元数据排版调整

**数据库：** 无结构变更

---

### v1.0.54（2026-06-27）

**类型：** 分享 404 修复、伪静态文档、分享/预览 UI 优化

**变更说明：**
- 新增 `伪静态配置.md`，README 增加 Nginx/Apache 伪静态说明（解决 `/d/{token}` 404）
- 分享管理与预览弹窗按钮改为圆角矩形；分享列表显示储存来源
- 手机端分享卡片重设计；预览弹窗电脑端可滚动查看直链、替换与下载
- 「文件信息与操作」改用 SVG 图标；预览元数据增加储存字段

**数据库：** 无结构变更

---

### v1.0.49（2026-06-27）

**类型：** 预览本地化 + 账号/上传优化

**变更说明：**
- 预览库内置至 `assets/vendor/preview/`，图片/音视频原生渲染
- Word(.docx)/Excel(.xlsx)/MD/PDF 本地预览；播放按钮 SVG 图标；全屏预览
- 登录支持用户名或邮箱；账号设置可改用户名、电脑端左右布局
- 修复 HTML/JS/CSS/PHP 等文件上传与 MIME 识别

**数据库：** 无结构变更

---

### v1.0.48（2026-06-27）

**类型：** 在线预览重构 + 更新体验优化

**变更说明：**
- 移除 Flyfish Viewer（约 130MB 内置资源），改用 jsDelivr CDN 轻量预览库
- **支持在线预览：** PDF、Word(.docx)、Excel、Markdown、HTML、PHP、CSS、JS、音频、视频
- **不支持预览：** 压缩包及其他格式 → 详情弹窗仅显示文件图标
- 预览内容限制在预览框内，不横向溢出；手机端抽屉高度 80vh
- 预览区右上角支持放大（弹窗内扩展，非新窗口）
- 自定义音视频播放器（音乐图标 + 波形动画，非浏览器原生控件）
- 更新过程分步进度：下载 → 解压 → 覆盖 → 数据库（如有）
- 用户界面统一使用「云端」表述

**数据库：** 无结构变更

---

### v1.0.47（2026-06-27）

**类型：** 结构简化与体验优化

**变更说明：**
- 移除 `domain` 表，绑定子域名改存 `config.bound_domains` JSON
- 登录页「记住账号密码」：勾选后在浏览器本地保存并自动填充
- AWS S3、蓝奏云优享版、WebDAV 配置区标注「暂时不可用」
- 七牛云 / 腾讯云 / 阿里云 OSS 配置区增加「前往官网注册」入口

**数据库：** 升级时执行 `1.0.47` 迁移，自动迁移旧 domain 表数据

---

### v1.0.46（2026-06-27）

**类型：** 清理 — 密码重置机制

**变更说明：**
- 移除废弃的 `password_reset` 表及邮件链接 + token 一次性重置流程
- 忘记密码统一为 `/admin/forgot.php` 邮箱验证码方式
- 删除 `admin/reset.php`、项目内 `docs/flyfish-viewer.md`

**数据库：** 升级时执行 `1.0.46.sql` 删除 `password_reset` 表

---

### v1.0.45（2026-06-27）

**类型：** 热修复 — 文件上传

**变更说明：**
- 修复 `upload-queue.js` 在 v1.0.42 重构时误删 `uid()`，导致点击/拖拽上传时 JavaScript 报错、进度浮层不出现
- 本地、腾讯云、阿里云等所有储存的上传流程已恢复

---

### v1.0.44（2026-06-27）

**类型：** Flyfish Viewer 本地自托管

**变更说明：**
- 将 `@file-viewer/web-full@2.1.3` 完整静态资源内置至 `assets/vendor/flyfish-viewer/`
- 文件预览**不再依赖 jsDelivr 等外部 CDN**，内网/离线环境可直接使用
- Worker、WASM、字体等资源随主脚本同目录自动加载

---

### v1.0.43（2026-06-27）

**类型：** Flyfish Viewer 多格式在线预览

**变更说明：**
- 文件管理预览弹窗集成 [Flyfish Viewer](https://doc.flyfish.dev/)，纯前端预览 **150+** 种格式
- 覆盖 Office、PDF、CAD、压缩包、Markdown、代码、音视频、邮件、电子书等
- 新增 `admin/file-stream.php` 鉴权文件流；跨域云储存走同源代理，本地 PDF 支持 Range
- 点击或双击文件即可在弹窗内预览；预览资源内置至 `assets/vendor/flyfish-viewer/`

---

### v1.0.42（2026-06-27）

**类型：** 文件管理修复与提示体验

**变更说明：**
- 删除文件后列表即时刷新；若正在预览被删文件则自动关闭弹窗
- 预览框分享链接显示解码后的可读 URL（如中文路径）
- 电脑端预览弹窗加宽为横向布局，标题改为显示 **存储名**（stored_name）
- 文件管理页本地上传改走当前页 XHR，修复误报「响应异常」但文件已上传
- AJAX 响应增加容错解析；服务端输出 JSON 前清理缓冲
- 保存、复制、上传等成功提示改为顶部 **胶囊 Toast**，替代绿色长条

---

### v1.0.41（2026-06-27）

**类型：** 上传 UI 优化

**变更说明：**
- 上传完成后进度浮层自动淡出消失（约 0.7 秒）
- 最小化按钮改为白色 pill 样式，仅上传进行中且主动折叠时显示
- 上传失败保留 4 秒便于查看错误信息

---

### v1.0.40（2026-06-26）

**类型：** 本地储存直链简化

**变更说明：**
- 移除随机字母 slug 直链网关，本地分享链接改回 `{域名}/upload/路径/文件名`
- 删除 `public-file.php`、`LocalFileServe.php`、`storage_local_public_slug` 等
- 升级后执行 `1.0.40.sql` 并自动刷新已有本地文件外链

---

### v1.0.39（2026-06-26）

**类型：** 上传修复与全局进度

**变更说明：**
- 修复文件上传因 `FormData` 未创建而完全失败、进度条不动
- 上传进度改为全局浮层（所有后台页面可见），可折叠隐藏
- 桥接窗口在后台继续上传，切换页面不中断；返回文件管理仍可看进度

---

### v1.0.38（2026-06-26）

**类型：** 文件替换（重传）

**变更说明：**
- 文件预览抽屉新增「选择替换文件」
- 替换流程：**先删除**储存内旧文件，再上传新内容，**保留 stored_name 与外链**
- 适用于本地储存及 S3/OSS/COS/七牛/WebDAV/蓝奏等云储存

---

### v1.0.37（2026-06-26）

**类型：** 本地直链修复与上传体验

**变更说明：**
- 新增 `public-file.php`，根目录 `.htaccess` 转发 `/{slug}/{文件名}`，修复外链 404
- Nginx 用户需参考 `install/nginx-local-storage.conf.example` 配置转发
- 文件上传右下角显示进度浮层，多文件各自独立进度条
- 补充本地直链 slug 规则文档（a–z 随机单字母，持久保存）

---

### v1.0.36（2026-06-26）

**类型：** 热修复 — 数据库迁移

**变更说明：**
- 修复 `1.0.35.sql` 写入 `config` 表时未使用 `{prefix}` 占位符，导致升级报 `Table 'xxx.config' doesn't exist`
- `DatabaseMigrator` 增加表前缀校验，防止迁移脚本遗漏前缀
- 从 1.0.35 升级失败的用户，更新到本版后会自动重试 `1.0.35` 结构更新

---

### v1.0.35（2026-06-26）

**类型：** 文件管理列表修复与本地直链

**变更说明：**
- 修复列表视图列错位，正确显示类型、储存、大小、操作
- 手机端列表在文件名旁显示大小；预览抽屉与弹窗高度统一为 **75vh**
- 本地储存对外链接改为 `/{随机单字母}/{文件名}`，通过网关读取，不暴露 `upload/` 路径
- 保存储存设置或首次本地上传时自动部署直链网关

---

### v1.0.34（2026-06-26）

**类型：** 文件管理 UI 与储存配置体验优化

**变更说明：**
- 修复文件管理页列表/网格视图 UI 错位与异常
- 优化新建文件夹、重命名弹窗关闭按钮样式
- 点击文件/图片弹出预览：电脑端居中弹窗，手机端底部抽屉；支持复制分享链接、新窗口打开与下载
- 储存配置各字段增加**中英文标签**与**中文填写说明**，便于普通用户配置密钥、Bucket 等

---

### v1.0.33（2026-06-26）

**类型：** 更新记录修复

**变更说明：**
- 修复 `update-log.json` 中 1.0.31 条目缺失 `version` 字段，导致 1.0.31 站点检测更新时出现 PHP Warning
- `UpdateLog` 排序时跳过无效版本记录，增强容错
- 发行包继续包含七种储存驱动完整 `vendor/` 依赖

---

### v1.0.32（2026-06-26）

**类型：** 文件管理与升级流程修复

**变更说明：**
- 修复文件管理页进入即显示「松开鼠标以上传文件」遮罩
- 修复系统设置 → 储存配置中七个子板块无法折叠
- 本地储存访问 URL 随当前站点域名自动生成，无需手动配置
- 移除各储存「URL 后缀参数」配置项
- 在线更新改为**逐版升级**：每次仅安装下一个版本，避免跳过中间版本的数据库迁移

---

### v1.0.31（2026-06-26）

**类型：** 储存 KEY 修正与文件管理增强

**变更说明：**
- 七种储存 KEY 统一为 **1–7**（WebDAV 由 9 改为 7）
- 文件管理：批量上传、拖拽上传、文件夹重命名
- 系统设置各储存板块新增「测试连接」
- 修正 README 目录结构说明

---

### v1.0.30（2026-06-26）

**类型：** 文件管理与七种储存配置

**变更说明：**
- 系统设置新增第四折叠板块「储存配置」，七种储存可同时启用，各含独立子折叠表单
- 全局上传命名：保留原名、全局递增、时间戳、毫秒时间戳、UUID、日期递增
- 文件管理页：创建文件夹并绑定储存，子文件夹继承；支持大图标/小图标/列表视图
- 数据库新增 `file_folder`、`file_item` 表及储存相关 `config` 字段（迁移 `1.0.30.sql`）

---

### v1.0.29（2026-06-26）

**类型：** 侧边栏新增 AI 大类

**变更说明：**
- 侧边栏新增 **AI** 大类，子菜单：DeepSeek、ChatGPT、Claude、Gemini
- 各子页面为占位页，显示「功能开发中」

---

### v1.0.28（2026-06-26）

**类型：** 更新记录改为云端读取

**变更说明：**
- 系统升级页更新记录优先从 Gitee 云端拉取 `update-log.json`
- 版本检测与数据库变更判断同步使用云端记录

---

### v1.0.27（2026-06-26）

**类型：** 关于页信息布局统一

**变更说明：**
- 关于页每项信息独占一行，名称在左、内容在右
- 电脑端两列展示，手机端单列展示

---

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
- 在线更新时会**自动创建** `data/update/` 临时目录（无需纳入仓库、无需手动上传）
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
- `admin/files/`、`admin/cdn/*`、`admin/archive/*`
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
