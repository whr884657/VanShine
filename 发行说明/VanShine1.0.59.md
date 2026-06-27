# VanShine 1.0.59 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.59.zip`

---

## 变更内容

### 分享流接口（安全加固）

- 合并 `stream-handler.php` 至唯一入口 **`/d/stream.php`**，删除重复与旧版兼容逻辑
- 移除 `index.php?stream=1`、PATH `/stream`、旧链接 301 跳转等兼容代码
- 下载/预览使用 **HMAC 签名短时 URL**（`s/f/e/k`），URL 中**不含**分享 `token`
- 须先在分享页建立 **Session 会话** 后方可拉流；带密码分享仍校验解锁状态
- 流接口增加 `Cache-Control: no-store` 等安全响应头

### 分享页

- 预览 JS 改为使用服务端下发的 `shareStreams` 映射，不再拼接含 token 的流地址
- 安全提醒页/分享页统一安全响应头

### 文档

- `伪静态配置.md`、分享管理提示、README 版本记录已同步

---

## 升级说明

- **无数据库变更**，覆盖文件即可（保留 `config/`）
- 旧版 `/d/?token=...&stream=1` 链接**不再有效**，需使用新版分享页生成的下载/预览

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.59/VanShine1.0.59.zip
