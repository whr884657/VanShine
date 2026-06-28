<?php
/**
 * 生成分类 Api 类与 EdgeOneActions 注册表
 * 用法：php build-apis.php
 */

$apiDir = __DIR__ . '/Api';
$registryPath = __DIR__ . '/data/actions-registry.php';

$sections = array(
    '4.1'  => array('class' => 'EdgeOneZoneApi', 'file' => 'EdgeOneZoneApi.php'),
    '4.2'  => array('class' => 'EdgeOneAccelerationDomainApi', 'file' => 'EdgeOneAccelerationDomainApi.php'),
    '4.3'  => array('class' => 'EdgeOneL7AccApi', 'file' => 'EdgeOneL7AccApi.php'),
    '4.4'  => array('class' => 'EdgeOneFunctionApi', 'file' => 'EdgeOneFunctionApi.php'),
    '4.5'  => array('class' => 'EdgeOneAliasDomainApi', 'file' => 'EdgeOneAliasDomainApi.php'),
    '4.6'  => array('class' => 'EdgeOneSecurityApi', 'file' => 'EdgeOneSecurityApi.php'),
    '4.7'  => array('class' => 'EdgeOneL4ProxyApi', 'file' => 'EdgeOneL4ProxyApi.php'),
    '4.8'  => array('class' => 'EdgeOneContentApi', 'file' => 'EdgeOneContentApi.php'),
    '4.9'  => array('class' => 'EdgeOneAnalyticsApi', 'file' => 'EdgeOneAnalyticsApi.php'),
    '4.10' => array('class' => 'EdgeOneLogApi', 'file' => 'EdgeOneLogApi.php'),
    '4.11' => array('class' => 'EdgeOneBillingApi', 'file' => 'EdgeOneBillingApi.php'),
    '4.12' => array('class' => 'EdgeOneCertificateApi', 'file' => 'EdgeOneCertificateApi.php'),
    '4.13' => array('class' => 'EdgeOneOriginAclApi', 'file' => 'EdgeOneOriginAclApi.php'),
    '4.14' => array('class' => 'EdgeOneLoadBalancerApi', 'file' => 'EdgeOneLoadBalancerApi.php'),
    '4.15' => array('class' => 'EdgeOneDiagnosisApi', 'file' => 'EdgeOneDiagnosisApi.php'),
    '4.16' => array('class' => 'EdgeOneCustomErrorPageApi', 'file' => 'EdgeOneCustomErrorPageApi.php'),
    '4.17' => array('class' => 'EdgeOneConfigVersionApi', 'file' => 'EdgeOneConfigVersionApi.php'),
    '4.18' => array('class' => 'EdgeOneSecurityResourceApi', 'file' => 'EdgeOneSecurityResourceApi.php'),
    '4.19' => array('class' => 'EdgeOneDnsApi', 'file' => 'EdgeOneDnsApi.php'),
    '4.20' => array('class' => 'EdgeOneContentIdentifierApi', 'file' => 'EdgeOneContentIdentifierApi.php'),
    '4.21' => array('class' => 'EdgeOneOwnershipApi', 'file' => 'EdgeOneOwnershipApi.php'),
    '4.22' => array('class' => 'EdgeOneMediaTranscodeApi', 'file' => 'EdgeOneMediaTranscodeApi.php'),
    '4.23' => array('class' => 'EdgeOneMultiPathGatewayApi', 'file' => 'EdgeOneMultiPathGatewayApi.php'),
    '4.24' => array('class' => 'EdgeOneEdgeKvApi', 'file' => 'EdgeOneEdgeKvApi.php'),
);

$sectionTitles = array(
    '4.1'  => '站点相关',
    '4.2'  => '加速域名管理',
    '4.3'  => '站点加速配置（七层）',
    '4.4'  => '边缘函数',
    '4.5'  => '别称域名',
    '4.6'  => '安全配置',
    '4.7'  => '四层应用代理',
    '4.8'  => '内容管理',
    '4.9'  => '数据分析',
    '4.10' => '日志服务',
    '4.11' => '计费',
    '4.12' => '证书',
    '4.13' => '源站防护',
    '4.14' => '负载均衡',
    '4.15' => '诊断工具',
    '4.16' => '自定义响应页面',
    '4.17' => '版本管理',
    '4.18' => 'API 防护',
    '4.19' => 'DNS 记录',
    '4.20' => '内容标识符',
    '4.21' => '归属权',
    '4.22' => '图片与视频处理',
    '4.23' => '多通道安全加速网关',
    '4.24' => 'KV 存储（EdgeKV）',
);

if (!is_file($registryPath)) {
    fwrite(STDERR, "缺少注册表文件: {$registryPath}\n");
    exit(1);
}

$registry = require $registryPath;

function actionToMethod($action)
{
    return lcfirst($action);
}

function renderApiClass($meta, $actions, $title)
{
    $class = $meta['class'];
    $methods = array();

    foreach ($actions as $item) {
        $method = actionToMethod($item['action']);
        $desc = str_replace(array("\r", "\n", '*'), '', $item['description']);
        $methods[] = "    /**\n     * {$item['action']} — {$desc}\n     *\n     * @param array \$params\n     * @return array\n     * @throws EdgeOneException\n     */\n    public function {$method}(array \$params = array())\n    {\n        return \$this->invoke('{$item['action']}', \$params);\n    }";
    }

    $methodBlock = implode("\n\n", $methods);

    return <<<PHP
<?php
/**
 * 文件：core/cdn/edgeone/Api/{$meta['file']}
 * 作用：EdgeOne {$title}
 * @version 1.0.0
 * @generated build-apis.php
 */

class {$class} extends EdgeOneApiBase
{
{$methodBlock}
}

PHP;
}

$total = 0;
foreach ($sections as $sectionKey => $meta) {
    if (!isset($registry[$sectionKey])) {
        fwrite(STDERR, "警告: 章节 {$sectionKey} 未在注册表中找到\n");
        continue;
    }

    $php = renderApiClass($meta, $registry[$sectionKey], $sectionTitles[$sectionKey]);
    file_put_contents($apiDir . '/' . $meta['file'], $php);
    $total += count($registry[$sectionKey]);
    echo "生成 {$meta['file']} (" . count($registry[$sectionKey]) . " 个 Action)\n";
}

$registryExport = var_export($registry, true);
$registryPhp = <<<PHP
<?php
/**
 * 文件：core/cdn/edgeone/EdgeOneActions.php
 * 作用：EdgeOne Action 注册表（频率、文档 ID）
 * @version 1.0.0
 * @generated build-apis.php
 */

class EdgeOneActions
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function registry()
    {
        return {$registryExport};
    }

    /**
     * @param string \$action
     * @return array<string, mixed>|null
     */
    public static function find(\$action)
    {
        foreach (self::registry() as \$items) {
            foreach (\$items as \$item) {
                if (\$item['action'] === \$action) {
                    return \$item;
                }
            }
        }

        return null;
    }

    /**
     * @param string \$action
     * @return string
     */
    public static function docUrl(\$action)
    {
        \$item = self::find(\$action);
        if (\$item === null) {
            return 'https://cloud.tencent.com/document/api/1552/80731';
        }

        return 'https://cloud.tencent.com/document/api/1552/' . \$item['doc_id'];
    }
}

PHP;

file_put_contents(__DIR__ . '/EdgeOneActions.php', $registryPhp);
echo "生成 EdgeOneActions.php\n";
echo "共 {$total} 个 Action\n";
