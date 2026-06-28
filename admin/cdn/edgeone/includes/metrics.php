<?php
/**
 * 文件：admin/cdn/edgeone/includes/metrics.php
 * 作用：EdgeOne 指标定义、时间范围与数据解析
 */

/**
 * @return array<string, string>
 */
function vs_edgeone_analytics_intervals()
{
    return array(
        'min'   => '1 分钟',
        '5min'  => '5 分钟',
        'hour'  => '1 小时',
        'day'   => '1 天',
    );
}

/**
 * @return array<string, array{label: string, seconds: int, default_interval: string}>
 */
function vs_edgeone_analytics_ranges()
{
    return array(
        '1min'  => array('label' => '近 1 分钟', 'seconds' => 60, 'default_interval' => 'min'),
        '5min'  => array('label' => '近 5 分钟', 'seconds' => 300, 'default_interval' => 'min'),
        '1h'    => array('label' => '近 1 小时', 'seconds' => 3600, 'default_interval' => 'min'),
        '2h'    => array('label' => '近 2 小时', 'seconds' => 7200, 'default_interval' => 'min'),
        '1d'    => array('label' => '近 1 天', 'seconds' => 86400, 'default_interval' => '5min'),
        '24h'   => array('label' => '近 24 小时', 'seconds' => 86400, 'default_interval' => '5min'),
        '7d'    => array('label' => '近 7 天', 'seconds' => 604800, 'default_interval' => 'hour'),
        '15d'   => array('label' => '近 15 天', 'seconds' => 1296000, 'default_interval' => 'hour'),
        '31d'   => array('label' => '近 31 天', 'seconds' => 2678400, 'default_interval' => 'day'),
        '30d'   => array('label' => '近 30 天', 'seconds' => 2592000, 'default_interval' => 'day'),
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
 * @return array{label: string, times: array{StartTime: string, EndTime: string}, default_interval: string}
 */
function vs_edgeone_analytics_range_preset($rangeKey)
{
    $ranges = vs_edgeone_analytics_ranges();
    if (!isset($ranges[$rangeKey])) {
        $rangeKey = '7d';
    }
    $cfg = $ranges[$rangeKey];
    $end = strtotime('-10 minutes');
    $start = $end - (int) $cfg['seconds'];

    return array(
        'label'             => $cfg['label'],
        'default_interval'  => $cfg['default_interval'],
        'times'             => array(
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
 * @param string  $interval
 * @param string  $rangeKey
 * @return array
 */
function vs_edgeone_query_analytics(EdgeOne $eo, $zoneId, $source, $metric, $interval, $rangeKey)
{
    $range = vs_edgeone_analytics_range_preset($rangeKey);
    $params = array_merge($range['times'], array(
        'ZoneIds'     => vs_edgeone_zone_ids($zoneId),
        'MetricNames' => array($metric),
        'Interval'    => $interval !== '' ? $interval : $range['default_interval'],
    ));

    if ($source === 'origin') {
        return $eo->analytics->describeTimingL7OriginPullData($params);
    }
    if ($source === 'l4') {
        return $eo->analytics->describeTimingL4Data($params);
    }

    return $eo->analytics->describeTimingL7AnalysisData($params);
}

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @param string  $metric
 * @param string  $interval
 * @param array{StartTime: string, EndTime: string} $window
 * @return array
 */
function vs_edgeone_query_billing_series(EdgeOne $eo, $zoneId, $metric, $interval, array $window)
{
    return $eo->billing->describeBillingData(array_merge($window, array(
        'Interval'   => $interval,
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
 * @return array{range: string, interval: string, filter_zone: string, filter_domain: string}
 */
function vs_edgeone_overview_filters_default()
{
    return array(
        'range'         => '7d',
        'interval'      => 'hour',
        'filter_zone'   => '*',
        'filter_domain' => '',
    );
}

/**
 * @param array<string, mixed> $src
 * @return array{range: string, interval: string, filter_zone: string, filter_domain: string}
 */
function vs_edgeone_overview_filters_normalize(array $src)
{
    $defaults = vs_edgeone_overview_filters_default();
    $ranges = vs_edgeone_analytics_ranges();
    $intervals = vs_edgeone_analytics_intervals();

    $rangeKey = isset($src['range']) ? (string) $src['range'] : $defaults['range'];
    if (!isset($ranges[$rangeKey])) {
        $rangeKey = $defaults['range'];
    }

    $preset = vs_edgeone_analytics_range_preset($rangeKey);
    $interval = isset($src['interval']) ? (string) $src['interval'] : $preset['default_interval'];
    if (!isset($intervals[$interval])) {
        $interval = $preset['default_interval'];
    }

    $filterZone = isset($src['filter_zone']) ? trim((string) $src['filter_zone']) : $defaults['filter_zone'];
    if ($filterZone === '') {
        $filterZone = '*';
    }

    return array(
        'range'         => $rangeKey,
        'interval'      => $interval,
        'filter_zone'   => $filterZone,
        'filter_domain' => isset($src['filter_domain']) ? trim((string) $src['filter_domain']) : '',
    );
}

/**
 * @param array{range: string, interval: string, filter_zone: string, filter_domain: string} $filters
 * @return void
 */
function vs_edgeone_overview_filters_save(array $filters)
{
    $_SESSION['vs_edgeone_overview_filters'] = $filters;
}

/**
 * @param array<string, mixed>|null $src
 * @return array{range: string, interval: string, filter_zone: string, filter_domain: string}
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
 * @return array{source: string, metric: string, range: string, interval: string}
 */
function vs_edgeone_analytics_filters_default()
{
    return array(
        'source'   => 'l7',
        'metric'   => 'l7Flow_outFlux',
        'range'    => '7d',
        'interval' => 'hour',
    );
}

/**
 * @param array<string, mixed> $src
 * @return array{source: string, metric: string, range: string, interval: string}
 */
function vs_edgeone_analytics_filters_normalize(array $src)
{
    $defaults = vs_edgeone_analytics_filters_default();
    $ranges = vs_edgeone_analytics_ranges();
    $intervals = vs_edgeone_analytics_intervals();

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

    $preset = vs_edgeone_analytics_range_preset($rangeKey);
    $interval = isset($src['interval']) ? (string) $src['interval'] : $preset['default_interval'];
    if (!isset($intervals[$interval])) {
        $interval = $preset['default_interval'];
    }

    return array(
        'source'   => $source,
        'metric'   => $metric,
        'range'    => $rangeKey,
        'interval' => $interval,
    );
}

/**
 * @param array{source: string, metric: string, range: string, interval: string} $filters
 * @return void
 */
function vs_edgeone_analytics_filters_save(array $filters)
{
    $_SESSION['vs_edgeone_analytics_filters'] = $filters;
}

/**
 * @param array<string, mixed>|null $src
 * @return array{source: string, metric: string, range: string, interval: string}
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
 * @return array{metric: string, range: string, interval: string}
 */
function vs_edgeone_billing_filters_default()
{
    return array(
        'metric'   => 'acc_flux',
        'range'    => '7d',
        'interval' => 'hour',
    );
}

/**
 * @param array<string, mixed> $src
 * @return array{metric: string, range: string, interval: string}
 */
function vs_edgeone_billing_filters_normalize(array $src)
{
    $defaults = vs_edgeone_billing_filters_default();
    $ranges = vs_edgeone_analytics_ranges();
    $intervals = vs_edgeone_analytics_intervals();
    $billingMetrics = vs_edgeone_billing_metrics();

    $metric = isset($src['metric']) ? (string) $src['metric'] : $defaults['metric'];
    if (!isset($billingMetrics[$metric])) {
        $metric = $defaults['metric'];
    }

    $rangeKey = isset($src['range']) ? (string) $src['range'] : $defaults['range'];
    if (!isset($ranges[$rangeKey])) {
        $rangeKey = $defaults['range'];
    }

    $preset = vs_edgeone_analytics_range_preset($rangeKey);
    $interval = isset($src['interval']) ? (string) $src['interval'] : $preset['default_interval'];
    if (!isset($intervals[$interval])) {
        $interval = $preset['default_interval'];
    }

    return array(
        'metric'   => $metric,
        'range'    => $rangeKey,
        'interval' => $interval,
    );
}

/**
 * @param array{metric: string, range: string, interval: string} $filters
 * @return void
 */
function vs_edgeone_billing_filters_save(array $filters)
{
    $_SESSION['vs_edgeone_billing_filters'] = $filters;
}

/**
 * @param array<string, mixed>|null $src
 * @return array{metric: string, range: string, interval: string}
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
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @param array<int, string> $metrics
 * @param string  $interval
 * @param string  $rangeKey
 * @param string  $domain
 * @return array
 */
function vs_edgeone_query_l7_metrics_batch(EdgeOne $eo, $zoneId, array $metrics, $interval, $rangeKey, $domain = '')
{
    $range = vs_edgeone_analytics_range_preset($rangeKey);
    $params = array_merge($range['times'], array(
        'ZoneIds'     => array($zoneId),
        'MetricNames' => array_values($metrics),
        'Interval'    => $interval !== '' ? $interval : $range['default_interval'],
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
 * @param array{range: string, interval: string, filter_zone: string, filter_domain: string} $filters
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

    // 按站点逐站查询（API 不返回分线）；每站一次请求携带全部指标
    foreach ($targetZones as $zone) {
        $zid = isset($zone['ZoneId']) ? (string) $zone['ZoneId'] : '';
        if ($zid === '') {
            continue;
        }

        $label = vs_edgeone_zone_display_name($zone);
        if ($filters['filter_domain'] !== '') {
            $label .= ' · ' . $filters['filter_domain'];
        }

        $result = vs_edgeone_try_call(function () use ($eo, $zid, $apiMetrics, $filters) {
            return vs_edgeone_query_l7_metrics_batch(
                $eo,
                $zid,
                $apiMetrics,
                $filters['interval'],
                $filters['range'],
                $filters['filter_domain']
            );
        });

        if (!$result['ok']) {
            foreach ($apiMetrics as $metric) {
                if ($charts[$metric]['error'] === '') {
                    $charts[$metric]['error'] = $result['error'];
                }
            }
            continue;
        }

        $extracted = vs_edgeone_extract_timing_series($result['data']);

        foreach ($apiMetrics as $metric) {
            $points = vs_edgeone_series_points_for_metric($extracted, $metric);
            $metricPoints[$metric][] = array(
                'label'  => $label,
                'points' => $points,
            );

            foreach ($extracted as $block) {
                if (isset($block['metric']) && (string) $block['metric'] === (string) $metric && isset($block['sum'])) {
                    $prev = isset($charts[$metric]['sum']) ? (float) $charts[$metric]['sum'] : 0.0;
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
    }

    return vs_edgeone_append_total_bandwidth_chart($charts);
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
                return vs_edgeone_query_billing_series($eo, $zid, $mKey, 'hour', $today);
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

