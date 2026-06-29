<?php
/**
 * 文件：admin/cdn/edgeone/includes/metrics.php
 * 作用：EdgeOne 指标定义、时间范围与数据解析
 */

/**
 * @return array<string, array{label: string, seconds: int}>
 */
function vs_edgeone_analytics_ranges()
{
    return array(
        '30min'     => array('label' => '近 30 分钟', 'seconds' => 1800),
        '1h'        => array('label' => '近 1 小时', 'seconds' => 3600),
        '6h'        => array('label' => '近 6 小时', 'seconds' => 21600),
        'today'     => array('label' => '今天', 'kind' => 'today'),
        'yesterday' => array('label' => '昨天', 'kind' => 'yesterday'),
        '3d'        => array('label' => '近 3 天', 'seconds' => 259200),
        '7d'        => array('label' => '近 7 天', 'seconds' => 604800),
        '14d'       => array('label' => '近 14 天', 'seconds' => 1209600),
        '30d'       => array('label' => '近 30 天', 'seconds' => 2592000),
    );
}

/**
 * @return array<string, array{label: string, unit: string, group: string}>
 */
function vs_edgeone_l7_analysis_metrics()
{
    return array(
        'l7Flow_outFlux'                  => array('label' => 'EdgeOne 响应流量', 'unit' => 'bytes', 'group' => '流量'),
        'l7Flow_inFlux'                   => array('label' => '客户端请求流量', 'unit' => 'bytes', 'group' => '流量'),
        'l7Flow_flux'                     => array('label' => '访问总流量', 'unit' => 'bytes', 'group' => '流量'),
        'l7Flow_request'                  => array('label' => '访问请求数', 'unit' => 'count', 'group' => '请求'),
        'l7Flow_outBandwidth'             => array('label' => '响应带宽', 'unit' => 'bps', 'group' => '带宽'),
        'l7Flow_inBandwidth'              => array('label' => '客户端请求带宽', 'unit' => 'bps', 'group' => '带宽'),
        'l7Flow_avgResponseTime'          => array('label' => '平均响应耗时', 'unit' => 'ms', 'group' => '性能'),
        'l7Flow_avgFirstByteResponseTime' => array('label' => '平均首字节耗时', 'unit' => 'ms', 'group' => '性能'),
        'l7Flow_hitRequest'               => array('label' => '缓存命中次数', 'unit' => 'count', 'group' => '缓存'),
    );
}

/**
 * @return array<string, array{label: string, unit: string, group: string}>
 */
function vs_edgeone_origin_metrics()
{
    return array(
        'l7Flow_outFlux_hy'      => array('label' => '回源请求流量', 'unit' => 'bytes', 'group' => '回源流量'),
        'l7Flow_inFlux_hy'       => array('label' => '源站响应流量', 'unit' => 'bytes', 'group' => '回源流量'),
        'l7Flow_outBandwidth_hy' => array('label' => '回源请求带宽', 'unit' => 'bps', 'group' => '回源带宽'),
        'l7Flow_inBandwidth_hy'  => array('label' => '源站响应带宽', 'unit' => 'bps', 'group' => '回源带宽'),
        'l7Flow_request_hy'      => array('label' => '回源请求数', 'unit' => 'count', 'group' => '回源请求'),
    );
}

/**
 * @return array<string, array{label: string, unit: string, group: string}>
 */
function vs_edgeone_l4_metrics()
{
    return array(
        'l4Flow_flux'    => array('label' => '四层总流量', 'unit' => 'bytes', 'group' => '四层'),
        'l4Flow_inFlux'  => array('label' => '四层入流量', 'unit' => 'bytes', 'group' => '四层'),
        'l4Flow_outFlux' => array('label' => '四层出流量', 'unit' => 'bytes', 'group' => '四层'),
        'l4Flow_conn'    => array('label' => '四层连接数', 'unit' => 'count', 'group' => '四层'),
    );
}

/**
 * @return array<string, array{label: string, unit: string}>
 */
function vs_edgeone_billing_metrics()
{
    return array(
        'acc_flux'     => array('label' => '内容加速流量', 'unit' => 'bytes'),
        'acc_bandwidth'=> array('label' => '内容加速带宽', 'unit' => 'bps'),
        'sec_flux'     => array('label' => '安全防护流量', 'unit' => 'bytes'),
        'sec_request'  => array('label' => '安全请求数', 'unit' => 'count'),
        'smart_flux'   => array('label' => '智能加速流量', 'unit' => 'bytes'),
        'smart_request'=> array('label' => '智能加速请求数', 'unit' => 'count'),
        'l4_flux'      => array('label' => '四层代理流量', 'unit' => 'bytes'),
    );
}

/**
 * @param string $source l7|origin|l4
 * @return array<string, array{label: string, unit: string, group?: string}>
 */
function vs_edgeone_metrics_for_source($source)
{
    if ($source === 'origin') {
        return vs_edgeone_origin_metrics();
    }
    if ($source === 'l4') {
        return vs_edgeone_l4_metrics();
    }

    return vs_edgeone_l7_analysis_metrics();
}

/**
 * @param string $metric
 * @param string $source
 * @return array{label: string, unit: string, group: string}
 */
