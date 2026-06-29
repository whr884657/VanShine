# VanShine 1.5.0 发行说明

**发行日期：** 2026-06-30  
**压缩包名称：** `VanShine1.5.0.zip`  
**版本类型：** 大版本（EdgeOne 规则引擎页整页改版）

---

## 变更内容

### 规则引擎（原「七层加速」）

对齐腾讯云 EdgeOne 控制台命名：**规则引擎** —— 针对特定域名生效的差异化加速配置。

1. **新页面** `admin/cdn/edgeone/rules.php`，导航与站点列表快捷入口均已更新
2. **删除** `l7.php`，**不做**旧路径 301 或兼容页
3. **规则列表 UI**：序号、拖拽排序、启用开关、编辑 / 复制 / 删除、搜索名称/注释
4. **创建/编辑抽屉**：规则名称、注释、HOST 或全站匹配；常用操作（智能压缩、强制 HTTPS、HTTP/2、节点不缓存）
5. **API 对接**：[DescribeL7AccRules](https://cloud.tencent.com/document/api/1552/115820)、[CreateL7AccRules](https://cloud.tencent.com/document/api/1552/115822)、[ModifyL7AccRule](https://cloud.tencent.com/document/api/1552/115818)、[DeleteL7AccRules](https://cloud.tencent.com/document/api/1552/115821)、[ModifyL7AccRulePriority](https://cloud.tencent.com/document/api/1552/117133)

> 规则优先级：列表从上到下执行，**越靠下优先级越高**；同时命中多条规则时，下方规则覆盖上方。详见 [规则引擎概览](https://cloud.tencent.com/document/product/1552/70901)。

> 首版表单仅覆盖常用操作；含多分支或复杂操作的规则，保存后可在腾讯云控制台继续编辑。

### 移除别称域名入口

腾讯别称域名功能尚在测试阶段，VanShine **删除** `alias.php` 及导航入口；底层 SDK API 仍保留，待官方正式发布后再考虑开放。

---

## 升级说明

- **无数据库变更**：覆盖文件即可
- 若书签或文档仍指向 `l7.php` / `alias.php`，请改为 `rules.php`；旧文件已不存在

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.5.0/VanShine1.5.0.zip
