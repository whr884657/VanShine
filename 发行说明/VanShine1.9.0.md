# VanShine 1.9.0 发行说明

**发行日期：** 2026-06-29  
**压缩包名称：** `VanShine1.9.0.zip`  
**版本类型：** 大版本（规则引擎控制台级体验）

---

## 变更内容

### 去乱码 · 全部人类可读

- 左侧**规则导航**、IF 标题、列表条件预览：显示「HOST 等于 api.example.com」而非 `${http.request.host} in ['…`
- 复杂条件未改动前仍保留腾讯云原配置，但界面不再展示代码表达式

### 表单化（对齐 CreateL7AccRules 示例）

- **自定义 Cache Key**：全 URL、忽略大小写、查询字符串/请求头/Cookie 等全部开关+下拉
- **节点缓存 / 浏览器缓存**：遵循源站、不缓存、自定义时间；时间可选秒/分/时/天
- **状态码缓存 TTL**：多行「状态码 + 缓存时间」
- **HTTP 头修改**：添加/设置/删除 + 名称/值 行编辑器
- **智能压缩**：Gzip / Brotli 多选
- **Token 鉴权、回源 URL 重写、回源请求参数、错误页面** 等改为表单

### 嵌套规则

- SubRules 显示为 **嵌套 IF / ELSE IF**，与腾讯云控制台 IF-ELSE IF-ELSE 逻辑一致

---

- **无数据库变更**：覆盖文件即可

---

## 下载

- Gitee：https://gitee.com/xunjinlu/VanShine/releases/download/v1.9.0/VanShine1.9.0.zip
