# 腾讯云 EdgeOne（边缘安全加速平台 EO）API 对接文档

> 本文档整理自腾讯云官方 API 文档，供 VanShine 后续对接 EdgeOne 使用。  
> 官方文档入口：[API 概览](https://cloud.tencent.com/document/api/1552/80731)  
> 文档更新时间参考：2026-06-02（以腾讯云官网为准）

---

## 1. 产品说明

**EdgeOne（边缘安全加速平台 EO）** 提供站点加速、安全防护、边缘函数、四层代理、DNS、证书、日志与计费等能力。所有 Open API 通过 HTTPS 调用，推荐使用 **签名方法 v3（TC3-HMAC-SHA256）** 与 **POST + application/json**。

| 项目 | 值 |
|------|-----|
| 产品英文名 | teo |
| API Version | `2022-09-01`（以各接口文档为准；`2022-01-06` 已下线） |
| 推荐接入域名 | `teo.tencentcloudapi.com` |
| 密钥管理 | [云 API 密钥控制台](https://console.cloud.tencent.com/capi) |

---

## 2. 调用方式

### 2.1 请求结构

参考：[请求结构](https://cloud.tencent.com/document/api/1552/80723)

| 项 | 说明 |
|----|------|
| 通信协议 | HTTPS |
| 推荐请求方法 | **POST** |
| 推荐 Content-Type | `application/json`（须使用签名 v3） |
| 字符编码 | UTF-8 |
| POST（v3）请求体上限 | 10 MB |

**服务地址（Endpoint）**

| 接入地域 | 域名 |
|----------|------|
| 就近接入（推荐，非金融区） | `teo.tencentcloudapi.com` |
| 华南（广州） | `teo.ap-guangzhou.tencentcloudapi.com` |
| 华东（上海） | `teo.ap-shanghai.tencentcloudapi.com` |
| 华东（南京） | `teo.ap-nanjing.tencentcloudapi.com` |
| 华北（北京） | `teo.ap-beijing.tencentcloudapi.com` |
| 西南（成都） | `teo.ap-chengdu.tencentcloudapi.com` |
| 西南（重庆） | `teo.ap-chongqing.tencentcloudapi.com` |
| 港澳台（香港） | `teo.ap-hongkong.tencentcloudapi.com` |

> 域名是 API 接入点，不代表资源所在地域；接口支持的 Region 见 [公共参数 - 地域列表](https://cloud.tencent.com/document/api/1552/80724)。

**EO 常用 Region**

| 地域 | Region 取值 |
|------|-------------|
| 华南（广州） | `ap-guangzhou` |
| 华东（上海） | `ap-shanghai` |
| 西南（重庆） | `ap-chongqing` |

---

### 2.2 公共参数（签名 v3）

参考：[公共参数](https://cloud.tencent.com/document/api/1552/80724)

签名 v3 下，公共参数放在 **HTTP Header** 中：

| Header | 必选 | 说明 |
|--------|------|------|
| `X-TC-Action` | 是 | 接口名称，如 `CreateZone` |
| `X-TC-Version` | 是 | API 版本，如 `2022-09-01` |
| `X-TC-Timestamp` | 是 | 当前 UNIX 时间戳；与服务器相差超过 5 分钟会签名过期 |
| `X-TC-Region` | 视接口 | 地域；部分接口可不传 |
| `Authorization` | 是 | `TC3-HMAC-SHA256 Credential=...` 签名串 |
| `X-TC-Token` | 否 | 临时密钥 Token |
| `X-TC-Language` | 否 | `zh-CN` / `en-US` |

**请求示例（JSON POST）**

```http
POST https://teo.tencentcloudapi.com/
Content-Type: application/json
Host: teo.tencentcloudapi.com
X-TC-Action: DescribeZones
X-TC-Version: 2022-09-01
X-TC-Timestamp: 1710000000
X-TC-Region: ap-guangzhou
Authorization: TC3-HMAC-SHA256 Credential=AKID***/2026-06-27/teo/tc3_request, SignedHeaders=content-type;host, Signature=...

{"Offset":0,"Limit":20}
```

---

### 2.3 签名方法 v3

参考：[签名方法 v3](https://cloud.tencent.com/document/api/1552/80725)

1. 拼接规范请求串 `CanonicalRequest`
2. 拼接待签名字符串 `StringToSign`
3. 使用 `SecretKey` 计算 `TC3-HMAC-SHA256` 签名
4. 拼接 `Authorization` Header

**要点**

- `service` 固定为 **`teo`**
- 推荐使用 [API Explorer](https://console.cloud.tencent.com/api/explorer) 调试与生成签名
- 推荐使用腾讯云官方 SDK（PHP / Go / Python 等），避免手写签名

---

### 2.4 返回结果

参考：[返回结果](https://cloud.tencent.com/document/api/1552/80727)

**成功示例**

```json
{
  "Response": {
    "RequestId": "b5b41468-520d-4192-b42f-595cc34b6c1c",
    "...": "各接口自定义字段"
  }
}
```

**失败示例**

```json
{
  "Response": {
    "Error": {
      "Code": "AuthFailure.SignatureFailure",
      "Message": "The provided credentials could not be validated."
    },
    "RequestId": "ed93f3cb-f35e-473f-b9f3-0d451b8b79c6"
  }
}
```

| 说明 | 内容 |
|------|------|
| HTTP 状态码 | 请求被正常处理时通常为 **200**（含业务错误） |
| `RequestId` | 每次请求唯一 ID，工单排查必备 |
| `Error.Code` | 错误码，见官方「错误码」章节 |
| 响应体上限 | JSON 最大约 **50 MB** |

---

### 2.5 参数类型

参考：[参数类型](https://cloud.tencent.com/document/api/1552/80728)

| 类型 | 说明 |
|------|------|
| String | 字符串 |
| Integer | 整型（无符号 64 位以内） |
| Boolean | 布尔 |
| Float / Double | 浮点 |
| Date | 日期，如 `2022-01-01` |
| Timestamp | 时间，如 `2022-01-01 00:00:00` |
| Timestamp ISO8601 | 如 `2022-01-01T00:00:00+08:00` |
| Binary | 二进制（特定协议） |

---

## 3. VanShine 对接建议

1. **密钥**：SecretId / SecretKey 存配置表或环境变量，禁止入库到公开仓库。
2. **封装**：建议单独实现 `TeoClient`（签名 + HTTP + 错误解析），Action 作为方法名映射。
3. **Region**：站点类接口通常传 `ap-guangzhou` 或与站点实际地域一致。
4. **限频**：默认多为 **20 次/秒**（维度：API + 接入地域 + 子账号），数据分析类接口更高，超限需退避重试。
5. **幂等**：创建类接口注意记录 `ZoneId` / `DomainId` 等，避免重复创建。
6. **JSON 请求体**：`Content-Type: application/json` 时 body 必须是 **对象** `{}`；空参数勿传 `[]`，否则返回「请求内容不是合法的 Json 格式」。VanShine `EdgeOneClient` 已自动处理。
7. **DescribeBillingData**：必填 `StartTime` / `EndTime` / `MetricName` / `ZoneIds`；`Interval` 可选，不传时由 API 根据时间跨度自动推算颗粒度。
8. **数据分析类接口**（如 `DescribeTimingL7AnalysisData`）使用 `ZoneIds` 数组，勿传 `ZoneId`；`Interval` 可选，不传时自动推算。
9. **DescribeTimingL7AnalysisData 的 Filters** 使用 `QueryCondition` 结构：`Key` / `Operator` / `Value`（如域名筛选 `{ "Key": "domain", "Operator": "equals", "Value": ["www.example.com"] }`），**不是** `Name` / `Values`。
10. **缓存命中次数**无独立 MetricName；使用 `l7Flow_request` + `Filters: [{ "Key": "cacheType", "Operator": "equals", "Value": ["hit"] }]`。
11. **DescribeIdentifications** 必填 `Filters`（如 `zone-name`，该接口字段名与数据分析不同）；**CheckCnameStatus** 必填 `RecordNames`（加速域名列表）。
11. **DescribeL7AccRules** 仅需 `ZoneId`，勿传已废弃的 `Entity` 参数。

---

## 4. API 接口索引

> 下表「官方文档」链接格式：`https://cloud.tencent.com/document/api/1552/{文档ID}`  
> 完整分类与频率限制以 [API 概览](https://cloud.tencent.com/document/api/1552/80731) 为准。

### 4.1 站点相关

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateZone | 创建站点 | 20 | [80719](https://cloud.tencent.com/document/api/1552/80719) |
| DescribeIdentifications | 查询站点的验证信息 | 20 | [80714](https://cloud.tencent.com/document/api/1552/80714) |
| ModifyZone | 修改站点 | 20 | [80709](https://cloud.tencent.com/document/api/1552/80709) |
| DeleteZone | 删除站点 | 20 | [80717](https://cloud.tencent.com/document/api/1552/80717) |
| ModifyZoneStatus | 切换站点状态 | 20 | [80707](https://cloud.tencent.com/document/api/1552/80707) |
| CheckCnameStatus | 校验域名 CNAME 配置状态 | 20 | [94491](https://cloud.tencent.com/document/api/1552/94491) |
| IdentifyZone | 认证站点 | 20 | [80712](https://cloud.tencent.com/document/api/1552/80712) |
| DescribeZones | 查询站点列表 | 20 | [80713](https://cloud.tencent.com/document/api/1552/80713) |
| ExportZoneConfig | 导出站点配置 | 20 | [113230](https://cloud.tencent.com/document/api/1552/113230) |
| ImportZoneConfig | 导入站点配置 | 20 | [113229](https://cloud.tencent.com/document/api/1552/113229) |
| DescribeZoneConfigImportResult | 查询站点配置导入结果 | 20 | [113231](https://cloud.tencent.com/document/api/1552/113231) |

### 4.2 加速域名管理

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateAccelerationDomain | 创建加速域名 | 20 | [86338](https://cloud.tencent.com/document/api/1552/86338) |
| DescribeAccelerationDomains | 查询加速域名列表 | 20 | [86336](https://cloud.tencent.com/document/api/1552/86336) |
| ModifyAccelerationDomain | 修改加速域名信息 | 20 | [86335](https://cloud.tencent.com/document/api/1552/86335) |
| ModifyAccelerationDomainStatuses | 批量修改加速域名状态 | 20 | [86334](https://cloud.tencent.com/document/api/1552/86334) |
| DeleteAccelerationDomains | 批量删除加速域名 | 20 | [86337](https://cloud.tencent.com/document/api/1552/86337) |
| CreateSharedCNAME | 创建共享 CNAME | 20 | [97762](https://cloud.tencent.com/document/api/1552/97762) |
| DescribeSharedCNAME | 查询共享 CNAME 列表 | 20 | [130048](https://cloud.tencent.com/document/api/1552/130048) |
| ModifySharedCNAME | 修改共享 CNAME | 20 | [130047](https://cloud.tencent.com/document/api/1552/130047) |
| BindSharedCNAME | 绑定共享 CNAME | 20 | [101363](https://cloud.tencent.com/document/api/1552/101363) |
| DeleteSharedCNAME | 删除共享 CNAME | 20 | [101362](https://cloud.tencent.com/document/api/1552/101362) |

### 4.3 站点加速配置（七层 / 规则引擎）

> 腾讯云控制台称 **规则引擎**（针对特定域名生效的差异化加速配置）。VanShine 后台入口：`/admin/cdn/edgeone/rules.php`。API Action 名称仍为 `L7Acc*`。
>
> **VanShine v1.10.0+** 规则引擎后台能力：
> - 全屏编辑器：**16 种匹配类型**（含客户端运营商）、6 种运算符、**官方文档全部 RuleEngineAction**（见 `includes/rules-catalog.php`）
> - **100% 表单化**：含 AccessURLRedirect、SiteFailover、OriginAuthentication；未知操作自动嵌套表单，无 JSON 编辑
> - 嵌套 SubRules 支持 **IF / ELSE IF / ELSE 兜底** 三分支
> - 保存/修改走完整 `RuleEngineItem`（`Branches` / `Actions` / `SubRules` / `Condition` 表达式）
> - 前端脚本：`assets/js/edgeone-rules-editor.js`；列表 CRUD：`assets/js/edgeone-admin.js`
> - 匹配类型与操作对照：[90438](https://cloud.tencent.com/document/product/1552/90438)

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateL7AccRules | 创建七层加速规则 | 20 | [115822](https://cloud.tencent.com/document/api/1552/115822) |
| DescribeL7AccRules | 查询七层加速规则 | 20 | [115820](https://cloud.tencent.com/document/api/1552/115820) |
| ModifyL7AccRule | 修改七层加速规则 | 20 | [115818](https://cloud.tencent.com/document/api/1552/115818) |
| DeleteL7AccRules | 删除七层加速规则 | 20 | [115821](https://cloud.tencent.com/document/api/1552/115821) |
| DescribeL7AccSetting | 查询七层加速全局配置 | 20 | [115819](https://cloud.tencent.com/document/api/1552/115819) |
| ModifyL7AccSetting | 修改七层加速全局配置 | 20 | [115817](https://cloud.tencent.com/document/api/1552/115817) |
| ModifyL7AccRulePriority | 修改七层加速规则优先级 | 20 | [117133](https://cloud.tencent.com/document/api/1552/117133) |

### 4.4 边缘函数

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateFunction | 创建边缘函数 | 5 | [111389](https://cloud.tencent.com/document/api/1552/111389) |
| DescribeFunctions | 查询边缘函数列表 | 20 | [111383](https://cloud.tencent.com/document/api/1552/111383) |
| ModifyFunction | 修改边缘函数 | 20 | [111381](https://cloud.tencent.com/document/api/1552/111381) |
| DeleteFunction | 删除边缘函数 | 20 | [111387](https://cloud.tencent.com/document/api/1552/111387) |
| CreateFunctionRule | 创建边缘函数触发规则 | 20 | [111388](https://cloud.tencent.com/document/api/1552/111388) |
| DescribeFunctionRules | 查询边缘函数触发规则 | 20 | [111385](https://cloud.tencent.com/document/api/1552/111385) |
| ModifyFunctionRule | 修改边缘函数触发规则 | 20 | [111380](https://cloud.tencent.com/document/api/1552/111380) |
| ModifyFunctionRulePriority | 修改边缘函数触发规则优先级 | 20 | [111379](https://cloud.tencent.com/document/api/1552/111379) |
| DeleteFunctionRules | 删除边缘函数触发规则 | 20 | [111386](https://cloud.tencent.com/document/api/1552/111386) |
| DescribeFunctionRuntimeEnvironment | 查询边缘函数运行环境 | 20 | [111384](https://cloud.tencent.com/document/api/1552/111384) |
| HandleFunctionRuntimeEnvironment | 操作边缘函数运行环境 | 20 | [111382](https://cloud.tencent.com/document/api/1552/111382) |
| CreateFunctionReplica | 创建边缘函数副本 | 20 | [132393](https://cloud.tencent.com/document/api/1552/132393) |
| DeleteFunctionReplica | 删除边缘函数副本 | 20 | [132392](https://cloud.tencent.com/document/api/1552/132392) |
| DescribeFunctionReplicas | 查询边缘函数副本列表 | 20 | [132391](https://cloud.tencent.com/document/api/1552/132391) |
| ModifyFunctionReplica | 编辑边缘函数副本 | 20 | [132390](https://cloud.tencent.com/document/api/1552/132390) |
| DescribeFunctionComponentBindings | 查询函数组件绑定列表 | 20 | [130180](https://cloud.tencent.com/document/api/1552/130180) |
| ModifyFunctionComponentBindings | 修改函数组件绑定 | 20 | [130179](https://cloud.tencent.com/document/api/1552/130179) |

### 4.5 别称域名

> SDK 已封装对应 API，但 VanShine 后台**暂未开放**别称域名管理页（腾讯功能尚在测试）。

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateAliasDomain | 创建别称域名 | 20 | [81247](https://cloud.tencent.com/document/api/1552/81247) |
| DescribeAliasDomains | 查询别称域名信息列表 | 20 | [81245](https://cloud.tencent.com/document/api/1552/81245) |
| ModifyAliasDomain | 修改别称域名 | 20 | [81244](https://cloud.tencent.com/document/api/1552/81244) |
| ModifyAliasDomainStatus | 修改别称域名状态 | 20 | [81243](https://cloud.tencent.com/document/api/1552/81243) |
| DeleteAliasDomain | 删除别称域名 | 20 | [81246](https://cloud.tencent.com/document/api/1552/81246) |

### 4.6 安全配置

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateSecurityIPGroup | 创建安全 IP 组 | 20 | [90651](https://cloud.tencent.com/document/api/1552/90651) |
| DescribeSecurityIPGroup | 查询安全 IP 组 | 20 | [105866](https://cloud.tencent.com/document/api/1552/105866) |
| ModifySecurityIPGroup | 修改安全 IP 组 | 20 | [90649](https://cloud.tencent.com/document/api/1552/90649) |
| DeleteSecurityIPGroup | 删除安全 IP 组 | 20 | [90650](https://cloud.tencent.com/document/api/1552/90650) |
| DescribeSecurityTemplateBindings | 查询指定策略模板的绑定关系列表 | 20 | [100811](https://cloud.tencent.com/document/api/1552/100811) |
| BindSecurityTemplateToEntity | 绑定或解绑安全策略模板 | 20 | [100814](https://cloud.tencent.com/document/api/1552/100814) |
| DescribeSecurityPolicy | 查询安全防护配置详情 | 20 | [80677](https://cloud.tencent.com/document/api/1552/80677) |
| ModifySecurityPolicy | 修改 Web & Bot 安全配置 | 20 | [80669](https://cloud.tencent.com/document/api/1552/80669) |
| DescribeSecurityIPGroupContent | 分页查询 IP 组中的 IP 列表 | 20 | [122107](https://cloud.tencent.com/document/api/1552/122107) |
| CreateSecurityJSInjectionRule | 创建 JavaScript 注入规则 | 20 | [122111](https://cloud.tencent.com/document/api/1552/122111) |
| DescribeSecurityJSInjectionRule | 查询 JavaScript 注入规则 | 20 | [122106](https://cloud.tencent.com/document/api/1552/122106) |
| ModifySecurityJSInjectionRule | 修改 JavaScript 注入规则 | 20 | [122104](https://cloud.tencent.com/document/api/1552/122104) |
| DeleteSecurityJSInjectionRule | 删除 JavaScript 注入规则 | 20 | [122109](https://cloud.tencent.com/document/api/1552/122109) |
| CreateSecurityClientAttester | 创建客户端认证选项 | 20 | [122112](https://cloud.tencent.com/document/api/1552/122112) |
| DescribeSecurityClientAttester | 查询客户端认证选项 | 20 | [122108](https://cloud.tencent.com/document/api/1552/122108) |
| ModifySecurityClientAttester | 修改客户端认证选项 | 20 | [122105](https://cloud.tencent.com/document/api/1552/122105) |
| DeleteSecurityClientAttester | 删除客户端认证选项 | 20 | [122110](https://cloud.tencent.com/document/api/1552/122110) |
| CreateWebSecurityTemplate | 创建安全策略配置模板 | 20 | [121063](https://cloud.tencent.com/document/api/1552/121063) |
| DeleteWebSecurityTemplate | 删除安全策略配置模板 | 20 | [121064](https://cloud.tencent.com/document/api/1552/121064) |
| DescribeWebSecurityTemplates | 查询安全策略配置模板列表 | 20 | [121061](https://cloud.tencent.com/document/api/1552/121061) |
| ModifyWebSecurityTemplate | 修改安全策略配置模板 | 20 | [121060](https://cloud.tencent.com/document/api/1552/121060) |
| DescribeDDoSProtection | 查询站点的独立 DDoS 防护信息 | 20 | [121631](https://cloud.tencent.com/document/api/1552/121631) |
| DescribeWebSecurityTemplate | 查询安全策略配置模板详情 | 20 | [121062](https://cloud.tencent.com/document/api/1552/121062) |
| ModifyDDoSProtection | 修改站点的独立 DDoS 防护 | 20 | [121630](https://cloud.tencent.com/document/api/1552/121630) |

### 4.7 四层应用代理

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateL4Proxy | 创建四层代理实例 | 20 | [103417](https://cloud.tencent.com/document/api/1552/103417) |
| ModifyL4Proxy | 修改四层代理实例 | 20 | [103411](https://cloud.tencent.com/document/api/1552/103411) |
| ModifyL4ProxyStatus | 修改四层代理实例状态 | 20 | [103408](https://cloud.tencent.com/document/api/1552/103408) |
| DescribeL4Proxy | 查询四层代理实例列表 | 20 | [103413](https://cloud.tencent.com/document/api/1552/103413) |
| DeleteL4Proxy | 删除四层代理实例 | 20 | [103415](https://cloud.tencent.com/document/api/1552/103415) |
| CreateL4ProxyRules | 创建四层代理转发规则 | 20 | [103416](https://cloud.tencent.com/document/api/1552/103416) |
| ModifyL4ProxyRules | 修改四层代理转发规则 | 20 | [103410](https://cloud.tencent.com/document/api/1552/103410) |
| ModifyL4ProxyRulesStatus | 修改四层代理转发规则状态 | 20 | [103409](https://cloud.tencent.com/document/api/1552/103409) |
| DescribeL4ProxyRules | 查询四层代理转发规则列表 | 20 | [103412](https://cloud.tencent.com/document/api/1552/103412) |
| DeleteL4ProxyRules | 删除四层代理转发规则 | 20 | [103414](https://cloud.tencent.com/document/api/1552/103414) |

### 4.8 缓存管理（Purge / Prefetch，控制台称「刷新预热」）

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreatePurgeTask | 创建清除缓存任务 | 20 | [80703](https://cloud.tencent.com/document/api/1552/80703) |
| DescribePurgeTasks | 查询清除缓存历史记录 | 20 | [80699](https://cloud.tencent.com/document/api/1552/80699) |
| CreatePrefetchTask | 创建预热任务 | 20 | [80704](https://cloud.tencent.com/document/api/1552/80704) |
| DescribePrefetchTasks | 查询预热任务状态 | 20 | [80700](https://cloud.tencent.com/document/api/1552/80700) |
| DescribeContentQuota | 查询内容管理接口配额 | 20 | [80701](https://cloud.tencent.com/document/api/1552/80701) |
| DescribePrefetchOriginLimit | 查询预热回源限速限制 | 20 | [126842](https://cloud.tencent.com/document/api/1552/126842) |
| ModifyPrefetchOriginLimit | 配置预热回源限速限制 | 20 | [126841](https://cloud.tencent.com/document/api/1552/126841) |

### 4.9 数据分析

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| DescribeDDoSAttackData | 查询 DDoS 攻击时序数据 | 100 | [80660](https://cloud.tencent.com/document/api/1552/80660) |
| DescribeDDoSAttackEvent | 查询 DDoS 攻击事件列表 | 100 | [80659](https://cloud.tencent.com/document/api/1552/80659) |
| DescribeDDoSAttackTopData | 查询 DDoS 攻击 Top 数据 | 100 | [80656](https://cloud.tencent.com/document/api/1552/80656) |
| DescribeTimingL7OriginPullData | 查询回源时序数据 | 20 | [123320](https://cloud.tencent.com/document/api/1552/123320) |
| DescribeTimingL4Data | 查询四层流量时序数据 | 100 | [80649](https://cloud.tencent.com/document/api/1552/80649) |
| DescribeTimingL7AnalysisData | 查询流量分析时序数据 | 30 | [80648](https://cloud.tencent.com/document/api/1552/80648) |
| DescribeTopL7AnalysisData | 查询流量分析 Top 数据 | 30 | [80646](https://cloud.tencent.com/document/api/1552/80646) |

### 4.10 日志服务

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| DownloadL7Logs | 下载七层离线日志 | 100 | [80635](https://cloud.tencent.com/document/api/1552/80635) |
| DownloadL4Logs | 下载四层离线日志 | 100 | [80636](https://cloud.tencent.com/document/api/1552/80636) |
| CreateCLSIndex | 创建 CLS 索引 | 20 | [104113](https://cloud.tencent.com/document/api/1552/104113) |
| CreateRealtimeLogDeliveryTask | 创建实时日志投递任务 | 20 | [104112](https://cloud.tencent.com/document/api/1552/104112) |
| ModifyRealtimeLogDeliveryTask | 修改实时日志投递任务 | 20 | [104109](https://cloud.tencent.com/document/api/1552/104109) |
| DeleteRealtimeLogDeliveryTask | 删除实时日志投递任务 | 20 | [104111](https://cloud.tencent.com/document/api/1552/104111) |
| DescribeRealtimeLogDeliveryTasks | 查询实时日志投递任务列表 | 20 | [104110](https://cloud.tencent.com/document/api/1552/104110) |

### 4.11 计费

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreatePlan | 创建套餐 | 20 | [105771](https://cloud.tencent.com/document/api/1552/105771) |
| DescribePlans | 查询套餐信息列表 | 20 | [118485](https://cloud.tencent.com/document/api/1552/118485) |
| UpgradePlan | 升级套餐 | 20 | [105766](https://cloud.tencent.com/document/api/1552/105766) |
| RenewPlan | 续费套餐 | 20 | [105767](https://cloud.tencent.com/document/api/1552/105767) |
| ModifyPlan | 修改套餐配置 | 20 | [105768](https://cloud.tencent.com/document/api/1552/105768) |
| IncreasePlanQuota | 增购套餐配额 | 20 | [105769](https://cloud.tencent.com/document/api/1552/105769) |
| DestroyPlan | 销毁套餐 | 20 | [105770](https://cloud.tencent.com/document/api/1552/105770) |
| CreatePlanForZone | 为未购买套餐的站点购买套餐 | 20 | [80607](https://cloud.tencent.com/document/api/1552/80607) |
| BindZoneToPlan | 为站点绑定套餐 | 20 | [83042](https://cloud.tencent.com/document/api/1552/83042) |
| DescribeBillingData | 查询计费数据 | 50 | [103562](https://cloud.tencent.com/document/api/1552/103562) |
| DescribeAvailablePlans | 查询当前账户可购买套餐信息列表 | 20 | [80606](https://cloud.tencent.com/document/api/1552/80606) |

### 4.12 证书

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| DescribeDefaultCertificates | 查询默认证书列表 | 20 | [80603](https://cloud.tencent.com/document/api/1552/80603) |
| ModifyHostsCertificate | 配置域名证书 | 20 | [80764](https://cloud.tencent.com/document/api/1552/80764) |
| ApplyFreeCertificate | 申请免费证书 | 20 | [124807](https://cloud.tencent.com/document/api/1552/124807) |
| CheckFreeCertificateVerification | 检查免费证书申请结果 | 20 | [124806](https://cloud.tencent.com/document/api/1552/124806) |

### 4.13 源站防护

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| EnableOriginACL | 开启源站防护 | 20 | [120406](https://cloud.tencent.com/document/api/1552/120406) |
| ModifyOriginACL | 变更源站防护实例 | 20 | [120405](https://cloud.tencent.com/document/api/1552/120405) |
| DescribeOriginACL | 查询源站防护详情 | 20 | [120408](https://cloud.tencent.com/document/api/1552/120408) |
| ConfirmOriginACLUpdate | 确认回源 IP 网段更新 | 20 | [120409](https://cloud.tencent.com/document/api/1552/120409) |
| DisableOriginACL | 关闭源站防护 | 20 | [120407](https://cloud.tencent.com/document/api/1552/120407) |

### 4.14 负载均衡

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateOriginGroup | 创建源站组 | 20 | [80598](https://cloud.tencent.com/document/api/1552/80598) |
| ModifyOriginGroup | 修改源站组 | 20 | [80592](https://cloud.tencent.com/document/api/1552/80592) |
| DeleteOriginGroup | 删除源站组 | 20 | [80596](https://cloud.tencent.com/document/api/1552/80596) |
| DescribeOriginGroup | 获取源站组列表 | 20 | [80594](https://cloud.tencent.com/document/api/1552/80594) |
| CreateLoadBalancer | 创建负载均衡实例 | 20 | [111970](https://cloud.tencent.com/document/api/1552/111970) |
| ModifyLoadBalancer | 修改负载均衡实例 | 20 | [111969](https://cloud.tencent.com/document/api/1552/111969) |
| DeleteLoadBalancer | 删除负载均衡实例 | 20 | [111973](https://cloud.tencent.com/document/api/1552/111973) |
| DescribeLoadBalancerList | 查询负载均衡实例列表 | 20 | [111972](https://cloud.tencent.com/document/api/1552/111972) |
| DescribeOriginGroupHealthStatus | 查询负载均衡实例下源站组健康状态 | 20 | [111971](https://cloud.tencent.com/document/api/1552/111971) |

### 4.15 诊断工具

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| DescribeIPRegion | 查询 IP 归属信息 | 20 | [102227](https://cloud.tencent.com/document/api/1552/102227) |

### 4.16 自定义响应页面

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateCustomizeErrorPage | 创建自定义响应页面 | 20 | [107118](https://cloud.tencent.com/document/api/1552/107118) |
| DescribeCustomErrorPages | 查询自定义响应页面列表 | 20 | [107116](https://cloud.tencent.com/document/api/1552/107116) |
| ModifyCustomErrorPage | 修改自定义响应页面 | 20 | [107115](https://cloud.tencent.com/document/api/1552/107115) |
| DeleteCustomErrorPage | 删除自定义响应页面 | 20 | [107117](https://cloud.tencent.com/document/api/1552/107117) |

### 4.17 版本管理

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateConfigGroupVersion | 创建配置组版本 | 20 | [101867](https://cloud.tencent.com/document/api/1552/101867) |
| DeployConfigGroupVersion | 发布配置组版本 | 20 | [101866](https://cloud.tencent.com/document/api/1552/101866) |
| DescribeConfigGroupVersionDetail | 查询配置组版本详情 | 20 | [101865](https://cloud.tencent.com/document/api/1552/101865) |
| DescribeConfigGroupVersions | 查询配置组版本列表 | 20 | [101864](https://cloud.tencent.com/document/api/1552/101864) |
| DescribeDeployHistory | 查询版本发布历史 | 20 | [101863](https://cloud.tencent.com/document/api/1552/101863) |
| DescribeEnvironments | 查询环境信息 | 20 | [101862](https://cloud.tencent.com/document/api/1552/101862) |
| ModifyZoneWorkMode | 修改站点工作模式 | 20 | [127806](https://cloud.tencent.com/document/api/1552/127806) |

### 4.18 API 防护

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateSecurityAPIResource | 创建 API 资源 | 20 | [122125](https://cloud.tencent.com/document/api/1552/122125) |
| DescribeSecurityAPIResource | 查询 API 资源 | 20 | [122121](https://cloud.tencent.com/document/api/1552/122121) |
| ModifySecurityAPIResource | 修改 API 资源 | 20 | [122119](https://cloud.tencent.com/document/api/1552/122119) |
| DeleteSecurityAPIResource | 删除 API 资源 | 20 | [122123](https://cloud.tencent.com/document/api/1552/122123) |
| CreateSecurityAPIService | 创建 API 服务 | 20 | [122124](https://cloud.tencent.com/document/api/1552/122124) |
| DescribeSecurityAPIService | 查询 API 服务 | 20 | [122120](https://cloud.tencent.com/document/api/1552/122120) |
| ModifySecurityAPIService | 修改 API 服务 | 20 | [122118](https://cloud.tencent.com/document/api/1552/122118) |
| DeleteSecurityAPIService | 删除 API 服务 | 20 | [122122](https://cloud.tencent.com/document/api/1552/122122) |

### 4.19 DNS 记录

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateDnsRecord | 创建 DNS 记录 | 20 | [80720](https://cloud.tencent.com/document/api/1552/80720) |
| DescribeDnsRecords | 查询 DNS 记录列表 | 20 | [80716](https://cloud.tencent.com/document/api/1552/80716) |
| ModifyDnsRecords | 批量修改 DNS 记录 | 20 | [114252](https://cloud.tencent.com/document/api/1552/114252) |
| ModifyDnsRecordsStatus | 批量修改 DNS 记录状态 | 20 | [114251](https://cloud.tencent.com/document/api/1552/114251) |
| DeleteDnsRecords | 批量删除 DNS 记录 | 20 | [80718](https://cloud.tencent.com/document/api/1552/80718) |

### 4.20 内容标识符

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateContentIdentifier | 创建内容标识符 | 20 | [114249](https://cloud.tencent.com/document/api/1552/114249) |
| DescribeContentIdentifiers | 批量查询内容标识符 | 20 | [114247](https://cloud.tencent.com/document/api/1552/114247) |
| ModifyContentIdentifier | 修改内容标识符 | 20 | [114246](https://cloud.tencent.com/document/api/1552/114246) |
| DeleteContentIdentifier | 删除内容标识符 | 20 | [114248](https://cloud.tencent.com/document/api/1552/114248) |

### 4.21 归属权

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| VerifyOwnership | 验证归属权 | 20 | [98879](https://cloud.tencent.com/document/api/1552/98879) |

### 4.22 图片与视频处理

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateJustInTimeTranscodeTemplate | 创建即时转码模板 | 20 | [122116](https://cloud.tencent.com/document/api/1552/122116) |
| DescribeJustInTimeTranscodeTemplates | 获取即时转码模板列表 | 20 | [122114](https://cloud.tencent.com/document/api/1552/122114) |
| DeleteJustInTimeTranscodeTemplates | 删除即时转码模板 | 20 | [122115](https://cloud.tencent.com/document/api/1552/122115) |

### 4.23 多通道安全加速网关

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| ConfirmMultiPathGatewayOriginACL | 确认多通道安全加速网关回源 IP 网段更新 | 20 | [123860](https://cloud.tencent.com/document/api/1552/123860) |
| DescribeMultiPathGatewayOriginACL | 查询多通道安全加速网关源站防护详情 | 20 | [123859](https://cloud.tencent.com/document/api/1552/123859) |
| CreateMultiPathGateway | 创建多通道安全加速网关 | 20 | [121343](https://cloud.tencent.com/document/api/1552/121343) |
| DescribeMultiPathGateways | 查询多通道安全加速网关列表 | 20 | [121334](https://cloud.tencent.com/document/api/1552/121334) |
| DescribeMultiPathGateway | 查询多通道安全加速网关详情 | 20 | [121338](https://cloud.tencent.com/document/api/1552/121338) |
| ModifyMultiPathGateway | 修改多通道安全加速网关信息 | 20 | [121333](https://cloud.tencent.com/document/api/1552/121333) |
| ModifyMultiPathGatewayStatus | 修改多通道安全加速网关状态 | 20 | [123858](https://cloud.tencent.com/document/api/1552/123858) |
| DeleteMultiPathGateway | 删除多通道安全加速网关 | 20 | [121340](https://cloud.tencent.com/document/api/1552/121340) |
| DescribeMultiPathGatewayRegions | 查询多通道安全加速网关可用地域列表 | 20 | [121336](https://cloud.tencent.com/document/api/1552/121336) |
| CreateMultiPathGatewaySecretKey | 创建多通道安全加速网关密钥 | 20 | [121341](https://cloud.tencent.com/document/api/1552/121341) |
| DescribeMultiPathGatewaySecretKey | 查询多通道安全加速网关接入密钥 | 20 | [121335](https://cloud.tencent.com/document/api/1552/121335) |
| ModifyMultiPathGatewaySecretKey | 修改多通道安全加速网关接入密钥 | 20 | [121331](https://cloud.tencent.com/document/api/1552/121331) |
| RefreshMultiPathGatewaySecretKey | 刷新多通道安全加速网关密钥 | 20 | [121330](https://cloud.tencent.com/document/api/1552/121330) |
| CreateMultiPathGatewayLine | 创建多通道安全加速网关线路 | 20 | [121342](https://cloud.tencent.com/document/api/1552/121342) |
| DescribeMultiPathGatewayLine | 查询多通道安全加速网关线路详情 | 20 | [121337](https://cloud.tencent.com/document/api/1552/121337) |
| ModifyMultiPathGatewayLine | 修改多通道安全加速网关线路信息 | 20 | [121332](https://cloud.tencent.com/document/api/1552/121332) |
| DeleteMultiPathGatewayLine | 删除多通道安全加速网关线路 | 20 | [121339](https://cloud.tencent.com/document/api/1552/121339) |

### 4.24 KV 存储（EdgeKV）

| Action | 功能描述 | 频率(次/s) | 官方文档 |
|--------|----------|-----------|----------|
| CreateEdgeKVNamespace | 创建 KV 命名空间 | 20 | [130189](https://cloud.tencent.com/document/api/1552/130189) |
| DeleteEdgeKVNamespace | 删除 KV 命名空间 | 20 | [130188](https://cloud.tencent.com/document/api/1552/130188) |
| DescribeEdgeKVNamespaces | 查询 KV 命名空间 | 20 | [130187](https://cloud.tencent.com/document/api/1552/130187) |
| EdgeKVDelete | 删除 KV 数据 | 20 | [130186](https://cloud.tencent.com/document/api/1552/130186) |
| EdgeKVGet | 查询 KV 数据 | 20 | [130185](https://cloud.tencent.com/document/api/1552/130185) |
| EdgeKVList | 查询 KV 键名列表 | 20 | [130184](https://cloud.tencent.com/document/api/1552/130184) |
| EdgeKVPut | 写入 KV 数据 | 20 | [130183](https://cloud.tencent.com/document/api/1552/130183) |
| ModifyEdgeKVNamespace | 修改 KV 命名空间 | 20 | [130182](https://cloud.tencent.com/document/api/1552/130182) |

---

## 5. 官方文档链接汇总

| 主题 | 链接 |
|------|------|
| API 概览 | https://cloud.tencent.com/document/api/1552/80731 |
| 请求结构 | https://cloud.tencent.com/document/api/1552/80723 |
| 公共参数 | https://cloud.tencent.com/document/api/1552/80724 |
| 签名方法 v3 | https://cloud.tencent.com/document/api/1552/80725 |
| 返回结果 | https://cloud.tencent.com/document/api/1552/80727 |
| 参数类型 | https://cloud.tencent.com/document/api/1552/80728 |
| API Explorer | https://console.cloud.tencent.com/api/explorer?Product=teo |

---

## 6. 修订记录

| 日期 | 说明 |
|------|------|
| 2026-06-27 | v1.0.74：修正 ZoneIds/Filters/RecordNames 等 2022-09-01 必填参数 |
| 2026-06-27 | v1.0.71：补充空 JSON 对象与 DescribeBillingData 必填参数说明 |
| 2026-06-27 | 初版：整理 EO 调用方式与全量 API 索引，供 VanShine 对接参考 |
