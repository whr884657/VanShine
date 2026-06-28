# VanShine EdgeOne CDN 对接（core/cdn/edgeone）

腾讯云 **EdgeOne** Open API 后端 + 后台管理页对接。

## 后台入口

CDN 侧边栏仅 **EdgeOne**、**ESA** 两项；EdgeOne 内导航：

| 端 | 布局 |
|----|------|
| 电脑端（≥992px） | 顶部**横向大类**导航；多子项大类悬停**下拉菜单** |
| 手机端 | **折叠分组**导航，默认仅展开当前所在分组 |

| 分组 | 页面 |
|------|------|
| 总览 | 概览 |
| 站点与域名 | 站点管理、域名加速、七层加速、别称域名、DNS 记录 |
| 内容与函数 | 内容管理、边缘函数 |
| 安全与证书 | 安全策略、证书管理、源站防护 |
| 网络与负载 | 四层代理、负载均衡 |
| 监控与计费 | 数据分析、日志服务、套餐计费 |
| 配置与扩展 | 配置版本、扩展功能 |

路径前缀：`/admin/cdn/edgeone/`（入口：`index.php`）

## UI 展示

- **概览**（`index.php`）：账号级统计页，无「当前站点」选择器；筛选条件存 Session（地址栏无查询串）；统计图/配额 **AJAX 异步加载**；电脑端双列折线图、多站点右上角图例
- **数据分析**（`analytics.php`）：可选指标、时间范围、粒度，KPI + 折线图；筛选存 Session
- **套餐计费**（`billing.php`）：套餐卡片、绑定站点、今日/本月用量与计费趋势；筛选存 Session
- EdgeOne 内页切换、查询表单、切换站点为**局部刷新**（`fragment=1`），不整页 reload，**不在 URL 拼接筛选参数**
- 其他功能页 API 结果经 `data-view.php` 渲染为表格/卡片/配额进度，**不展示原始 JSON**

## 配置（系统设置 → CDN 配置）

腾讯云 **SecretId / SecretKey / Region** 与 **COS 储存** 共用（`tencent_secret_*`），禁止在代码中写密钥。

```php
require VS_ROOT . '/core/cdn/edgeone/EdgeOne.php';
EdgeOne::load();
$eo = EdgeOne::create(); // 凭证来自 vs_config
```

## 接入点

每次创建客户端时：广州 / 上海 / 重庆 **随机主用**，失败时依次尝试就近 / 南京 / 北京 / 成都 / 香港后备域名。

## 依赖

```bash
cd core/cdn/edgeone && composer install
```

## 维护

编辑 `data/actions-registry.php` 后执行 `php build-apis.php` 同步 API 分类类。

## 调用注意

- 无参 Action 请求体须为 JSON 对象 `{}`；`EdgeOneClient` 自动处理空参数数组。
- `DescribeContentQuota`、`DescribeBillingData` 等接口需传 `ZoneId` / `Interval` / `ZoneIds`。
- 数据分析类 API 使用 `ZoneIds`（数组），勿传 `ZoneId`。
- `DescribeTimingL7AnalysisData` 的 `Filters` 使用 `{ Key, Operator, Value }`（域名：`Key=domain`），勿用 `Name`/`Values`。
- 边缘函数页已移除 EdgeKV；域名加速页已移除共享 CNAME。
