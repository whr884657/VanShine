# VanShine 1.0.21 发行说明

**发行日期：** 2026-06-26  
**压缩包名称：** `VanShine1.0.21.zip`

---

## 版本概述

修复宝塔等 `open_basedir` 环境下版本检测失败、升级弹窗不出现的问题。

---

## 问题原因

v1.0.19 起 `Updater` 会检测系统 CA 证书路径（如 `/etc/ssl/certs/ca-certificates.crt`）。在宝塔面板等限制目录的环境中，`is_file()` 触发 **open_basedir Warning**，污染 AJAX 的 JSON 响应，导致：

- 系统升级页显示「网络异常」
- 登录后更新弹窗无法弹出
- 关于页（服务端渲染）仍可能显示有更新

---

## 修复内容

- 访问 CA 路径前先校验是否在 `open_basedir` 允许范围内
- **优先**使用项目内 `core/cacert.pem`
- 更新 API 关闭 display_errors，避免 Warning 混入 JSON

---

## 升级说明

- 无数据库结构变更
- 建议从 1.0.19/1.0.20 在线或手动升级至本版本

---

https://gitee.com/xunjinlu/VanShine