function vs_edgeone_metric_meta($metric, $source = 'l7')
{
    if ((string) $metric === 'l7Flow_totalBandwidth') {
        return array('label' => '访问总带宽', 'unit' => 'bps', 'group' => '带宽');
    }

    $all = vs_edgeone_metrics_for_source($source);
    if (isset($all[$metric])) {
        $meta = $all[$metric];
        if (!isset($meta['group'])) {
            $meta['group'] = '';
        }
        return $meta;
    }

    $billing = vs_edgeone_billing_metrics();
    if (isset($billing[$metric])) {
        return array_merge($billing[$metric], array('group' => '计费'));
    }

    return array('label' => $metric, 'unit' => 'number', 'group' => '');
}

/**
 * @param string $rangeKey
 * @return array{label: string, times: array{StartTime: string, EndTime: string}}
 */
function vs_edgeone_analytics_range_preset($rangeKey)
{
    $ranges = vs_edgeone_analytics_ranges();
    if (!isset($ranges[$rangeKey])) {
        $rangeKey = '7d';
    }
    $cfg = $ranges[$rangeKey];
    $end = strtotime('-10 minutes');

    if (isset($cfg['kind']) && $cfg['kind'] === 'today') {
        return array(
            'label' => $cfg['label'],
            'times' => array(
                'StartTime' => date('Y-m-d\T00:00:00+08:00', $end),
                'EndTime'   => date('Y-m-d\TH:i:s+08:00', $end),
            ),
        );
    }

    if (isset($cfg['kind']) && $cfg['kind'] === 'yesterday') {
        $day = strtotime('-1 day', $end);

        return array(
            'label' => $cfg['label'],
            'times' => array(
                'StartTime' => date('Y-m-d\T00:00:00+08:00', $day),
                'EndTime'   => date('Y-m-d\T23:59:59+08:00', $day),
            ),
        );
    }

    $start = $end - (int) $cfg['seconds'];

    return array(
        'label' => $cfg['label'],
        'times' => array(
            'StartTime' => date('Y-m-d\TH:i:s+08:00', $start),
            'EndTime'   => date('Y-m-d\TH:i:s+08:00', $end),
        ),
    );
}

/**
 * @param string $kind today|month
 * @return array{StartTime: string, EndTime: string}
 */
function vs_edgeone_billing_window($kind)
{
    $end = strtotime('-10 minutes');
    if ($kind === 'today') {
        return array(
            'StartTime' => date('Y-m-d\T00:00:00+08:00', $end),
            'EndTime'   => date('Y-m-d\TH:i:s+08:00', $end),
        );
    }

    return array(
        'StartTime' => date('Y-m-01\T00:00:00+08:00', $end),
        'EndTime'   => date('Y-m-d\TH:i:s+08:00', $end),
    );
}

/**
 * @param mixed $value
 * @param string $unit bytes|bps|count|ms|number
 * @return string
 */
function vs_edgeone_format_metric_value($value, $unit)
{
    if ($value === null || $value === '') {
        return '-';
    }
    $n = (float) $value;

    if ($unit === 'bytes') {
        return vs_edgeone_format_bytes($n);
    }
    if ($unit === 'bps') {
        if ($n >= 1000000000) {
            return number_format($n / 1000000000, 2) . ' Gbps';
        }
        if ($n >= 1000000) {
            return number_format($n / 1000000, 2) . ' Mbps';
        }
        if ($n >= 1000) {
            return number_format($n / 1000, 2) . ' Kbps';
        }
        return number_format($n, 0) . ' bps';
    }
    if ($unit === 'ms') {
        return number_format($n, 2) . ' ms';
    }

    return vs_edgeone_format_number($n);
}

/**
 * @param array<string, mixed> $response
 * @return array<int, array{metric: string, avg: mixed, max: mixed, sum: mixed, points: array<int, array{ts: int, value: float}>}>
 */
function vs_edgeone_extract_timing_series(array $response)
{
    $records = array();
    if (isset($response['Data']) && is_array($response['Data'])) {
        $records = $response['Data'];
    } elseif (isset($response['TimingDataRecords']) && is_array($response['TimingDataRecords'])) {
        $records = $response['TimingDataRecords'];
    }

    $out = array();
    foreach ($records as $block) {
        if (!is_array($block)) {
            continue;
        }
        $values = isset($block['TypeValue']) && is_array($block['TypeValue']) ? $block['TypeValue'] : array();
        foreach ($values as $tv) {
            if (!is_array($tv)) {
                continue;
            }
            $points = array();
            $detail = isset($tv['Detail']) && is_array($tv['Detail']) ? $tv['Detail'] : array();
            foreach ($detail as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $points[] = array(
                    'ts'    => (int) (isset($row['Timestamp']) ? $row['Timestamp'] : 0),
                    'value' => (float) (isset($row['Value']) ? $row['Value'] : 0),
                );
            }
            $out[] = array(
                'metric' => isset($tv['MetricName']) ? (string) $tv['MetricName'] : '',
                'avg'    => isset($tv['Avg']) ? $tv['Avg'] : null,
                'max'    => isset($tv['Max']) ? $tv['Max'] : null,
                'sum'    => isset($tv['Sum']) ? $tv['Sum'] : null,
                'points' => $points,
            );
        }
    }

    return $out;
}

/**
 * @param array<int, array<string, mixed>> $rows
 * @return float
 */
function vs_edgeone_sum_series_values(array $rows)
{
    $sum = 0.0;
    foreach ($rows as $row) {
        $sum += (float) (isset($row['Value']) ? $row['Value'] : 0);
    }

    return $sum;
}

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @param string  $source
 * @param string  $metric
 * @param string  $rangeKey
 * @return array
 */
