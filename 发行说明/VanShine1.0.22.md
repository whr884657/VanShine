# VanShine 1.0.22 发行说明

**发行日期：** 2026-06-26  
**压缩包名称：** `VanShine1.0.22.zip`

---

## 版本概述

优化在线更新的 HTTPS 与下载策略，移除本地 CA 证书包依赖，使长期更新更稳定。

---

## 背景说明

- **站点 HTTPS**（您域名上的 SSL 证书）与 **系统访问 Gitee 下载更新** 是两回事
- 此前内置的 `cacert.pem` 是 Mozilla **CA 根证书集合**，不是网站证书，但会随时间过时，并受宝塔 `open_basedir` 影响

---

## 优化内容

### 下载方式

- **唯一来源**：Gitee 发行版直链（与浏览器下载相同）  
  `https://gitee.com/xunjinlu/VanShine/releases/download/v{版本}/VanShine{版本}.zip`
- 已移除「仓库快照」备用源（易返回 HTML 非 ZIP）
- 已移除 `core/cacert.pem`

### 安全策略

- 出站请求仅限 Gitee 白名单域名（`gitee.com`、`foruda.gitee.com` 等）
- 下载完成后校验 **ZIP 文件头**（`PK` 魔数）再解压
- 仍不覆盖 `config/database.php`

---

## 升级说明

- 无数据库结构变更

---

https://gitee.com/xunjinlu/VanShine
