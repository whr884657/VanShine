# VanShine 1.10.1 发行说明

**发行日期：** 2026-07-01  
**压缩包名称：** `VanShine1.10.1.zip`  
**版本类型：** 小版本（紧急修复）

---

## 变更内容

### 修复规则列表页 Warning 刷屏

- 修复 `vs_edgeone_humanize_condition()` 中第 112 行正则括号转义错误
- 错误信息：`preg_match(): Compilation failed: unmatched closing parenthesis`
- 规则列表页条件摘要恢复正常显示

---

- **无数据库变更**：覆盖 `admin/cdn/edgeone/includes/rules-page.php` 及版本文件即可

---

## 下载

- Gitee：https://gitee.com/xunjinlu/VanShine/releases/download/v1.10.1/VanShine1.10.1.zip
