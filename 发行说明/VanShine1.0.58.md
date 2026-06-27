# VanShine 1.0.58 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.58.zip`

---

## 变更内容

### 分享链接

- 新标准：`https://域名/d/?token={32位token}`（URL 不含 `index.php`）
- 下载流：`/d/?token=...&stream=1&file=ID`
- 旧版 `/d/index.php?token=`、`/d/index.php/{token}` 自动 301 到 `/d/?token=`
- 伪静态仍只需站点通用 `try_files`，无需额外规则

### 分享页 / 安全提醒页

- 底部增加站点版权、ICP 备案、公安备案（读取系统配置）

### 文件管理

- 文件夹/文件操作按钮：默认图标，悬停显示「分享」「重命名」「删除」
- 每个文件行增加分享按钮，可不打开预览直接创建分享

### 预览弹窗

- 预览区 + 文件信息与操作区作为整体在弹窗内滚动
- 修复电脑端/手机端底部替换、下载按钮被遮挡

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.58/VanShine1.0.58.zip
