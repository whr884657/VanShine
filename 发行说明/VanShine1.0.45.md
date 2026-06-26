# VanShine 1.0.45 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.45.zip`

---

## 版本概述

热修复：恢复 v1.0.42 起失效的文件上传功能（选择文件、拖拽上传均无反应）。

---

## 问题原因

`assets/js/upload-queue.js` 在 v1.0.42 添加 JSON 解析 helper 时**误删**了 `uid()` 函数。  
上传队列 `enqueue()` 创建任务 ID 时调用 `uid()` 抛出 `ReferenceError`，导致：

- 右下角上传进度浮层不出现
- 本地 / 腾讯云 / 阿里云等所有储存上传均无响应

---

## 修复内容

- 恢复 `uid()` 函数
- `files.js` 弹窗事件绑定增加空节点判断，避免类似中断

---

## 升级说明

- **无数据库结构变更**
- 升级后 **Ctrl+F5** 强刷后台

---

https://gitee.com/xunjinlu/VanShine