function vs_edgeone_query_analytics(EdgeOne $eo, $zoneId, $source, $metric, $rangeKey)
{
    $range = vs_edgeone_analytics_range_preset($rangeKey);
    $params = array_merge($range['times'], array(
        'ZoneIds'     => vs_edgeone_zone_ids($zoneId),
        'MetricNames' => array($metric),
    ));

    if ($source === 'origin') {
        return $eo->analytics->describeTimingL7OriginPullData($params);
    }
    if ($source === 'l4') {
        return $eo->analytics->describeTimingL4Data($params);
    }

    return vs_edgeone_query_l7_metric($eo, $zoneId, $metric, $rangeKey);
}

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @param string  $metric
 * @param array{StartTime: string, EndTime: string} $window
 * @return array
 */
function vs_edgeone_query_billing_series(EdgeOne $eo, $zoneId, $metric, array $window)
{
    return $eo->billing->describeBillingData(array_merge($window, array(
        'MetricName' => $metric,
        'ZoneIds'    => vs_edgeone_zone_ids($zoneId),
    )));
}

/**
 * 概览页向 API 请求的指标（不含需本地汇总的虚拟指标）
 *
 * @return array<int, string>
 */
function vs_edgeone_overview_api_metric_keys()
{
    return array_keys(vs_edgeone_l7_analysis_metrics());
}

/**
 * @return array<string, string>
 */
function vs_edgeone_overview_metric_keys()
{
    $keys = vs_edgeone_overview_api_metric_keys();
    $keys[] = 'l7Flow_totalBandwidth';

    return $keys;
}

/**
 * @param array<string, array{meta: array, series: array, sum: float|null, error: string}> $charts
 * @return array<string, array{meta: array, series: array, sum: float|null, error: string}>
 */
function vs_edgeone_append_total_bandwidth_chart(array $charts)
{
    $outKey = 'l7Flow_outBandwidth';
    $inKey = 'l7Flow_inBandwidth';
    $totalKey = 'l7Flow_totalBandwidth';

    if (!isset($charts[$outKey]) || !isset($charts[$inKey])) {
        return $charts;
    }

    $outChart = $charts[$outKey];
    $inChart = $charts[$inKey];
    $series = array();
    $maxLen = max(count($outChart['series']), count($inChart['series']));

    for ($i = 0; $i < $maxLen; $i++) {
        $outSeries = isset($outChart['series'][$i]) ? $outChart['series'][$i] : array('label' => '', 'points' => array());
        $inSeries = isset($inChart['series'][$i]) ? $inChart['series'][$i] : array('label' => $outSeries['label'], 'points' => array());
        $label = isset($outSeries['label']) && $outSeries['label'] !== ''
            ? (string) $outSeries['label']
            : (isset($inSeries['label']) ? (string) $inSeries['label'] : '');
        $points = vs_edgeone_merge_series_sum(
            isset($outSeries['points']) ? $outSeries['points'] : array(),
            isset($inSeries['points']) ? $inSeries['points'] : array()
        );
        $item = array('label' => $label, 'points' => $points);
        if (!empty($outSeries['is_total']) || !empty($inSeries['is_total'])) {
            $item['is_total'] = true;
        }
        $series[] = $item;
    }

    $sum = null;
    if ($outChart['sum'] !== null || $inChart['sum'] !== null) {
        $sum = (float) ($outChart['sum'] !== null ? $outChart['sum'] : 0)
            + (float) ($inChart['sum'] !== null ? $inChart['sum'] : 0);
    }

    $charts[$totalKey] = array(
        'meta'   => array(
            'label' => '访问总带宽',
            'unit'  => 'bps',
            'group' => '带宽',
        ),
        'series' => $series,
        'sum'    => $sum,
        'error'  => trim((string) $outChart['error'] . ' ' . (string) $inChart['error']),
    );

    return $charts;
}

/**
 * @return array{range: string, filter_zone: string, filter_domain: string, custom_filters: array<int, array{key: string, operator: string, values: array<int, string>}>}
 */
function vs_edgeone_overview_filters_default()
{
    return array(
        'range'          => 'today',
        'filter_zone'    => '*',
        'filter_domain'  => '',
        'custom_filters' => array(),
    );
}

/**
 * @param array<string, mixed> $src
 * @return array{range: string, filter_zone: string, filter_domain: string, custom_filters: array<int, array{key: string, operator: string, values: array<int, string>}>}
 */
function vs_edgeone_overview_filters_normalize(array $src)
{
    $defaults = vs_edgeone_overview_filters_default();
    $ranges = vs_edgeone_analytics_ranges();

    $rangeKey = isset($src['range']) ? (string) $src['range'] : $defaults['range'];
    if (!isset($ranges[$rangeKey])) {
        $rangeKey = $defaults['range'];
    }

    $filterZone = isset($src['filter_zone']) ? trim((string) $src['filter_zone']) : $defaults['filter_zone'];
    if ($filterZone === '') {
        $filterZone = '*';
    }

    $customRaw = array();
    if (isset($src['custom_filters_json'])) {
        $customRaw = $src['custom_filters_json'];
    } elseif (isset($src['custom_filters'])) {
        $customRaw = $src['custom_filters'];
    }

    return array(
        'range'          => $rangeKey,
        'filter_zone'    => $filterZone,
        'filter_domain'  => isset($src['filter_domain']) ? trim((string) $src['filter_domain']) : '',
        'custom_filters' => vs_edgeone_custom_filters_normalize($customRaw),
    );
}

/**
 * @param array{range: string, filter_zone: string, filter_domain: string, custom_filters: array} $filters
 * @return void
 */
