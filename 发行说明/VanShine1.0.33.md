# VanShine 1.0.33 发行说明

**发行日期：** 2026-06-26  
**压缩包名称：** `VanShine1.0.33.zip`

---

## 版本概述

修复更新记录数据缺陷导致的 PHP 报错，并增强 `UpdateLog` 对异常数据的容错能力。

---

## 主要变更

### 在线更新

- 修复 `update-log.json` 中 **1.0.31** 条目曾缺失 `version` 字段的问题
- 1.0.31 站点检测更新时不再出现 `Undefined array key "version"` 与 `version_compare()` 相关 Warning
- `UpdateLog::allVersions()` 排序前跳过无有效 `version` 的记录，避免类似数据问题再次触发报错

### 储存驱动

- 发行压缩包继续包含七种储存驱动完整代码及 `vendor/` 依赖，无需手动 `composer install`

---

## 升级说明

- **无数据库结构变更**
- 若当前为 1.0.32，可直接在线更新；若仍为 1.0.31，亦可通过本版本修复更新检测报错

---

https://gitee.com/xunjinlu/VanShine
