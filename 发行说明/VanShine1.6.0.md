# VanShine 1.6.0 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.6.0.zip`  
**版本类型：** 大版本（EdgeOne 规则引擎完整重做）

---

## 变更内容

### 规则引擎完整重做

对齐腾讯云 [规则引擎](https://cloud.tencent.com/document/product/1552/70901) 与 [匹配类型与操作](https://cloud.tencent.com/document/product/1552/90438) 文档，VanShine 后台规则引擎由「简化抽屉」升级为**全功能编辑器**。

1. **全屏桌面弹窗**（约 1120×860，与域名/筛选弹窗一致）；手机端底部抽屉（约 92vh）
2. **匹配条件**：16 种类型（HOST、URL Path/Full、查询字符串、文件后缀/名、HTTP 请求/响应头、地理位置、协议、IP、方法、Cookie、响应状态码、全部站点等）+ 6 种运算符（等于/不等于/存在/不存在/正则/正则不匹配）
3. **操作（THEN）**：40+ 种，含节点缓存、浏览器缓存、HTTP/2、QUIC、智能压缩、强制 HTTPS、修改 HTTP 头、Token 鉴权、修改源站等；分类面板 + 关键字搜索
4. **规则结构**：多 IF 条件块、SubRules 子规则、高级 Condition 表达式（与 API 字段一致，复杂规则可直接粘贴）
5. **保存机制**：`rule_json` 提交完整 `RuleEngineItem`，对接 [CreateL7AccRules](https://cloud.tencent.com/document/api/1552/115822) / [ModifyL7AccRule](https://cloud.tencent.com/document/api/1552/115818)；编辑时整规则加载，**不再**用简化表单覆盖多分支/多操作规则

> 规则优先级：列表从上到下执行，**越靠下优先级越高**。详见 [ModifyL7AccRulePriority](https://cloud.tencent.com/document/api/1552/117133)。

### 新增文件

| 文件 | 说明 |
|------|------|
| `admin/cdn/edgeone/includes/rules-catalog.php` | 匹配类型、运算符、操作目录 |
| `admin/cdn/edgeone/includes/rules-editor.php` | 全屏编辑器 HTML |
| `assets/js/edgeone-rules-editor.js` | 编辑器交互逻辑 |

---

- **无数据库变更**：覆盖文件即可
- 从 v1.5.0 升级：规则列表页不变；创建/编辑入口改为全屏编辑器

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.6.0/VanShine1.6.0.zip