function vs_edgeone_overview_filters_save(array $filters)
{
    $_SESSION['vs_edgeone_overview_filters'] = $filters;
}

/**
 * @param array<string, mixed>|null $src
 * @return array{range: string, filter_zone: string, filter_domain: string}
 */
function vs_edgeone_overview_filters_from_request($src = null)
{
    if ($src === null) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['range'])) {
            $src = $_POST;
        } elseif (!empty($_GET['range']) || !empty($_GET['filter_zone'])) {
            $src = $_GET;
        } else {
            $src = array();
        }
    }

    if (!empty($src) && (isset($src['range']) || isset($src['filter_zone']))) {
        $filters = vs_edgeone_overview_filters_normalize($src);
        vs_edgeone_overview_filters_save($filters);

        return $filters;
    }

    if (isset($_SESSION['vs_edgeone_overview_filters']) && is_array($_SESSION['vs_edgeone_overview_filters'])) {
        return vs_edgeone_overview_filters_normalize($_SESSION['vs_edgeone_overview_filters']);
    }

    return vs_edgeone_overview_filters_default();
}

/**
 * @return array{source: string, metric: string, range: string}
 */
function vs_edgeone_analytics_filters_default()
{
    return array(
        'source' => 'l7',
        'metric' => 'l7Flow_outFlux',
        'range'  => '7d',
    );
}

/**
 * @param array<string, mixed> $src
 * @return array{source: string, metric: string, range: string}
 */
function vs_edgeone_analytics_filters_normalize(array $src)
{
    $defaults = vs_edgeone_analytics_filters_default();
    $ranges = vs_edgeone_analytics_ranges();

    $source = isset($src['source']) ? (string) $src['source'] : $defaults['source'];
    if (!in_array($source, array('l7', 'origin', 'l4'), true)) {
        $source = $defaults['source'];
    }

    $metrics = vs_edgeone_metrics_for_source($source);
    $metric = isset($src['metric']) ? (string) $src['metric'] : $defaults['metric'];
    if (!isset($metrics[$metric])) {
        $keys = array_keys($metrics);
        $metric = count($keys) > 0 ? (string) $keys[0] : $defaults['metric'];
    }

    $rangeKey = isset($src['range']) ? (string) $src['range'] : $defaults['range'];
    if (!isset($ranges[$rangeKey])) {
        $rangeKey = $defaults['range'];
    }

    return array(
        'source' => $source,
        'metric' => $metric,
        'range'  => $rangeKey,
    );
}

/**
 * @param array{source: string, metric: string, range: string} $filters
 * @return void
 */
function vs_edgeone_analytics_filters_save(array $filters)
{
    $_SESSION['vs_edgeone_analytics_filters'] = $filters;
}

/**
 * @param array<string, mixed>|null $src
 * @return array{source: string, metric: string, range: string}
 */
function vs_edgeone_analytics_filters_from_request($src = null)
{
    if ($src === null) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['range'])) {
            $src = $_POST;
        } elseif (!empty($_GET['range']) || !empty($_GET['metric']) || !empty($_GET['source'])) {
            $src = $_GET;
        } else {
            $src = array();
        }
    }

    if (!empty($src) && (isset($src['range']) || isset($src['metric']) || isset($src['source']))) {
        $filters = vs_edgeone_analytics_filters_normalize($src);
        vs_edgeone_analytics_filters_save($filters);

        return $filters;
    }

    if (isset($_SESSION['vs_edgeone_analytics_filters']) && is_array($_SESSION['vs_edgeone_analytics_filters'])) {
        return vs_edgeone_analytics_filters_normalize($_SESSION['vs_edgeone_analytics_filters']);
    }

    return vs_edgeone_analytics_filters_default();
}

/**
 * @return array{metric: string, range: string}
 */
function vs_edgeone_billing_filters_default()
{
    return array(
        'metric' => 'acc_flux',
        'range'  => '7d',
    );
}

/**
 * @param array<string, mixed> $src
 * @return array{metric: string, range: string}
 */
function vs_edgeone_billing_filters_normalize(array $src)
{
    $defaults = vs_edgeone_billing_filters_default();
    $ranges = vs_edgeone_analytics_ranges();
    $billingMetrics = vs_edgeone_billing_metrics();

    $metric = isset($src['metric']) ? (string) $src['metric'] : $defaults['metric'];
    if (!isset($billingMetrics[$metric])) {
        $metric = $defaults['metric'];
    }

    $rangeKey = isset($src['range']) ? (string) $src['range'] : $defaults['range'];
    if (!isset($ranges[$rangeKey])) {
        $rangeKey = $defaults['range'];
    }

    return array(
        'metric' => $metric,
        'range'  => $rangeKey,
    );
}

/**
 * @param array{metric: string, range: string} $filters
 * @return void
 */
function vs_edgeone_billing_filters_save(array $filters)
{
    $_SESSION['vs_edgeone_billing_filters'] = $filters;
}

/**
 * @param array<string, mixed>|null $src
 * @return array{metric: string, range: string}
 */
function vs_edgeone_billing_filters_from_request($src = null)
{
    if ($src === null) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['range'])) {
            $src = $_POST;
        } elseif (!empty($_GET['range']) || !empty($_GET['metric'])) {
            $src = $_GET;
        } else {
            $src = array();
        }
    }

    if (!empty($src) && (isset($src['range']) || isset($src['metric']))) {
        $filters = vs_edgeone_billing_filters_normalize($src);
        vs_edgeone_billing_filters_save($filters);

        return $filters;
    }

    if (isset($_SESSION['vs_edgeone_billing_filters']) && is_array($_SESSION['vs_edgeone_billing_filters'])) {
        return vs_edgeone_billing_filters_normalize($_SESSION['vs_edgeone_billing_filters']);
    }

    return vs_edgeone_billing_filters_default();
}

