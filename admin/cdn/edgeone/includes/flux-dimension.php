<?php
/**
 * 文件：admin/cdn/edgeone/includes/flux-dimension.php
 * 作用：L7 访问流量多维折线图（维度 Tab + TOP 排行图例）
 */

/**
 * @return array<string, array{label: string, mode: string, metric?: string, filter_key?: string, top_metric?: string, series_label?: string, options?: array<string, string>, more?: bool, limit?: int}>
 */
function vs_edgeone_flux_chart_dimensions()
{
  $cacheOpts = array(
    'hit'     => '命中',
    'miss'    => '未命中',
    'dynamic' => '动态',
    'other'   => '其他',
  );

  return array(
    'all'        => array(
      'label'        => '全部',
      'mode'         => 'single',
      'metric'       => 'l7Flow_flux',
      'series_label' => 'L7 访问流量',
    ),
    'cacheType'    => array(
      'label'      => '缓存状态',
      'mode'       => 'enum',
      'metric'     => 'l7Flow_outFlux',
      'filter_key' => 'cacheType',
      'options'    => $cacheOpts,
    ),
    'country'      => array(
      'label'       => '国家/地区',
      'mode'        => 'top',
      'metric'      => 'l7Flow_outFlux',
      'filter_key'  => 'country',
      'top_metric'  => 'l7Flow_outFlux_country',
      'limit'       => 20,
    ),
    'province'     => array(
      'label'       => '境内省份',
      'mode'        => 'top',
      'metric'      => 'l7Flow_outFlux',
      'filter_key'  => 'province',
      'top_metric'  => 'l7Flow_outFlux_province',
      'limit'       => 20,
    ),
    'domain'       => array(
      'label'       => 'Host',
      'mode'        => 'top',
      'metric'      => 'l7Flow_outFlux',
      'filter_key'  => 'domain',
      'top_metric'  => 'l7Flow_outFlux_domain',
      'limit'       => 20,
    ),
    'statusCode'   => array(
      'label'       => '状态码',
      'mode'        => 'top',
      'metric'      => 'l7Flow_outFlux',
      'filter_key'  => 'statusCode',
      'top_metric'  => 'l7Flow_outFlux_statusCode',
      'limit'       => 20,
    ),
    'clientIp'     => array(
      'label'       => '客户端 IP',
      'mode'        => 'top',
      'metric'      => 'l7Flow_outFlux',
      'filter_key'  => 'clientIp',
      'top_metric'  => 'l7Flow_outFlux_sip',
      'limit'       => 20,
      'more'        => true,
    ),
    'referer'      => array(
      'label'       => 'Referer',
      'mode'        => 'top',
      'metric'      => 'l7Flow_outFlux',
      'filter_key'  => 'referer',
      'top_metric'  => 'l7Flow_outFlux_referers',
      'limit'       => 20,
      'more'        => true,
    ),
    'url'          => array(
      'label'       => 'URL Path',
      'mode'        => 'top',
      'metric'      => 'l7Flow_outFlux',
      'filter_key'  => 'url',
      'top_metric'  => 'l7Flow_outFlux_url',
      'limit'       => 20,
      'more'        => true,
    ),
    'resourceType' => array(
      'label'       => '资源类型',
      'mode'        => 'top',
      'metric'      => 'l7Flow_outFlux',
      'filter_key'  => 'resourceType',
      'top_metric'  => 'l7Flow_outFlux_resourceType',
      'limit'       => 20,
      'more'        => true,
    ),
    'browserType'  => array(
      'label'       => '客户端浏览器',
      'mode'        => 'top',
      'metric'      => 'l7Flow_outFlux',
      'filter_key'  => 'browserType',
      'top_metric'  => 'l7Flow_outFlux_ua_browser',
      'limit'       => 20,
      'more'        => true,
    ),
    'deviceType'   => array(
      'label'       => '客户端设备类型',
      'mode'        => 'top',
      'metric'      => 'l7Flow_outFlux',
      'filter_key'  => 'deviceType',
      'top_metric'  => 'l7Flow_outFlux_ua_device',
      'limit'       => 20,
      'more'        => true,
    ),
    'operatingSystemType' => array(
      'label'       => '客户端操作系统',
      'mode'        => 'top',
      'metric'      => 'l7Flow_outFlux',
      'filter_key'  => 'operatingSystemType',
      'top_metric'  => 'l7Flow_outFlux_ua_os',
      'limit'       => 20,
      'more'        => true,
    ),
  );
}

