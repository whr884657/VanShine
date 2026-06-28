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
        '2h'  => array('label' => '近 2 小时', 'seconds' => 7200, 'default_interval' => 'min'),
        '24h' => array('label' => '近 24 小时', 'seconds' => 86400, 'default_interval' => '5min'),
        '7d'  => array('label' => '近 7 天', 'seconds' => 604800, 'default_interval' => 'hour'),
        '30d' => array('label' => '近 30 天', 'seconds' => 2592000, 'default_interval' => 'day'),
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