/**
 * 指标 API 查询规格（display key => 实际 MetricName + 附加 Filters）
 *
 * @param string $metric
 * @return array{api_metric: string, filters: array<int, array{Key: string, Operator: string, Value: array<int, string>}>}
 */
function vs_edgeone_l7_metric_query_spec($metric)
{
    if ((string) $metric === 'l7Flow_hitRequest') {
        return array(
            'api_metric' => 'l7Flow_request',
            'filters'    => array(
                vs_edgeone_analytics_filter('cacheType', 'hit', 'equals'),
            ),
        );
    }

    return array(
        'api_metric' => (string) $metric,
        'filters'    => array(),
    );
}

/**
 * @param string $metric
 * @param string $domain
 * @param array<int, array{key: string, operator: string, values: array<int, string>}> $customFilters
 * @return array<int, array{Key: string, Operator: string, Value: array<int, string>}>
 */
function vs_edgeone_build_l7_query_filters($metric, $domain, array $customFilters)
{
    $spec = vs_edgeone_l7_metric_query_spec($metric);
    $filters = $spec['filters'];

    if ($domain !== '') {
        $filters[] = vs_edgeone_analytics_filter('domain', $domain, 'equals');
    }

    $filters = array_merge(
        $filters,
        vs_edgeone_custom_filters_to_api($customFilters, array('domain'))
    );

    return $filters;
}

/**
 * @param array<int, array<string, mixed>> $zones
 * @param string $filterZone
 * @return array<int, string>
 */
function vs_edgeone_overview_zone_ids(array $zones, $filterZone)
{
    if ($filterZone !== '' && $filterZone !== '*') {
        return array($filterZone);
    }

    $ids = array();
    foreach ($zones as $zone) {
        if (!empty($zone['ZoneId'])) {
            $ids[] = (string) $zone['ZoneId'];
        }
    }

    return count($ids) > 0 ? $ids : array('*');
}

/**
 * @param EdgeOne $eo
 * @param string  $metric   展示用指标 key
 * @param string  $rangeKey
 * @param string  $domain
 * @param array<int, array{key: string, operator: string, values: array<int, string>}> $customFilters
 * @param array<int, string> $zoneIds
 * @return array
 */
function vs_edgeone_query_l7_metric(EdgeOne $eo, $zoneId, $metric, $rangeKey, $domain = '', array $customFilters = array())
{
    return vs_edgeone_query_l7_metric_zones($eo, array($zoneId), $metric, $rangeKey, $domain, $customFilters);
}

/**
 * @param EdgeOne $eo
 * @param array<int, string> $zoneIds
 * @param string $metric
 * @param string $rangeKey
 * @param string $domain
 * @param array<int, array{key: string, operator: string, values: array<int, string>}> $customFilters
 * @return array
 */
function vs_edgeone_query_l7_metric_zones(EdgeOne $eo, array $zoneIds, $metric, $rangeKey, $domain = '', array $customFilters = array())
{
    $spec = vs_edgeone_l7_metric_query_spec($metric);
    $range = vs_edgeone_analytics_range_preset($rangeKey);
    $params = array_merge($range['times'], array(
        'ZoneIds'     => $zoneIds,
        'MetricNames' => array($spec['api_metric']),
    ));

    $filters = vs_edgeone_build_l7_query_filters($metric, $domain, $customFilters);
    if (count($filters) > 0) {
        $params['Filters'] = $filters;
    }

    return $eo->analytics->describeTimingL7AnalysisData($params);
}

/**
 * @param EdgeOne $eo
 * @param array<int, string> $zoneIds
 * @param string $metricName
 * @param string $rangeKey
 * @param string $domain
 * @param array<int, array{key: string, operator: string, values: array<int, string>}> $customFilters
 * @param int $limit
 * @return array
 */
function vs_edgeone_query_l7_top(EdgeOne $eo, array $zoneIds, $metricName, $rangeKey, $domain = '', array $customFilters = array(), $limit = 10)
{
    $range = vs_edgeone_analytics_range_preset($rangeKey);
    $params = array_merge($range['times'], array(
        'ZoneIds'    => $zoneIds,
        'MetricName' => (string) $metricName,
        'Limit'      => max(1, min(100, (int) $limit)),
    ));

    $filters = vs_edgeone_build_l7_query_filters('', $domain, $customFilters);
    if (count($filters) > 0) {
        $params['Filters'] = $filters;
    }

    return $eo->analytics->describeTopL7AnalysisData($params);
}

/**
 * @param array<string, mixed> $response
 * @return array<int, array{key: string, value: float}>
 */
function vs_edgeone_extract_top_data(array $response)
{
    $rows = array();
    $data = isset($response['Data']) && is_array($response['Data']) ? $response['Data'] : array();

    foreach ($data as $block) {
        if (!is_array($block)) {
            continue;
        }
        $details = isset($block['DetailData']) && is_array($block['DetailData']) ? $block['DetailData'] : array();
        foreach ($details as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rows[] = array(
                'key'   => isset($row['Key']) ? (string) $row['Key'] : '',
                'value' => (float) (isset($row['Value']) ? $row['Value'] : 0),
            );
        }
    }

    usort($rows, function ($a, $b) {
        return $b['value'] <=> $a['value'];
    });

    return $rows;
}

