# VanShine 1.0.26 发行说明

**发行日期：** 2026-06-26  
**压缩包名称：** `VanShine1.0.26.zip`

---

## 更新内容

### 在线更新临时文件清理

- 系统升级完成后，自动清空 `storage/update/` 下的下载 ZIP 与解压目录
- 无论升级成功或失败，均会在结束时清理临时文件，避免残留 `vanshine-update.zip`

### 发行包排除运行时目录

- 新增 `.gitattributes`，`git archive` 打包时排除 `storage/`、`release/` 目录
- 在线更新覆盖文件时，整棵 `storage/` 目录受保护，不会被发行包覆盖

---

## 升级说明

- 无数据库结构变更
- 若服务器上仍有旧版残留的 `storage/update/vanshine-update.zip`，升级至本版本后会自动清理

---

https://gitee.com/xunjinlu/VanShine
