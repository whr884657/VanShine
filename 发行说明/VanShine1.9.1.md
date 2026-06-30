# VanShine 1.9.1 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.9.1.zip`  
**版本类型：** 小版本（紧急修复 + 体验补全）

---

## 变更内容

### 修复规则列表页崩溃

- 修复 `rules-page.php` 在 PHP 8.2+ 下因双引号字符串 `${http.request.host}` 被解析为变量导致的 **Deprecated + Fatal error**
- 规则列表、摘要统计、条件预览恢复正常

### 中文说明与新手引导

- 16 种匹配类型均增加中文标签与 hint（如 HOST、URL 路径、客户端 IP 等）
- 回源协议、TLS 加密套件、301/302 跳转、Token 鉴权类型等改为中文选项
- 规则编辑器顶部增加新手引导；各表单字段显示用途说明

---

- **无数据库变更**：覆盖文件即可

---

## 下载

- Gitee：https://gitee.com/xunjinlu/VanShine/releases/download/v1.9.1/VanShine1.9.1.zip