/**
 * @return array<int, array{metric: string, title: string, unit: string}>
 */
function vs_edgeone_overview_top_panels()
{
    return array(
        array('metric' => 'l7Flow_outFlux_domain', 'title' => 'Host 排行', 'unit' => 'bytes'),
        array('metric' => 'l7Flow_outFlux_sip', 'title' => '客户端 IP 排行', 'unit' => 'bytes'),
        array('metric' => 'l7Flow_outFlux_referers', 'title' => 'Referer 排行', 'unit' => 'bytes'),
        array('metric' => 'l7Flow_outFlux_url', 'title' => 'URL Path 排行', 'unit' => 'bytes'),
        array('metric' => 'l7Flow_outFlux_resourceType', 'title' => '资源类型排行', 'unit' => 'bytes'),
        array('metric' => 'l7Flow_outFlux_statusCode', 'title' => '状态码排行', 'unit' => 'bytes'),
        array('metric' => 'l7Flow_outFlux_ua_browser', 'title' => '客户端浏览器排行', 'unit' => 'bytes'),
        array('metric' => 'l7Flow_outFlux_ua_device', 'title' => '客户端设备类型排行', 'unit' => 'bytes'),
        array('metric' => 'l7Flow_outFlux_ua_os', 'title' => '客户端操作系统排行', 'unit' => 'bytes'),
    );
}

/**
 * @param EdgeOne $eo
 * @param array<int, array<string, mixed>> $zones
 * @param array $filters
 * @return array
 */
function vs_edgeone_fetch_overview_dashboard(EdgeOne $eo, array $zones, array $filters)
{
    $zoneIds = vs_edgeone_overview_zone_ids($zones, $filters['filter_zone']);
    $domain = isset($filters['filter_domain']) ? (string) $filters['filter_domain'] : '';
    $custom = isset($filters['custom_filters']) && is_array($filters['custom_filters']) ? $filters['custom_filters'] : array();
    $rangeKey = isset($filters['range']) ? (string) $filters['range'] : 'today';

    $kpiMetrics = array('l7Flow_flux', 'l7Flow_outFlux', 'l7Flow_inFlux', 'l7Flow_hitRequest', 'l7Flow_request');
    $charts = array();
    foreach ($kpiMetrics as $metric) {
        $charts[$metric] = array(
            'meta'   => vs_edgeone_metric_meta($metric, 'l7'),
            'series' => array(),
            'sum'    => null,
            'error'  => '',
        );
    }

    $fluxChart = array(
        'meta'   => vs_edgeone_metric_meta('l7Flow_flux', 'l7'),
        'series' => array(),
        'sum'    => null,
        'error'  => '',
    );

    $result = vs_edgeone_try_call(function () use ($eo, $zoneIds, $kpiMetrics, $rangeKey, $domain, $custom) {
        return vs_edgeone_query_l7_metric_zones($eo, $zoneIds, 'l7Flow_flux', $rangeKey, $domain, $custom);
    });
    if ($result['ok']) {
        $extracted = vs_edgeone_extract_timing_series($result['data']);
        $spec = vs_edgeone_l7_metric_query_spec('l7Flow_flux');
        $fluxChart['series'][] = array(
            'label'  => '总流量',
            'points' => vs_edgeone_series_points_for_metric($extracted, $spec['api_metric']),
        );
        foreach ($extracted as $block) {
            if (isset($block['metric']) && (string) $block['metric'] === (string) $spec['api_metric'] && isset($block['sum'])) {
                $fluxChart['sum'] = (float) $block['sum'];
                $charts['l7Flow_flux']['sum'] = (float) $block['sum'];
            }
        }
    } else {
        $fluxChart['error'] = $result['error'];
    }

    foreach (array('l7Flow_outFlux', 'l7Flow_inFlux', 'l7Flow_hitRequest', 'l7Flow_request') as $metric) {
        $call = vs_edgeone_try_call(function () use ($eo, $zoneIds, $metric, $rangeKey, $domain, $custom) {
            return vs_edgeone_query_l7_metric_zones($eo, $zoneIds, $metric, $rangeKey, $domain, $custom);
        });
        if (!$call['ok']) {
            $charts[$metric]['error'] = $call['error'];
            continue;
        }
        $extracted = vs_edgeone_extract_timing_series($call['data']);
        $spec = vs_edgeone_l7_metric_query_spec($metric);
        foreach ($extracted as $block) {
            if (isset($block['metric']) && (string) $block['metric'] === (string) $spec['api_metric'] && isset($block['sum'])) {
                $charts[$metric]['sum'] = (float) $block['sum'];
            }
        }
    }

    $countryTop = array('title' => '访问区域分布', 'rows' => array(), 'error' => '');
    $countryCall = vs_edgeone_try_call(function () use ($eo, $zoneIds, $rangeKey, $domain, $custom) {
        return vs_edgeone_query_l7_top($eo, $zoneIds, 'l7Flow_outFlux_country', $rangeKey, $domain, $custom, 10);
    });
    if ($countryCall['ok']) {
        $countryTop['rows'] = vs_edgeone_extract_top_data($countryCall['data']);
    } else {
        $countryTop['error'] = $countryCall['error'];
    }

    $tops = array();
    foreach (vs_edgeone_overview_top_panels() as $panel) {
        $item = array(
            'title' => $panel['title'],
            'unit'  => $panel['unit'],
            'rows'  => array(),
            'error' => '',
        );
        $topCall = vs_edgeone_try_call(function () use ($eo, $zoneIds, $panel, $rangeKey, $domain, $custom) {
            return vs_edgeone_query_l7_top($eo, $zoneIds, $panel['metric'], $rangeKey, $domain, $custom, 8);
        });
        if ($topCall['ok']) {
            $item['rows'] = vs_edgeone_extract_top_data($topCall['data']);
        } else {
            $item['error'] = $topCall['error'];
        }
        $tops[] = $item;
    }

    return array(
        'summary'     => vs_edgeone_overview_summary_from_charts($charts),
        'flux_chart'  => $fluxChart,
        'country_top' => $countryTop,
        'top_panels'  => $tops,
    );
}

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @param array<int, string> $metrics
 * @param string  $rangeKey
 * @param string  $domain
 * @return array
 */