/**
 * @param string $key
 * @return array|null
 */
function vs_edgeone_flux_dimension_config($key)
{
  $all = vs_edgeone_flux_chart_dimensions();
  if (isset($all[$key])) {
    return $all[$key];
  }

  return isset($all['all']) ? $all['all'] : null;
}

/**
 * @param EdgeOne $eo
 * @param array<int, string> $zoneIds
 * @param string $rangeKey
 * @param string $domain
 * @param array<int, array{key: string, operator: string, values: array<int, string>}> $customFilters
 * @param string $dimensionKey
 * @return array{dimension: string, unit: string, sum: float|null, series: array, legend: array, error: string}
 */
function vs_edgeone_fetch_flux_chart_by_dimension(EdgeOne $eo, array $zoneIds, $rangeKey, $domain, array $customFilters, $dimensionKey)
{
  $dim = vs_edgeone_flux_dimension_config($dimensionKey);
  if ($dim === null) {
    $dim = vs_edgeone_flux_dimension_config('all');
    $dimensionKey = 'all';
  }

  $out = array(
    'dimension' => (string) $dimensionKey,
    'unit'      => 'bytes',
    'sum'       => null,
    'series'    => array(),
    'legend'    => array(),
    'error'     => '',
  );

  if ($dim['mode'] === 'single') {
    $metric = isset($dim['metric']) ? (string) $dim['metric'] : 'l7Flow_flux';
    $call = vs_edgeone_try_call(function () use ($eo, $zoneIds, $metric, $rangeKey, $domain, $customFilters) {
      return vs_edgeone_query_l7_metric_zones($eo, $zoneIds, $metric, $rangeKey, $domain, $customFilters);
    });
    if (!$call['ok']) {
      $out['error'] = $call['error'];
      return $out;
    }
    $extracted = vs_edgeone_extract_timing_series($call['data']);
    $spec = vs_edgeone_l7_metric_query_spec($metric);
    $points = vs_edgeone_series_points_for_metric($extracted, $spec['api_metric']);
    $label = isset($dim['series_label']) ? (string) $dim['series_label'] : 'L7 访问流量';
    foreach ($extracted as $block) {
      if (isset($block['metric']) && (string) $block['metric'] === (string) $spec['api_metric'] && isset($block['sum'])) {
        $out['sum'] = (float) $block['sum'];
      }
    }
    $out['series'][] = array('label' => $label, 'points' => $points, 'is_total' => true);
    $out['legend'][] = array(
      'key'   => '_total',
      'label' => $label . ' - 总和',
      'value' => $out['sum'] !== null ? $out['sum'] : 0,
      'visible' => true,
    );
    return $out;
  }

  if ($dim['mode'] === 'enum') {
    $metric = isset($dim['metric']) ? (string) $dim['metric'] : 'l7Flow_outFlux';
    $filterKey = isset($dim['filter_key']) ? (string) $dim['filter_key'] : '';
    $options = isset($dim['options']) && is_array($dim['options']) ? $dim['options'] : array();
    $totalSum = 0.0;

    foreach ($options as $val => $optLabel) {
      $extra = array(vs_edgeone_analytics_filter($filterKey, $val, 'equals'));
      $call = vs_edgeone_try_call(function () use ($eo, $zoneIds, $metric, $rangeKey, $domain, $customFilters, $extra) {
        return vs_edgeone_query_l7_metric_zones_extra($eo, $zoneIds, $metric, $rangeKey, $domain, $customFilters, $extra);
      });
      if (!$call['ok']) {
        if ($out['error'] === '') {
          $out['error'] = $call['error'];
        }
        continue;
      }
      $extracted = vs_edgeone_extract_timing_series($call['data']);
      $spec = vs_edgeone_l7_metric_query_spec($metric);
      $points = vs_edgeone_series_points_for_metric($extracted, $spec['api_metric']);
      $sum = null;
      foreach ($extracted as $block) {
        if (isset($block['metric']) && (string) $block['metric'] === (string) $spec['api_metric'] && isset($block['sum'])) {
          $sum = (float) $block['sum'];
        }
      }
      if ($sum === null || $sum <= 0 && count($points) === 0) {
        continue;
      }
      $rowSum = $sum !== null ? $sum : 0.0;
      $totalSum += $rowSum;
      $out['series'][] = array('label' => (string) $optLabel, 'points' => $points);
      $out['legend'][] = array(
        'key'     => (string) $val,
        'label'   => (string) $optLabel,
        'value'   => $rowSum,
        'visible' => true,
      );
    }
    $out['sum'] = $totalSum > 0 ? $totalSum : null;
    return $out;
  }

  if ($dim['mode'] === 'top') {
    $metric = isset($dim['metric']) ? (string) $dim['metric'] : 'l7Flow_outFlux';
    $filterKey = isset($dim['filter_key']) ? (string) $dim['filter_key'] : '';
    $topMetric = isset($dim['top_metric']) ? (string) $dim['top_metric'] : '';
    $limit = isset($dim['limit']) ? (int) $dim['limit'] : 20;
    $topCall = vs_edgeone_try_call(function () use ($eo, $zoneIds, $topMetric, $rangeKey, $domain, $customFilters, $limit) {
      return vs_edgeone_query_l7_top($eo, $zoneIds, $topMetric, $rangeKey, $domain, $customFilters, $limit);
    });
    if (!$topCall['ok']) {
      $out['error'] = $topCall['error'];
      return $out;
    }
    $rows = vs_edgeone_extract_top_data($topCall['data']);
    if (count($rows) === 0) {
      return $out;
    }
    $totalSum = 0.0;
    foreach ($rows as $row) {
      $key = isset($row['key']) ? (string) $row['key'] : '';
      if ($key === '') {
        continue;
      }
      $extra = array(vs_edgeone_analytics_filter($filterKey, $key, 'equals'));
      $call = vs_edgeone_try_call(function () use ($eo, $zoneIds, $metric, $rangeKey, $domain, $customFilters, $extra) {
        return vs_edgeone_query_l7_metric_zones_extra($eo, $zoneIds, $metric, $rangeKey, $domain, $customFilters, $extra);
      });
      if (!$call['ok']) {
        continue;
      }
      $extracted = vs_edgeone_extract_timing_series($call['data']);
      $spec = vs_edgeone_l7_metric_query_spec($metric);
      $points = vs_edgeone_series_points_for_metric($extracted, $spec['api_metric']);
      $sum = (float) $row['value'];
      $totalSum += $sum;
      $label = vs_edgeone_flux_series_label($key, $filterKey);
      $out['series'][] = array('label' => $label, 'points' => $points);
      $out['legend'][] = array(
        'key'     => $key,
        'label'   => $label,
        'value'   => $sum,
        'visible' => true,
      );
    }
    $out['sum'] = $totalSum > 0 ? $totalSum : null;
    return $out;
  }

  return $out;
}

/**
 * @param string $key
 * @param string $filterKey
 * @return string
 */
function vs_edgeone_flux_series_label($key, $filterKey)
{
  $key = trim((string) $key);
  if ($key === '') {
    return '-';
  }
  if ($filterKey === 'country') {
    static $countries = array(
      'CN' => '中国大陆', 'US' => '美国', 'HK' => '中国香港', 'TW' => '中国台湾',
      'JP' => '日本', 'SG' => '新加坡', 'KR' => '韩国', 'DE' => '德国', 'GB' => '英国',
    );
    if (isset($countries[$key])) {
      return $countries[$key] . ' (' . $key . ')';
    }
  }
  return $key;
}
