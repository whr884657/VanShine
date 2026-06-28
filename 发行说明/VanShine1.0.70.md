# VanShine 1.0.70 发行说明

**发行日期：** 2026-06-27  
**压缩包名称：** `VanShine1.0.70.zip`

---

## 变更内容

### EdgeOne API 修复

- API 版本由已下线的 `2022-01-06` 升级为 `2022-09-01`，修复 `DescribeAccelerationDomains`、`DescribeFunctions`、`DescribeL4Proxy`、`DescribePlans` 等接口报错
- 内容刷新/预热、计费流量查询补充 `StartTime` / `EndTime` 参数
- 各功能页 API 调用独立容错，单个接口失败不再导致整页崩溃

### CDN 侧边栏与导航

- CDN 侧边栏恢复为 **EdgeOne**、**ESA** 两项；功能模块通过页内横向标签切换
- 切换标签时侧边栏高亮保持不变

### 站点与界面

- 站点列表/概览展示 **站点备注**（`AliasZoneName`）、域名、ZoneId
- 运行状态、加速状态、接入方式、域名状态等改为中文显示
- 卡片布局增加间距，避免堆叠

---

## 升级说明

- **无数据库变更**：覆盖文件即可，保留 `config/database.php`
- 若从 1.0.69 升级，无需执行额外迁移

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.0.70/VanShine1.0.70.zip