function vs_edgeone_query_l7_metrics_batch(EdgeOne $eo, $zoneId, array $metrics, $rangeKey, $domain = '')
{
    if (count($metrics) === 1) {
        return vs_edgeone_query_l7_metric($eo, $zoneId, $metrics[0], $rangeKey, $domain);
    }

    $range = vs_edgeone_analytics_range_preset($rangeKey);
    $params = array_merge($range['times'], array(
        'ZoneIds'     => array($zoneId),
        'MetricNames' => array_values($metrics),
    ));

    if ($domain !== '') {
        $params['Filters'] = array(
            vs_edgeone_analytics_filter('domain', $domain, 'equals'),
        );
    }

    return $eo->analytics->describeTimingL7AnalysisData($params);
}

/**
 * @param array<int, array{ts: int, value: float}> $a
 * @param array<int, array{ts: int, value: float}> $b
 * @return array<int, array{ts: int, value: float}>
 */
function vs_edgeone_merge_series_sum(array $a, array $b)
{
    $map = array();
    foreach ($a as $p) {
        $map[(int) $p['ts']] = (float) $p['value'];
    }
    foreach ($b as $p) {
        $ts = (int) $p['ts'];
        $map[$ts] = (isset($map[$ts]) ? $map[$ts] : 0.0) + (float) $p['value'];
    }
    ksort($map);
    $out = array();
    foreach ($map as $ts => $val) {
        $out[] = array('ts' => (int) $ts, 'value' => (float) $val);
    }

    return $out;
}

/**
 * @param array<int, array{metric: string, avg: mixed, max: mixed, sum: mixed, points: array<int, array{ts: int, value: float}>}> $series
 * @param string $metric
 * @return array<int, array{ts: int, value: float}>
 */
function vs_edgeone_series_points_for_metric(array $series, $metric)
{
    foreach ($series as $block) {
        if (!isset($block['metric']) || (string) $block['metric'] !== (string) $metric) {
            continue;
        }
        return isset($block['points']) && is_array($block['points']) ? $block['points'] : array();
    }

    return array();
}

/**
 * @param EdgeOne $eo
 * @param array<int, array<string, mixed>> $zones
 * @param array{range: string, filter_zone: string, filter_domain: string} $filters
 * @return array<string, array{meta: array, series: array<int, array{label: string, points: array, is_total?: bool}>, sum: float|null, error: string}>
 */
function vs_edgeone_fetch_overview_charts(EdgeOne $eo, array $zones, array $filters)
{
    $apiMetrics = vs_edgeone_overview_api_metric_keys();
    $displayMetrics = vs_edgeone_overview_metric_keys();
    $charts = array();
    $targetZones = array();

    if ($filters['filter_zone'] !== '' && $filters['filter_zone'] !== '*') {
        foreach ($zones as $zone) {
            if (isset($zone['ZoneId']) && (string) $zone['ZoneId'] === $filters['filter_zone']) {
                $targetZones[] = $zone;
                break;
            }
        }
    } else {
        $targetZones = $zones;
    }

    foreach ($displayMetrics as $metric) {
        $charts[$metric] = array(
            'meta'   => vs_edgeone_metric_meta($metric, 'l7'),
            'series' => array(),
            'sum'    => null,
            'error'  => '',
        );
    }

    if (count($targetZones) === 0) {
        return $charts;
    }

    $metricPoints = array();
    foreach ($apiMetrics as $metric) {
        $metricPoints[$metric] = array();
    }

    // 按站点 × 指标逐条查询（与数据分析页一致；批量 MetricNames 会触发「无效的参数」）
    foreach ($apiMetrics as $metric) {
        foreach ($targetZones as $zone) {
            $zid = isset($zone['ZoneId']) ? (string) $zone['ZoneId'] : '';
            if ($zid === '') {
                continue;
            }

            $label = vs_edgeone_zone_display_name($zone);
            if ($filters['filter_domain'] !== '') {
                $label .= ' · ' . $filters['filter_domain'];
            }

            $spec = vs_edgeone_l7_metric_query_spec($metric);
            $custom = isset($filters['custom_filters']) && is_array($filters['custom_filters']) ? $filters['custom_filters'] : array();
            $result = vs_edgeone_try_call(function () use ($eo, $zid, $metric, $filters, $custom) {
                return vs_edgeone_query_l7_metric(
                    $eo,
                    $zid,
                    $metric,
                    $filters['range'],
                    $filters['filter_domain'],
                    $custom
                );
            });

            if (!$result['ok']) {
                if ($charts[$metric]['error'] === '') {
                    $charts[$metric]['error'] = $result['error'];
                }
                continue;
            }

            $extracted = vs_edgeone_extract_timing_series($result['data']);
            $points = vs_edgeone_series_points_for_metric($extracted, $spec['api_metric']);
            $metricPoints[$metric][] = array(
                'label'  => $label,
                'points' => $points,
            );

            foreach ($extracted as $block) {
                if (isset($block['metric']) && (string) $block['metric'] === (string) $spec['api_metric'] && isset($block['sum'])) {
                    $prev = $charts[$metric]['sum'] !== null ? (float) $charts[$metric]['sum'] : 0.0;
                    $charts[$metric]['sum'] = $prev + (float) $block['sum'];
                }
            }
        }
    }

    $multiZone = count($targetZones) > 1 && $filters['filter_domain'] === '';
    foreach ($apiMetrics as $metric) {
        $series = isset($metricPoints[$metric]) ? $metricPoints[$metric] : array();
        if ($multiZone && count($series) > 1) {
            $totalPoints = array();
            foreach ($series as $item) {
                $totalPoints = vs_edgeone_merge_series_sum($totalPoints, $item['points']);
            }
            if (count($totalPoints) > 0) {
                array_unshift($series, array(
                    'label'    => '总计',
                    'points'   => $totalPoints,
                    'is_total' => true,
                ));
            }
        }
        $charts[$metric]['series'] = $series;
        if ($charts[$metric]['sum'] !== null && (float) $charts[$metric]['sum'] <= 0) {
            $charts[$metric]['sum'] = null;
        }
        foreach ($series as $item) {
            if (!empty($item['points'])) {
                $charts[$metric]['error'] = '';
                break;
            }
        }
    }

    return vs_edgeone_append_total_bandwidth_chart($charts);
}

