# VanShine CDN 模块（core/cdn）

CDN 对接采用与 `core/Storage` 相同的**分产品独立目录**架构：每种 CDN 平台拥有独立文件夹，内含对接代码、配置键定义、Composer 依赖（`vendor/`）。

## 已对接

| 目录 | 产品 | 说明 |
|------|------|------|
| [edgeone](./edgeone/README.md) | 腾讯云 EdgeOne（TEO） | Open API 全量 Action 封装 |

## 引入方式

按需加载对应产品入口，**无需**在项目根目录安装 Composer。凭证仅存 `vs_config`，通过 `EdgeOne::create()` 读取：

```php
require VS_ROOT . '/core/cdn/edgeone/EdgeOne.php';
EdgeOne::load();

$eo = EdgeOne::create();
$zones = $eo->zone->describeZones(array('Offset' => 0, 'Limit' => 20));
```

配置键前缀：`cdn_edgeone_`（详见 [edgeone/README.md](./edgeone/README.md)）。

## 依赖安装

```bash
cd core/cdn/edgeone && composer install
```

## 维护说明

- 新增 CDN 产品：在 `core/cdn/` 下新建目录，遵循相同结构，并更新本 README
- EdgeOne API 变更：编辑 `edgeone/data/actions-registry.php` 后执行 `php build-apis.php`
