# VanShine EdgeOne CDN 对接（core/cdn/edgeone）

腾讯云 **EdgeOne** Open API 后端 + 后台管理页对接。

## 后台入口

CDN 侧边栏仅 **EdgeOne**、**ESA** 两项；EdgeOne 内通过页内横向标签切换下列页面：

| 页面 | 路径 |
|------|------|
| 概览 | `/admin/cdn/edgeone/index.php` |
| 站点管理 | `/admin/cdn/edgeone/zones.php` |
| 域名加速 | `/admin/cdn/edgeone/domains.php` |
| 内容刷新 | `/admin/cdn/edgeone/content.php` |
| 安全加速 | `/admin/cdn/edgeone/security.php` |
| 边缘函数 | `/admin/cdn/edgeone/edge.php` |
| 四层代理 | `/admin/cdn/edgeone/l4.php` |
| 监控日志 | `/admin/cdn/edgeone/monitor.php` |

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

- 无参 Action（如 `DescribeContentQuota`）请求体须为 JSON 对象 `{}`；`EdgeOneClient` 会自动将空参数数组转换，避免 `[]` 触发非法 JSON 错误。
- `DescribeBillingData` 必填 `Interval`（`min` / `5min` / `hour` / `day`）与 `ZoneIds`（站点 ID 或 `*`）。