/**
 * @param array<string, array{meta: array, series: array, sum: float|null, error: string}> $charts
 * @return array<int, array{label: string, value: string}>
 */
function vs_edgeone_overview_summary_from_charts(array $charts)
{
    $flux = isset($charts['l7Flow_flux']['sum']) ? $charts['l7Flow_flux']['sum'] : null;
    $outFlux = isset($charts['l7Flow_outFlux']['sum']) ? $charts['l7Flow_outFlux']['sum'] : null;
    $inFlux = isset($charts['l7Flow_inFlux']['sum']) ? $charts['l7Flow_inFlux']['sum'] : null;
    $hit = isset($charts['l7Flow_hitRequest']['sum']) ? $charts['l7Flow_hitRequest']['sum'] : null;
    $request = isset($charts['l7Flow_request']['sum']) ? $charts['l7Flow_request']['sum'] : null;

    $hitRate = null;
    if ($request !== null && (float) $request > 0 && $hit !== null) {
        $hitRate = ((float) $hit / (float) $request) * 100;
    }

    return array(
        array(
            'label' => '总流量',
            'value' => vs_edgeone_format_metric_value($flux, 'bytes'),
        ),
        array(
            'label' => 'EdgeOne 响应流量',
            'value' => vs_edgeone_format_metric_value($outFlux, 'bytes'),
        ),
        array(
            'label' => '客户端请求流量',
            'value' => vs_edgeone_format_metric_value($inFlux, 'bytes'),
        ),
        array(
            'label' => '缓存命中率',
            'value' => $hitRate !== null ? number_format($hitRate, 2) . '%' : '-',
        ),
    );
}

/**
 * @param EdgeOne|null $eo
 * @param array<int, array<string, mixed>> $zones
 * @return array{plans: array, usage: array<int, array<string, array{label: string, value: float, unit: string}>>, content_quota: array<string, array{ok: bool, data: mixed, error: string}>}
 */
function vs_edgeone_fetch_overview_quota(EdgeOne $eo, array $zones)
{
    $out = array(
        'plans'         => array(),
        'usage'         => array(),
        'content_quota' => array(),
    );

    if ($eo === null) {
        return $out;
    }

    $planResult = vs_edgeone_try_call(function () use ($eo) {
        return $eo->billing->describePlans(array('Offset' => 0, 'Limit' => 100));
    });
    if ($planResult['ok']) {
        $out['plans'] = isset($planResult['data']['Plans']) && is_array($planResult['data']['Plans'])
            ? $planResult['data']['Plans']
            : array();
    }

    $today = vs_edgeone_billing_window('today');
    foreach ($zones as $zone) {
        $zid = isset($zone['ZoneId']) ? (string) $zone['ZoneId'] : '';
        if ($zid === '') {
            continue;
        }

        $out['content_quota'][$zid] = vs_edgeone_try_call(function () use ($eo, $zid) {
            return $eo->content->describeContentQuota(array('ZoneId' => $zid));
        });

        $usage = array();
        foreach (array(
            'acc_flux'    => array('label' => '今日加速流量', 'unit' => 'bytes'),
            'sec_request' => array('label' => '今日安全请求', 'unit' => 'count'),
        ) as $mKey => $cfg) {
            $usageResult = vs_edgeone_try_call(function () use ($eo, $zid, $mKey, $today) {
                return vs_edgeone_query_billing_series($eo, $zid, $mKey, $today);
            });
            if ($usageResult['ok']) {
                $rows = isset($usageResult['data']['Data']) && is_array($usageResult['data']['Data'])
                    ? $usageResult['data']['Data']
                    : array();
                $usage[$mKey] = array(
                    'label' => $cfg['label'],
                    'value' => vs_edgeone_sum_series_values($rows),
                    'unit'  => $cfg['unit'],
                );
            }
        }
        $out['usage'][$zid] = $usage;
    }

    return $out;
}

