<?php
/**
 * 文件：core/cdn/edgeone/EdgeOneActions.php
 * 作用：EdgeOne Action 注册表（频率、文档 ID）
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneActions
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function registry()
    {
        return array (
  '4.1' => 
  array (
    0 => 
    array (
      'action' => 'CreateZone',
      'description' => '创建站点',
      'rate_limit' => 20,
      'doc_id' => '80719',
    ),
    1 => 
    array (
      'action' => 'DescribeIdentifications',
      'description' => '查询站点的验证信息',
      'rate_limit' => 20,
      'doc_id' => '80714',
    ),
    2 => 
    array (
      'action' => 'ModifyZone',
      'description' => '修改站点',
      'rate_limit' => 20,
      'doc_id' => '80709',
    ),
    3 => 
    array (
      'action' => 'DeleteZone',
      'description' => '删除站点',
      'rate_limit' => 20,
      'doc_id' => '80717',
    ),
    4 => 
    array (
      'action' => 'ModifyZoneStatus',
      'description' => '切换站点状态',
      'rate_limit' => 20,
      'doc_id' => '80707',
    ),
    5 => 
    array (
      'action' => 'CheckCnameStatus',
      'description' => '校验域名 CNAME 配置状态',
      'rate_limit' => 20,
      'doc_id' => '94491',
    ),
    6 => 
    array (
      'action' => 'IdentifyZone',
      'description' => '认证站点',
      'rate_limit' => 20,
      'doc_id' => '80712',
    ),
    7 => 
    array (
      'action' => 'DescribeZones',
      'description' => '查询站点列表',
      'rate_limit' => 20,
      'doc_id' => '80713',
    ),
    8 => 
    array (
      'action' => 'ExportZoneConfig',
      'description' => '导出站点配置',
      'rate_limit' => 20,
      'doc_id' => '113230',
    ),
    9 => 
    array (
      'action' => 'ImportZoneConfig',
      'description' => '导入站点配置',
      'rate_limit' => 20,
      'doc_id' => '113229',
    ),
    10 => 
    array (
      'action' => 'DescribeZoneConfigImportResult',
      'description' => '查询站点配置导入结果',
      'rate_limit' => 20,
      'doc_id' => '113231',
    ),
  ),
  '4.2' => 
  array (
    0 => 
    array (
      'action' => 'CreateAccelerationDomain',
      'description' => '创建加速域名',
      'rate_limit' => 20,
      'doc_id' => '86338',
    ),
    1 => 
    array (
      'action' => 'DescribeAccelerationDomains',
      'description' => '查询加速域名列表',
      'rate_limit' => 20,
      'doc_id' => '86336',
    ),
    2 => 
    array (
      'action' => 'ModifyAccelerationDomain',
      'description' => '修改加速域名信息',
      'rate_limit' => 20,
      'doc_id' => '86335',
    ),
    3 => 
    array (
      'action' => 'ModifyAccelerationDomainStatuses',
      'description' => '批量修改加速域名状态',
      'rate_limit' => 20,
      'doc_id' => '86334',
    ),
    4 => 
    array (
      'action' => 'DeleteAccelerationDomains',
      'description' => '批量删除加速域名',
      'rate_limit' => 20,
      'doc_id' => '86337',
    ),
    5 => 
    array (
      'action' => 'CreateSharedCNAME',
      'description' => '创建共享 CNAME',
      'rate_limit' => 20,
      'doc_id' => '97762',
    ),
    6 => 
    array (
      'action' => 'DescribeSharedCNAME',
      'description' => '查询共享 CNAME 列表',
      'rate_limit' => 20,
      'doc_id' => '130048',
    ),
    7 => 
    array (
      'action' => 'ModifySharedCNAME',
      'description' => '修改共享 CNAME',
      'rate_limit' => 20,
      'doc_id' => '130047',
    ),
    8 => 
    array (
      'action' => 'BindSharedCNAME',
      'description' => '绑定共享 CNAME',
      'rate_limit' => 20,
      'doc_id' => '101363',
    ),
    9 => 
    array (
      'action' => 'DeleteSharedCNAME',
      'description' => '删除共享 CNAME',
      'rate_limit' => 20,
      'doc_id' => '101362',
    ),
  ),
  '4.3' => 
  array (
    0 => 
    array (
      'action' => 'CreateL7AccRules',
      'description' => '创建七层加速规则',
      'rate_limit' => 20,
      'doc_id' => '115822',
    ),
    1 => 
    array (
      'action' => 'DescribeL7AccRules',
      'description' => '查询七层加速规则',
      'rate_limit' => 20,
      'doc_id' => '115820',
    ),
    2 => 
    array (
      'action' => 'ModifyL7AccRule',
      'description' => '修改七层加速规则',
      'rate_limit' => 20,
      'doc_id' => '115818',
    ),
    3 => 
    array (
      'action' => 'DeleteL7AccRules',
      'description' => '删除七层加速规则',
      'rate_limit' => 20,
      'doc_id' => '115821',
    ),
    4 => 
    array (
      'action' => 'DescribeL7AccSetting',
      'description' => '查询七层加速全局配置',
      'rate_limit' => 20,
      'doc_id' => '115819',
    ),
    5 => 
    array (
      'action' => 'ModifyL7AccSetting',
      'description' => '修改七层加速全局配置',
      'rate_limit' => 20,
      'doc_id' => '115817',
    ),
    6 => 
    array (
      'action' => 'ModifyL7AccRulePriority',
      'description' => '修改七层加速规则优先级',
      'rate_limit' => 20,
      'doc_id' => '117133',
    ),
  ),
  '4.4' => 
  array (
    0 => 
    array (
      'action' => 'CreateFunction',
      'description' => '创建边缘函数',
      'rate_limit' => 5,
      'doc_id' => '111389',
    ),
    1 => 
    array (
      'action' => 'DescribeFunctions',
      'description' => '查询边缘函数列表',
      'rate_limit' => 20,
      'doc_id' => '111383',
    ),
    2 => 
    array (
      'action' => 'ModifyFunction',
      'description' => '修改边缘函数',
      'rate_limit' => 20,
      'doc_id' => '111381',
    ),
    3 => 
    array (
      'action' => 'DeleteFunction',
      'description' => '删除边缘函数',
      'rate_limit' => 20,
      'doc_id' => '111387',
    ),
    4 => 
    array (
      'action' => 'CreateFunctionRule',
      'description' => '创建边缘函数触发规则',
      'rate_limit' => 20,
      'doc_id' => '111388',
    ),
    5 => 
    array (
      'action' => 'DescribeFunctionRules',
      'description' => '查询边缘函数触发规则',
      'rate_limit' => 20,
      'doc_id' => '111385',
    ),
    6 => 
    array (
      'action' => 'ModifyFunctionRule',
      'description' => '修改边缘函数触发规则',
      'rate_limit' => 20,
      'doc_id' => '111380',
    ),
    7 => 
    array (
      'action' => 'ModifyFunctionRulePriority',
      'description' => '修改边缘函数触发规则优先级',
      'rate_limit' => 20,
      'doc_id' => '111379',
    ),
    8 => 
    array (
      'action' => 'DeleteFunctionRules',
      'description' => '删除边缘函数触发规则',
      'rate_limit' => 20,
      'doc_id' => '111386',
    ),
    9 => 
    array (
      'action' => 'DescribeFunctionRuntimeEnvironment',
      'description' => '查询边缘函数运行环境',
      'rate_limit' => 20,
      'doc_id' => '111384',
    ),
    10 => 
    array (
      'action' => 'HandleFunctionRuntimeEnvironment',
      'description' => '操作边缘函数运行环境',
      'rate_limit' => 20,
      'doc_id' => '111382',
    ),
    11 => 
    array (
      'action' => 'CreateFunctionReplica',
      'description' => '创建边缘函数副本',
      'rate_limit' => 20,
      'doc_id' => '132393',
    ),
    12 => 
    array (
      'action' => 'DeleteFunctionReplica',
      'description' => '删除边缘函数副本',
      'rate_limit' => 20,
      'doc_id' => '132392',
    ),
    13 => 
    array (
      'action' => 'DescribeFunctionReplicas',
      'description' => '查询边缘函数副本列表',
      'rate_limit' => 20,
      'doc_id' => '132391',
    ),
    14 => 
    array (
      'action' => 'ModifyFunctionReplica',
      'description' => '编辑边缘函数副本',
      'rate_limit' => 20,
      'doc_id' => '132390',
    ),
    15 => 
    array (
      'action' => 'DescribeFunctionComponentBindings',
      'description' => '查询函数组件绑定列表',
      'rate_limit' => 20,
      'doc_id' => '130180',
    ),
    16 => 
    array (
      'action' => 'ModifyFunctionComponentBindings',
      'description' => '修改函数组件绑定',
      'rate_limit' => 20,
      'doc_id' => '130179',
    ),
  ),
  '4.5' => 
  array (
    0 => 
    array (
      'action' => 'CreateAliasDomain',
      'description' => '创建别称域名',
      'rate_limit' => 20,
      'doc_id' => '81247',
    ),
    1 => 
    array (
      'action' => 'DescribeAliasDomains',
      'description' => '查询别称域名信息列表',
      'rate_limit' => 20,
      'doc_id' => '81245',
    ),
    2 => 
    array (
      'action' => 'ModifyAliasDomain',
      'description' => '修改别称域名',
      'rate_limit' => 20,
      'doc_id' => '81244',
    ),
    3 => 
    array (
      'action' => 'ModifyAliasDomainStatus',
      'description' => '修改别称域名状态',
      'rate_limit' => 20,
      'doc_id' => '81243',
    ),
    4 => 
    array (
      'action' => 'DeleteAliasDomain',
      'description' => '删除别称域名',
      'rate_limit' => 20,
      'doc_id' => '81246',
    ),
  ),
  '4.6' => 
  array (
    0 => 
    array (
      'action' => 'CreateSecurityIPGroup',
      'description' => '创建安全 IP 组',
      'rate_limit' => 20,
      'doc_id' => '90651',
    ),
    1 => 
    array (
      'action' => 'DescribeSecurityIPGroup',
      'description' => '查询安全 IP 组',
      'rate_limit' => 20,
      'doc_id' => '105866',
    ),
    2 => 
    array (
      'action' => 'ModifySecurityIPGroup',
      'description' => '修改安全 IP 组',
      'rate_limit' => 20,
      'doc_id' => '90649',
    ),
    3 => 
    array (
      'action' => 'DeleteSecurityIPGroup',
      'description' => '删除安全 IP 组',
      'rate_limit' => 20,
      'doc_id' => '90650',
    ),
    4 => 
    array (
      'action' => 'DescribeSecurityTemplateBindings',
      'description' => '查询指定策略模板的绑定关系列表',
      'rate_limit' => 20,
      'doc_id' => '100811',
    ),
    5 => 
    array (
      'action' => 'BindSecurityTemplateToEntity',
      'description' => '绑定或解绑安全策略模板',
      'rate_limit' => 20,
      'doc_id' => '100814',
    ),
    6 => 
    array (
      'action' => 'DescribeSecurityPolicy',
      'description' => '查询安全防护配置详情',
      'rate_limit' => 20,
      'doc_id' => '80677',
    ),
    7 => 
    array (
      'action' => 'ModifySecurityPolicy',
      'description' => '修改 Web & Bot 安全配置',
      'rate_limit' => 20,
      'doc_id' => '80669',
    ),
    8 => 
    array (
      'action' => 'DescribeSecurityIPGroupContent',
      'description' => '分页查询 IP 组中的 IP 列表',
      'rate_limit' => 20,
      'doc_id' => '122107',
    ),
    9 => 
    array (
      'action' => 'CreateSecurityJSInjectionRule',
      'description' => '创建 JavaScript 注入规则',
      'rate_limit' => 20,
      'doc_id' => '122111',
    ),
    10 => 
    array (
      'action' => 'DescribeSecurityJSInjectionRule',
      'description' => '查询 JavaScript 注入规则',
      'rate_limit' => 20,
      'doc_id' => '122106',
    ),
    11 => 
    array (
      'action' => 'ModifySecurityJSInjectionRule',
      'description' => '修改 JavaScript 注入规则',
      'rate_limit' => 20,
      'doc_id' => '122104',
    ),
    12 => 
    array (
      'action' => 'DeleteSecurityJSInjectionRule',
      'description' => '删除 JavaScript 注入规则',
      'rate_limit' => 20,
      'doc_id' => '122109',
    ),
    13 => 
    array (
      'action' => 'CreateSecurityClientAttester',
      'description' => '创建客户端认证选项',
      'rate_limit' => 20,
      'doc_id' => '122112',
    ),
    14 => 
    array (
      'action' => 'DescribeSecurityClientAttester',
      'description' => '查询客户端认证选项',
      'rate_limit' => 20,
      'doc_id' => '122108',
    ),
    15 => 
    array (
      'action' => 'ModifySecurityClientAttester',
      'description' => '修改客户端认证选项',
      'rate_limit' => 20,
      'doc_id' => '122105',
    ),
    16 => 
    array (
      'action' => 'DeleteSecurityClientAttester',
      'description' => '删除客户端认证选项',
      'rate_limit' => 20,
      'doc_id' => '122110',
    ),
    17 => 
    array (
      'action' => 'CreateWebSecurityTemplate',
      'description' => '创建安全策略配置模板',
      'rate_limit' => 20,
      'doc_id' => '121063',
    ),
    18 => 
    array (
      'action' => 'DeleteWebSecurityTemplate',
      'description' => '删除安全策略配置模板',
      'rate_limit' => 20,
      'doc_id' => '121064',
    ),
    19 => 
    array (
      'action' => 'DescribeWebSecurityTemplates',
      'description' => '查询安全策略配置模板列表',
      'rate_limit' => 20,
      'doc_id' => '121061',
    ),
    20 => 
    array (
      'action' => 'ModifyWebSecurityTemplate',
      'description' => '修改安全策略配置模板',
      'rate_limit' => 20,
      'doc_id' => '121060',
    ),
    21 => 
    array (
      'action' => 'DescribeDDoSProtection',
      'description' => '查询站点的独立 DDoS 防护信息',
      'rate_limit' => 20,
      'doc_id' => '121631',
    ),
    22 => 
    array (
      'action' => 'DescribeWebSecurityTemplate',
      'description' => '查询安全策略配置模板详情',
      'rate_limit' => 20,
      'doc_id' => '121062',
    ),
    23 => 
    array (
      'action' => 'ModifyDDoSProtection',
      'description' => '修改站点的独立 DDoS 防护',
      'rate_limit' => 20,
      'doc_id' => '121630',
    ),
  ),
  '4.7' => 
  array (
    0 => 
    array (
      'action' => 'CreateL4Proxy',
      'description' => '创建四层代理实例',
      'rate_limit' => 20,
      'doc_id' => '103417',
    ),
    1 => 
    array (
      'action' => 'ModifyL4Proxy',
      'description' => '修改四层代理实例',
      'rate_limit' => 20,
      'doc_id' => '103411',
    ),
    2 => 
    array (
      'action' => 'ModifyL4ProxyStatus',
      'description' => '修改四层代理实例状态',
      'rate_limit' => 20,
      'doc_id' => '103408',
    ),
    3 => 
    array (
      'action' => 'DescribeL4Proxy',
      'description' => '查询四层代理实例列表',
      'rate_limit' => 20,
      'doc_id' => '103413',
    ),
    4 => 
    array (
      'action' => 'DeleteL4Proxy',
      'description' => '删除四层代理实例',
      'rate_limit' => 20,
      'doc_id' => '103415',
    ),
    5 => 
    array (
      'action' => 'CreateL4ProxyRules',
      'description' => '创建四层代理转发规则',
      'rate_limit' => 20,
      'doc_id' => '103416',
    ),
    6 => 
    array (
      'action' => 'ModifyL4ProxyRules',
      'description' => '修改四层代理转发规则',
      'rate_limit' => 20,
      'doc_id' => '103410',
    ),
    7 => 
    array (
      'action' => 'ModifyL4ProxyRulesStatus',
      'description' => '修改四层代理转发规则状态',
      'rate_limit' => 20,
      'doc_id' => '103409',
    ),
    8 => 
    array (
      'action' => 'DescribeL4ProxyRules',
      'description' => '查询四层代理转发规则列表',
      'rate_limit' => 20,
      'doc_id' => '103412',
    ),
    9 => 
    array (
      'action' => 'DeleteL4ProxyRules',
      'description' => '删除四层代理转发规则',
      'rate_limit' => 20,
      'doc_id' => '103414',
    ),
  ),
  '4.8' => 
  array (
    0 => 
    array (
      'action' => 'CreatePurgeTask',
      'description' => '创建清除缓存任务',
      'rate_limit' => 20,
      'doc_id' => '80703',
    ),
    1 => 
    array (
      'action' => 'DescribePurgeTasks',
      'description' => '查询清除缓存历史记录',
      'rate_limit' => 20,
      'doc_id' => '80699',
    ),
    2 => 
    array (
      'action' => 'CreatePrefetchTask',
      'description' => '创建预热任务',
      'rate_limit' => 20,
      'doc_id' => '80704',
    ),
    3 => 
    array (
      'action' => 'DescribePrefetchTasks',
      'description' => '查询预热任务状态',
      'rate_limit' => 20,
      'doc_id' => '80700',
    ),
    4 => 
    array (
      'action' => 'DescribeContentQuota',
      'description' => '查询内容管理接口配额',
      'rate_limit' => 20,
      'doc_id' => '80701',
    ),
    5 => 
    array (
      'action' => 'DescribePrefetchOriginLimit',
      'description' => '查询预热回源限速限制',
      'rate_limit' => 20,
      'doc_id' => '126842',
    ),
    6 => 
    array (
      'action' => 'ModifyPrefetchOriginLimit',
      'description' => '配置预热回源限速限制',
      'rate_limit' => 20,
      'doc_id' => '126841',
    ),
  ),
  '4.9' => 
  array (
    0 => 
    array (
      'action' => 'DescribeDDoSAttackData',
      'description' => '查询 DDoS 攻击时序数据',
      'rate_limit' => 100,
      'doc_id' => '80660',
    ),
    1 => 
    array (
      'action' => 'DescribeDDoSAttackEvent',
      'description' => '查询 DDoS 攻击事件列表',
      'rate_limit' => 100,
      'doc_id' => '80659',
    ),
    2 => 
    array (
      'action' => 'DescribeDDoSAttackTopData',
      'description' => '查询 DDoS 攻击 Top 数据',
      'rate_limit' => 100,
      'doc_id' => '80656',
    ),
    3 => 
    array (
      'action' => 'DescribeTimingL7OriginPullData',
      'description' => '查询回源时序数据',
      'rate_limit' => 20,
      'doc_id' => '123320',
    ),
    4 => 
    array (
      'action' => 'DescribeTimingL4Data',
      'description' => '查询四层流量时序数据',
      'rate_limit' => 100,
      'doc_id' => '80649',
    ),
    5 => 
    array (
      'action' => 'DescribeTimingL7AnalysisData',
      'description' => '查询流量分析时序数据',
      'rate_limit' => 30,
      'doc_id' => '80648',
    ),
    6 => 
    array (
      'action' => 'DescribeTopL7AnalysisData',
      'description' => '查询流量分析 Top 数据',
      'rate_limit' => 30,
      'doc_id' => '80646',
    ),
  ),
  '4.10' => 
  array (
    0 => 
    array (
      'action' => 'DownloadL7Logs',
      'description' => '下载七层离线日志',
      'rate_limit' => 100,
      'doc_id' => '80635',
    ),
    1 => 
    array (
      'action' => 'DownloadL4Logs',
      'description' => '下载四层离线日志',
      'rate_limit' => 100,
      'doc_id' => '80636',
    ),
    2 => 
    array (
      'action' => 'CreateCLSIndex',
      'description' => '创建 CLS 索引',
      'rate_limit' => 20,
      'doc_id' => '104113',
    ),
    3 => 
    array (
      'action' => 'CreateRealtimeLogDeliveryTask',
      'description' => '创建实时日志投递任务',
      'rate_limit' => 20,
      'doc_id' => '104112',
    ),
    4 => 
    array (
      'action' => 'ModifyRealtimeLogDeliveryTask',
      'description' => '修改实时日志投递任务',
      'rate_limit' => 20,
      'doc_id' => '104109',
    ),
    5 => 
    array (
      'action' => 'DeleteRealtimeLogDeliveryTask',
      'description' => '删除实时日志投递任务',
      'rate_limit' => 20,
      'doc_id' => '104111',
    ),
    6 => 
    array (
      'action' => 'DescribeRealtimeLogDeliveryTasks',
      'description' => '查询实时日志投递任务列表',
      'rate_limit' => 20,
      'doc_id' => '104110',
    ),
  ),
  '4.11' => 
  array (
    0 => 
    array (
      'action' => 'CreatePlan',
      'description' => '创建套餐',
      'rate_limit' => 20,
      'doc_id' => '105771',
    ),
    1 => 
    array (
      'action' => 'DescribePlans',
      'description' => '查询套餐信息列表',
      'rate_limit' => 20,
      'doc_id' => '118485',
    ),
    2 => 
    array (
      'action' => 'UpgradePlan',
      'description' => '升级套餐',
      'rate_limit' => 20,
      'doc_id' => '105766',
    ),
    3 => 
    array (
      'action' => 'RenewPlan',
      'description' => '续费套餐',
      'rate_limit' => 20,
      'doc_id' => '105767',
    ),
    4 => 
    array (
      'action' => 'ModifyPlan',
      'description' => '修改套餐配置',
      'rate_limit' => 20,
      'doc_id' => '105768',
    ),
    5 => 
    array (
      'action' => 'IncreasePlanQuota',
      'description' => '增购套餐配额',
      'rate_limit' => 20,
      'doc_id' => '105769',
    ),
    6 => 
    array (
      'action' => 'DestroyPlan',
      'description' => '销毁套餐',
      'rate_limit' => 20,
      'doc_id' => '105770',
    ),
    7 => 
    array (
      'action' => 'CreatePlanForZone',
      'description' => '为未购买套餐的站点购买套餐',
      'rate_limit' => 20,
      'doc_id' => '80607',
    ),
    8 => 
    array (
      'action' => 'BindZoneToPlan',
      'description' => '为站点绑定套餐',
      'rate_limit' => 20,
      'doc_id' => '83042',
    ),
    9 => 
    array (
      'action' => 'DescribeBillingData',
      'description' => '查询计费数据',
      'rate_limit' => 50,
      'doc_id' => '103562',
    ),
    10 => 
    array (
      'action' => 'DescribeAvailablePlans',
      'description' => '查询当前账户可购买套餐信息列表',
      'rate_limit' => 20,
      'doc_id' => '80606',
    ),
  ),
  '4.12' => 
  array (
    0 => 
    array (
      'action' => 'DescribeDefaultCertificates',
      'description' => '查询默认证书列表',
      'rate_limit' => 20,
      'doc_id' => '80603',
    ),
    1 => 
    array (
      'action' => 'ModifyHostsCertificate',
      'description' => '配置域名证书',
      'rate_limit' => 20,
      'doc_id' => '80764',
    ),
    2 => 
    array (
      'action' => 'ApplyFreeCertificate',
      'description' => '申请免费证书',
      'rate_limit' => 20,
      'doc_id' => '124807',
    ),
    3 => 
    array (
      'action' => 'CheckFreeCertificateVerification',
      'description' => '检查免费证书申请结果',
      'rate_limit' => 20,
      'doc_id' => '124806',
    ),
  ),
  '4.13' => 
  array (
    0 => 
    array (
      'action' => 'EnableOriginACL',
      'description' => '开启源站防护',
      'rate_limit' => 20,
      'doc_id' => '120406',
    ),
    1 => 
    array (
      'action' => 'ModifyOriginACL',
      'description' => '变更源站防护实例',
      'rate_limit' => 20,
      'doc_id' => '120405',
    ),
    2 => 
    array (
      'action' => 'DescribeOriginACL',
      'description' => '查询源站防护详情',
      'rate_limit' => 20,
      'doc_id' => '120408',
    ),
    3 => 
    array (
      'action' => 'ConfirmOriginACLUpdate',
      'description' => '确认回源 IP 网段更新',
      'rate_limit' => 20,
      'doc_id' => '120409',
    ),
    4 => 
    array (
      'action' => 'DisableOriginACL',
      'description' => '关闭源站防护',
      'rate_limit' => 20,
      'doc_id' => '120407',
    ),
  ),
  '4.14' => 
  array (
    0 => 
    array (
      'action' => 'CreateOriginGroup',
      'description' => '创建源站组',
      'rate_limit' => 20,
      'doc_id' => '80598',
    ),
    1 => 
    array (
      'action' => 'ModifyOriginGroup',
      'description' => '修改源站组',
      'rate_limit' => 20,
      'doc_id' => '80592',
    ),
    2 => 
    array (
      'action' => 'DeleteOriginGroup',
      'description' => '删除源站组',
      'rate_limit' => 20,
      'doc_id' => '80596',
    ),
    3 => 
    array (
      'action' => 'DescribeOriginGroup',
      'description' => '获取源站组列表',
      'rate_limit' => 20,
      'doc_id' => '80594',
    ),
    4 => 
    array (
      'action' => 'CreateLoadBalancer',
      'description' => '创建负载均衡实例',
      'rate_limit' => 20,
      'doc_id' => '111970',
    ),
    5 => 
    array (
      'action' => 'ModifyLoadBalancer',
      'description' => '修改负载均衡实例',
      'rate_limit' => 20,
      'doc_id' => '111969',
    ),
    6 => 
    array (
      'action' => 'DeleteLoadBalancer',
      'description' => '删除负载均衡实例',
      'rate_limit' => 20,
      'doc_id' => '111973',
    ),
    7 => 
    array (
      'action' => 'DescribeLoadBalancerList',
      'description' => '查询负载均衡实例列表',
      'rate_limit' => 20,
      'doc_id' => '111972',
    ),
    8 => 
    array (
      'action' => 'DescribeOriginGroupHealthStatus',
      'description' => '查询负载均衡实例下源站组健康状态',
      'rate_limit' => 20,
      'doc_id' => '111971',
    ),
  ),
  '4.15' => 
  array (
    0 => 
    array (
      'action' => 'DescribeIPRegion',
      'description' => '查询 IP 归属信息',
      'rate_limit' => 20,
      'doc_id' => '102227',
    ),
  ),
  '4.16' => 
  array (
    0 => 
    array (
      'action' => 'CreateCustomizeErrorPage',
      'description' => '创建自定义响应页面',
      'rate_limit' => 20,
      'doc_id' => '107118',
    ),
    1 => 
    array (
      'action' => 'DescribeCustomErrorPages',
      'description' => '查询自定义响应页面列表',
      'rate_limit' => 20,
      'doc_id' => '107116',
    ),
    2 => 
    array (
      'action' => 'ModifyCustomErrorPage',
      'description' => '修改自定义响应页面',
      'rate_limit' => 20,
      'doc_id' => '107115',
    ),
    3 => 
    array (
      'action' => 'DeleteCustomErrorPage',
      'description' => '删除自定义响应页面',
      'rate_limit' => 20,
      'doc_id' => '107117',
    ),
  ),
  '4.17' => 
  array (
    0 => 
    array (
      'action' => 'CreateConfigGroupVersion',
      'description' => '创建配置组版本',
      'rate_limit' => 20,
      'doc_id' => '101867',
    ),
    1 => 
    array (
      'action' => 'DeployConfigGroupVersion',
      'description' => '发布配置组版本',
      'rate_limit' => 20,
      'doc_id' => '101866',
    ),
    2 => 
    array (
      'action' => 'DescribeConfigGroupVersionDetail',
      'description' => '查询配置组版本详情',
      'rate_limit' => 20,
      'doc_id' => '101865',
    ),
    3 => 
    array (
      'action' => 'DescribeConfigGroupVersions',
      'description' => '查询配置组版本列表',
      'rate_limit' => 20,
      'doc_id' => '101864',
    ),
    4 => 
    array (
      'action' => 'DescribeDeployHistory',
      'description' => '查询版本发布历史',
      'rate_limit' => 20,
      'doc_id' => '101863',
    ),
    5 => 
    array (
      'action' => 'DescribeEnvironments',
      'description' => '查询环境信息',
      'rate_limit' => 20,
      'doc_id' => '101862',
    ),
    6 => 
    array (
      'action' => 'ModifyZoneWorkMode',
      'description' => '修改站点工作模式',
      'rate_limit' => 20,
      'doc_id' => '127806',
    ),
  ),
  '4.18' => 
  array (
    0 => 
    array (
      'action' => 'CreateSecurityAPIResource',
      'description' => '创建 API 资源',
      'rate_limit' => 20,
      'doc_id' => '122125',
    ),
    1 => 
    array (
      'action' => 'DescribeSecurityAPIResource',
      'description' => '查询 API 资源',
      'rate_limit' => 20,
      'doc_id' => '122121',
    ),
    2 => 
    array (
      'action' => 'ModifySecurityAPIResource',
      'description' => '修改 API 资源',
      'rate_limit' => 20,
      'doc_id' => '122119',
    ),
    3 => 
    array (
      'action' => 'DeleteSecurityAPIResource',
      'description' => '删除 API 资源',
      'rate_limit' => 20,
      'doc_id' => '122123',
    ),
    4 => 
    array (
      'action' => 'CreateSecurityAPIService',
      'description' => '创建 API 服务',
      'rate_limit' => 20,
      'doc_id' => '122124',
    ),
    5 => 
    array (
      'action' => 'DescribeSecurityAPIService',
      'description' => '查询 API 服务',
      'rate_limit' => 20,
      'doc_id' => '122120',
    ),
    6 => 
    array (
      'action' => 'ModifySecurityAPIService',
      'description' => '修改 API 服务',
      'rate_limit' => 20,
      'doc_id' => '122118',
    ),
    7 => 
    array (
      'action' => 'DeleteSecurityAPIService',
      'description' => '删除 API 服务',
      'rate_limit' => 20,
      'doc_id' => '122122',
    ),
  ),
  '4.19' => 
  array (
    0 => 
    array (
      'action' => 'CreateDnsRecord',
      'description' => '创建 DNS 记录',
      'rate_limit' => 20,
      'doc_id' => '80720',
    ),
    1 => 
    array (
      'action' => 'DescribeDnsRecords',
      'description' => '查询 DNS 记录列表',
      'rate_limit' => 20,
      'doc_id' => '80716',
    ),
    2 => 
    array (
      'action' => 'ModifyDnsRecords',
      'description' => '批量修改 DNS 记录',
      'rate_limit' => 20,
      'doc_id' => '114252',
    ),
    3 => 
    array (
      'action' => 'ModifyDnsRecordsStatus',
      'description' => '批量修改 DNS 记录状态',
      'rate_limit' => 20,
      'doc_id' => '114251',
    ),
    4 => 
    array (
      'action' => 'DeleteDnsRecords',
      'description' => '批量删除 DNS 记录',
      'rate_limit' => 20,
      'doc_id' => '80718',
    ),
  ),
  '4.20' => 
  array (
    0 => 
    array (
      'action' => 'CreateContentIdentifier',
      'description' => '创建内容标识符',
      'rate_limit' => 20,
      'doc_id' => '114249',
    ),
    1 => 
    array (
      'action' => 'DescribeContentIdentifiers',
      'description' => '批量查询内容标识符',
      'rate_limit' => 20,
      'doc_id' => '114247',
    ),
    2 => 
    array (
      'action' => 'ModifyContentIdentifier',
      'description' => '修改内容标识符',
      'rate_limit' => 20,
      'doc_id' => '114246',
    ),
    3 => 
    array (
      'action' => 'DeleteContentIdentifier',
      'description' => '删除内容标识符',
      'rate_limit' => 20,
      'doc_id' => '114248',
    ),
  ),
  '4.21' => 
  array (
    0 => 
    array (
      'action' => 'VerifyOwnership',
      'description' => '验证归属权',
      'rate_limit' => 20,
      'doc_id' => '98879',
    ),
  ),
  '4.22' => 
  array (
    0 => 
    array (
      'action' => 'CreateJustInTimeTranscodeTemplate',
      'description' => '创建即时转码模板',
      'rate_limit' => 20,
      'doc_id' => '122116',
    ),
    1 => 
    array (
      'action' => 'DescribeJustInTimeTranscodeTemplates',
      'description' => '获取即时转码模板列表',
      'rate_limit' => 20,
      'doc_id' => '122114',
    ),
    2 => 
    array (
      'action' => 'DeleteJustInTimeTranscodeTemplates',
      'description' => '删除即时转码模板',
      'rate_limit' => 20,
      'doc_id' => '122115',
    ),
  ),
  '4.23' => 
  array (
    0 => 
    array (
      'action' => 'ConfirmMultiPathGatewayOriginACL',
      'description' => '确认多通道安全加速网关回源 IP 网段更新',
      'rate_limit' => 20,
      'doc_id' => '123860',
    ),
    1 => 
    array (
      'action' => 'DescribeMultiPathGatewayOriginACL',
      'description' => '查询多通道安全加速网关源站防护详情',
      'rate_limit' => 20,
      'doc_id' => '123859',
    ),
    2 => 
    array (
      'action' => 'CreateMultiPathGateway',
      'description' => '创建多通道安全加速网关',
      'rate_limit' => 20,
      'doc_id' => '121343',
    ),
    3 => 
    array (
      'action' => 'DescribeMultiPathGateways',
      'description' => '查询多通道安全加速网关列表',
      'rate_limit' => 20,
      'doc_id' => '121334',
    ),
    4 => 
    array (
      'action' => 'DescribeMultiPathGateway',
      'description' => '查询多通道安全加速网关详情',
      'rate_limit' => 20,
      'doc_id' => '121338',
    ),
    5 => 
    array (
      'action' => 'ModifyMultiPathGateway',
      'description' => '修改多通道安全加速网关信息',
      'rate_limit' => 20,
      'doc_id' => '121333',
    ),
    6 => 
    array (
      'action' => 'ModifyMultiPathGatewayStatus',
      'description' => '修改多通道安全加速网关状态',
      'rate_limit' => 20,
      'doc_id' => '123858',
    ),
    7 => 
    array (
      'action' => 'DeleteMultiPathGateway',
      'description' => '删除多通道安全加速网关',
      'rate_limit' => 20,
      'doc_id' => '121340',
    ),
    8 => 
    array (
      'action' => 'DescribeMultiPathGatewayRegions',
      'description' => '查询多通道安全加速网关可用地域列表',
      'rate_limit' => 20,
      'doc_id' => '121336',
    ),
    9 => 
    array (
      'action' => 'CreateMultiPathGatewaySecretKey',
      'description' => '创建多通道安全加速网关密钥',
      'rate_limit' => 20,
      'doc_id' => '121341',
    ),
    10 => 
    array (
      'action' => 'DescribeMultiPathGatewaySecretKey',
      'description' => '查询多通道安全加速网关接入密钥',
      'rate_limit' => 20,
      'doc_id' => '121335',
    ),
    11 => 
    array (
      'action' => 'ModifyMultiPathGatewaySecretKey',
      'description' => '修改多通道安全加速网关接入密钥',
      'rate_limit' => 20,
      'doc_id' => '121331',
    ),
    12 => 
    array (
      'action' => 'RefreshMultiPathGatewaySecretKey',
      'description' => '刷新多通道安全加速网关密钥',
      'rate_limit' => 20,
      'doc_id' => '121330',
    ),
    13 => 
    array (
      'action' => 'CreateMultiPathGatewayLine',
      'description' => '创建多通道安全加速网关线路',
      'rate_limit' => 20,
      'doc_id' => '121342',
    ),
    14 => 
    array (
      'action' => 'DescribeMultiPathGatewayLine',
      'description' => '查询多通道安全加速网关线路详情',
      'rate_limit' => 20,
      'doc_id' => '121337',
    ),
    15 => 
    array (
      'action' => 'ModifyMultiPathGatewayLine',
      'description' => '修改多通道安全加速网关线路信息',
      'rate_limit' => 20,
      'doc_id' => '121332',
    ),
    16 => 
    array (
      'action' => 'DeleteMultiPathGatewayLine',
      'description' => '删除多通道安全加速网关线路',
      'rate_limit' => 20,
      'doc_id' => '121339',
    ),
  ),
  '4.24' => 
  array (
    0 => 
    array (
      'action' => 'CreateEdgeKVNamespace',
      'description' => '创建 KV 命名空间',
      'rate_limit' => 20,
      'doc_id' => '130189',
    ),
    1 => 
    array (
      'action' => 'DeleteEdgeKVNamespace',
      'description' => '删除 KV 命名空间',
      'rate_limit' => 20,
      'doc_id' => '130188',
    ),
    2 => 
    array (
      'action' => 'DescribeEdgeKVNamespaces',
      'description' => '查询 KV 命名空间',
      'rate_limit' => 20,
      'doc_id' => '130187',
    ),
    3 => 
    array (
      'action' => 'EdgeKVDelete',
      'description' => '删除 KV 数据',
      'rate_limit' => 20,
      'doc_id' => '130186',
    ),
    4 => 
    array (
      'action' => 'EdgeKVGet',
      'description' => '查询 KV 数据',
      'rate_limit' => 20,
      'doc_id' => '130185',
    ),
    5 => 
    array (
      'action' => 'EdgeKVList',
      'description' => '查询 KV 键名列表',
      'rate_limit' => 20,
      'doc_id' => '130184',
    ),
    6 => 
    array (
      'action' => 'EdgeKVPut',
      'description' => '写入 KV 数据',
      'rate_limit' => 20,
      'doc_id' => '130183',
    ),
    7 => 
    array (
      'action' => 'ModifyEdgeKVNamespace',
      'description' => '修改 KV 命名空间',
      'rate_limit' => 20,
      'doc_id' => '130182',
    ),
  ),
);
    }

    /**
     * @param string $action
     * @return array<string, mixed>|null
     */
    public static function find($action)
    {
        foreach (self::registry() as $items) {
            foreach ($items as $item) {
                if ($item['action'] === $action) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * @param string $action
     * @return string
     */
    public static function docUrl($action)
    {
        $item = self::find($action);
        if ($item === null) {
            return 'https://cloud.tencent.com/document/api/1552/80731';
        }

        return 'https://cloud.tencent.com/document/api/1552/' . $item['doc_id'];
    }
}
