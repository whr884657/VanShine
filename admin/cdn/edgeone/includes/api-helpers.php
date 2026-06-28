<?php
/**
 * 文件：admin/cdn/edgeone/includes/api-helpers.php
 * 作用：EdgeOne 后台 API 参数组装（2022-09-01）
 */

/**
 * DescribeTimingL7AnalysisData 筛选条件（QueryCondition）
 *
 * @param string               $key
 * @param string|array<int,string> $values
 * @param string               $operator
 * @return array{Key: string, Operator: string, Value: array<int, string>}
 */
function vs_edgeone_analytics_filter($key, $values, $operator = 'equals')
{
    if (!is_array($values)) {
        $values = array((string) $values);
    } else {
        $values = array_values(array_map('strval', $values));
    }

    return array(
        'Key'      => (string) $key,
        'Operator' => (string) $operator,
        'Value'    => $values,
    );
}

/**
 * @param string|null $zoneId
 * @return array<int, string>
 */
function vs_edgeone_zone_ids($zoneId = null)
{
    if ($zoneId === null || $zoneId === '') {
        $zoneId = vs_edgeone_selected_zone();
    }

    return $zoneId !== '' ? array($zoneId) : array('*');
}

/**
 * @param int          $days
 * @param array|string $metricNames
 * @param string|null  $zoneId
 * @return array<string, mixed>
 */
function vs_edgeone_analytics_query($days, $metricNames, $zoneId = null)
{
    return array_merge(vs_edgeone_analytics_range($days), array(
        'ZoneIds'     => vs_edgeone_zone_ids($zoneId),
        'MetricNames' => is_array($metricNames) ? $metricNames : array($metricNames),
        'Interval'    => 'day',
    ));
}

/**
 * @param int         $days
 * @param string|null $zoneId
 * @return array<string, mixed>
 */
function vs_edgeone_ddos_event_query($days, $zoneId = null)
{
    return array_merge(vs_edgeone_analytics_range($days), array(
        'ZoneIds' => vs_edgeone_zone_ids($zoneId),
        'Offset'  => 0,
        'Limit'   => 20,
    ));
}

/**
 * @param array<int, array<string, mixed>> $zones
 * @param string                           $zoneId
 * @return string
 */
function vs_edgeone_find_zone_name(array $zones, $zoneId)
{
    foreach ($zones as $zone) {
        if (isset($zone['ZoneId']) && (string) $zone['ZoneId'] === (string) $zoneId) {
            return isset($zone['ZoneName']) ? (string) $zone['ZoneName'] : '';
        }
    }

    return '';
}

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @return array<int, string>
 */
function vs_edgeone_fetch_domain_names(EdgeOne $eo, $zoneId)
{
    $resp = $eo->accelerationDomain->describeAccelerationDomains(array(
        'ZoneId' => $zoneId,
        'Offset' => 0,
        'Limit'  => 100,
    ));
    $domains = isset($resp['AccelerationDomains']) && is_array($resp['AccelerationDomains'])
        ? $resp['AccelerationDomains']
        : array();
    $names = array();
    foreach ($domains as $row) {
        if (!empty($row['DomainName'])) {
            $names[] = (string) $row['DomainName'];
        }
    }

    return $names;
}

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @return array<string, mixed>
 */
function vs_edgeone_fetch_l4_proxy_rules(EdgeOne $eo, $zoneId)
{
    $proxies = $eo->l4Proxy->describeL4Proxy(array('ZoneId' => $zoneId));
    $list = isset($proxies['L4Proxies']) && is_array($proxies['L4Proxies']) ? $proxies['L4Proxies'] : array();
    if (count($list) === 0) {
        return array('TotalCount' => 0, 'L4ProxyRules' => array());
    }

    $first = $list[0];
    $proxyId = '';
    if (isset($first['ProxyId'])) {
        $proxyId = (string) $first['ProxyId'];
    } elseif (isset($first['Sid'])) {
        $proxyId = (string) $first['Sid'];
    }

    if ($proxyId === '') {
        return array('Note' => '无法解析 ProxyId', 'L4Proxies' => $list);
    }

    return $eo->l4Proxy->describeL4ProxyRules(array(
        'ZoneId' => $zoneId,
        'ProxyId' => $proxyId,
        'Offset' => 0,
        'Limit'  => 100,
    ));
}

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @return array<string, mixed>
 */
function vs_edgeone_fetch_lb_health(EdgeOne $eo, $zoneId)
{
    $lbs = $eo->loadBalancer->describeLoadBalancerList(array('ZoneId' => $zoneId));
    $list = isset($lbs['LoadBalancers']) && is_array($lbs['LoadBalancers']) ? $lbs['LoadBalancers'] : array();
    if (count($list) === 0) {
        return array('TotalCount' => 0, 'HealthStatus' => array());
    }

    $first = $list[0];
    $lbId = '';
    if (isset($first['InstanceId'])) {
        $lbId = (string) $first['InstanceId'];
    } elseif (isset($first['LBInstanceId'])) {
        $lbId = (string) $first['LBInstanceId'];
    }

    if ($lbId === '') {
        return array('Note' => '无法解析 LBInstanceId', 'LoadBalancers' => $list);
    }

    return $eo->loadBalancer->describeOriginGroupHealthStatus(array(
        'ZoneId'       => $zoneId,
        'LBInstanceId' => $lbId,
    ));
}

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @return array<string, mixed>
 */
function vs_edgeone_fetch_config_group_versions(EdgeOne $eo, $zoneId)
{
    $envResp = $eo->configVersion->describeEnvironments(array('ZoneId' => $zoneId));
    $groupId = vs_edgeone_pick_config_group_id($envResp);
    if ($groupId === '') {
        return array('TotalCount' => 0, 'ConfigGroupVersions' => array());
    }

    return $eo->configVersion->describeConfigGroupVersions(array(
        'ZoneId'  => $zoneId,
        'GroupId' => $groupId,
        'Offset'  => 0,
        'Limit'   => 50,
    ));
}

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @return array<string, mixed>
 */
function vs_edgeone_fetch_deploy_history(EdgeOne $eo, $zoneId)
{
    $envResp = $eo->configVersion->describeEnvironments(array('ZoneId' => $zoneId));
    $envId = '';
    if (isset($envResp['EnvInfos']) && is_array($envResp['EnvInfos']) && count($envResp['EnvInfos']) > 0) {
        $envId = isset($envResp['EnvInfos'][0]['EnvId']) ? (string) $envResp['EnvInfos'][0]['EnvId'] : '';
    }
    if ($envId === '') {
        return array('TotalCount' => 0, 'DeployHistory' => array());
    }

    return $eo->configVersion->describeDeployHistory(array(
        'ZoneId' => $zoneId,
        'EnvId'  => $envId,
        'Offset' => 0,
        'Limit'  => 50,
    ));
}

/**
 * @param array<string, mixed> $envResp
 * @return string
 */
function vs_edgeone_pick_config_group_id(array $envResp)
{
    if (!isset($envResp['EnvInfos']) || !is_array($envResp['EnvInfos'])) {
        return '';
    }

    foreach ($envResp['EnvInfos'] as $env) {
        if (!isset($env['CurrentConfigGroupVersionInfos']) || !is_array($env['CurrentConfigGroupVersionInfos'])) {
            continue;
        }
        foreach ($env['CurrentConfigGroupVersionInfos'] as $info) {
            if (!empty($info['GroupId'])) {
                return (string) $info['GroupId'];
            }
        }
    }

    return '';
}
