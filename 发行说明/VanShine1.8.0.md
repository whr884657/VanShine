# VanShine 1.8.0 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.8.0.zip`  
**版本类型：** 大版本（规则引擎 API 补全 + 全面人性化）

---

## 变更内容

### 规则引擎 · 对齐腾讯云官方文档

1. **移除开发者区别对待**：删除「开发者选项」「高级表达式」「高级 JSON 折叠」；所有功能均以表单/下拉/开关呈现
2. **补全缺失操作**（对照文档 90438 / L7Acc API）：
   - 内容压缩 `ContentCompression`
   - 单连接下载限速 `ResponseSpeedLimit`
   - 源站卸载 `Shield`、源站故障转移 `SiteFailover`
   - 设置内容标识符 `SetContentIdentifier`、回源鉴权 `OriginAuthentication`
3. **新增匹配类型**：客户端运营商（`${http.request.ip.isp}`）
4. **常用操作表单化**：HSTS、修改源站、Host Header 重写、HTTP 应答等
5. **未知操作保留**：腾讯云控制台已有但目录未收录的操作，以可编辑参数块展示，保存时不丢失
6. **复杂条件保护**：无法完全解析的条件保留原表达式，修改后才按新配置保存

### 界面

- 规则列表增加 **IF / 子规则 / 操作** 数量摘要与首条条件预览
- 手机端编辑器底栏：**取消 | 仅保存 | 保存并发布** 单行三列排列

---

- **无数据库变更**：覆盖文件即可

---

## 下载

- Gitee：https://gitee.com/xunjinlu/VanShine/releases/download/v1.8.0/VanShine1.8.0.zip
- GitHub：https://github.com/whr884657/VanShine/releases/download/v1.8.0/VanShine1.8.0.zip
