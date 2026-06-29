<?php
/**
 * 文件：admin/cdn/edgeone/includes/overview-kpi.php
 * 作用：概览侧栏 KPI 卡片定义、聚合与迷你折线图
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

/**
 * @return array<string, array{label: string, metric: string, agg: string, unit: string, icon: string, default?: bool, mobile?: bool}>
 */
function vs_edgeone_overview_kpi_definitions()
{
  return array(
    'l7Flow_flux'           => array(
      'label'   => 'L7 访问流量',
      'metric'  => 'l7Flow_flux',
      'agg'     => 'sum',
      'unit'    => 'bytes',
      'icon'    => 'flux',
      'default' => true,
      'mobile'  => true,
    ),
    'l7Flow_totalBandwidth' => array(
      'label'   => 'L7 访问带宽',
      'metric'  => 'l7Flow_totalBandwidth',
      'agg'     => 'peak',
      'unit'    => 'bps',
      'icon'    => 'bandwidth',
      'default' => true,
      'mobile'  => true,
    ),
    'l7Flow_request'        => array(
      'label'   => 'L7 访问请求数',
      'metric'  => 'l7Flow_request',
      'agg'     => 'sum',
      'unit'    => 'count',
      'icon'    => 'request',
      'default' => true,
      'mobile'  => true,
    ),
    'l7Flow_mitigatedRequest' => array(
      'label'   => 'L7 防护命中次数',
      'metric'  => 'l7Flow_mitigatedRequest',
      'agg'     => 'sum',
      'unit'    => 'count',
      'icon'    => 'shield',
      'default' => true,
      'mobile'  => true,
    ),
    'l7Flow_requestPeak'    => array(
      'label'   => 'L7 访问请求速率',
      'metric'  => 'l7Flow_request',
      'agg'     => 'peak',
      'unit'    => 'qps',
      'icon'    => 'rate',
      'default' => true,
      'mobile'  => true,
    ),
    'l7Flow_hitRate'        => array(
      'label'   => '缓存命中率',
      'metric'  => 'l7Flow_hitRate',
      'agg'     => 'avg',
      'unit'    => 'percent',
      'icon'    => 'cache',
      'default' => true,
      'mobile'  => true,
    ),
    'l7Flow_outFlux'        => array(
      'label'   => 'EdgeOne 响应流量',
      'metric'  => 'l7Flow_outFlux',
      'agg'     => 'sum',
      'unit'    => 'bytes',
      'icon'    => 'out',
      'default' => false,
      'mobile'  => false,
    ),
    'l7Flow_inFlux'         => array(
      'label'   => '客户端请求流量',
      'metric'  => 'l7Flow_inFlux',
      'agg'     => 'sum',
      'unit'    => 'bytes',
      'icon'    => 'in',
      'default' => false,
      'mobile'  => false,
    ),
  );
}

/**
 * @return array<string, string>
 */
function vs_edgeone_kpi_agg_labels()
{
  return array(
    'sum'  => '总和',
    'peak' => '峰值',
    'avg'  => '平均值',
  );
}

/**
 * @param array<int, array{ts: int, value: float}> $points
 * @param int $width
 * @param int $height
 * @param string $color
 * @return string
 */
function vs_edgeone_render_sparkline_svg(array $points, $width = 120, $height = 36, $color = '#1677ff')
{
  if (count($points) < 2) {
    return '';
  }
  $vals = array();
  foreach ($points as $p) {
    $vals[] = (float) $p['value'];
  }
  $min = min($vals);
  $max = max($vals);
  if ($max <= $min) {
    $max = $min + 1;
  }
  $pad = 2;
  $w = (int) $width;
  $h = (int) $height;
  $n = count($vals);
  $coords = array();
  for ($i = 0; $i < $n; $i++) {
    $x = $pad + ($w - 2 * $pad) * ($i / max(1, $n - 1));
    $y = $pad + ($h - 2 * $pad) * (1 - (($vals[$i] - $min) / ($max - $min)));
    $coords[] = round($x, 1) . ',' . round($y, 1);
  }
  $path = 'M' . implode(' L', $coords);

  return '<svg class="vs-edgeone-kpi__spark" viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '" preserveAspectRatio="none" aria-hidden="true">'
    . '<path d="' . $path . '" fill="none" stroke="' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . '" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>'
    . '</svg>';
}

/**
 * @param array<string, array{meta: array, series: array, sum: float|null, peak: float|null, avg: float|null, error: string}> $charts
 * @param bool $mobileOnly
 * @return array<int, array{label: string, value: string, hint: string, icon: string, points: array, id: string}>
 */
function vs_edgeone_overview_kpi_items_from_charts(array $charts, $mobileOnly = false)
{
  $defs = vs_edgeone_overview_kpi_definitions();
  $aggLabels = vs_edgeone_kpi_agg_labels();
  $items = array();

  foreach ($defs as $id => $def) {
    if (empty($def['default'])) {
      continue;
    }
    if ($mobileOnly && empty($def['mobile'])) {
      continue;
    }
    $metric = isset($def['metric']) ? (string) $def['metric'] : $id;
    $chart = isset($charts[$metric]) ? $charts[$metric] : (isset($charts[$id]) ? $charts[$id] : null);
    if ($chart === null) {
      continue;
    }

    $unit = isset($def['unit']) ? (string) $def['unit'] : 'number';
    $agg = isset($def['agg']) ? (string) $def['agg'] : 'sum';
    $hint = isset($aggLabels[$agg]) ? $aggLabels[$agg] : '';

    $value = '-';
    if ($agg === 'sum' && isset($chart['sum']) && $chart['sum'] !== null) {
      $value = $unit === 'percent'
        ? number_format((float) $chart['sum'], 2) . '%'
        : vs_edgeone_format_metric_value($chart['sum'], $unit === 'qps' ? 'count' : $unit);
      if ($unit === 'qps' && $chart['sum'] !== null) {
        $value = number_format((float) $chart['sum'], 2) . ' qps';
      }
    } elseif ($agg === 'peak' && isset($chart['peak']) && $chart['peak'] !== null) {
      $value = vs_edgeone_format_metric_value($chart['peak'], $unit === 'qps' ? 'count' : $unit);
      if ($unit === 'qps') {
        $value = number_format((float) $chart['peak'], 2) . ' qps';
      }
    } elseif ($agg === 'avg' && isset($chart['avg']) && $chart['avg'] !== null) {
      $value = $unit === 'percent'
        ? number_format((float) $chart['avg'], 2) . '%'
        : vs_edgeone_format_metric_value($chart['avg'], $unit);
    }

    $points = array();
    if (isset($chart['spark_points']) && is_array($chart['spark_points'])) {
      $points = $chart['spark_points'];
    } elseif (isset($chart['series'][0]['points'])) {
      $points = $chart['series'][0]['points'];
    }

    $items[] = array(
      'id'     => (string) $id,
      'label'  => (string) $def['label'],
      'value'  => $value,
      'hint'   => $hint,
      'icon'   => (string) $def['icon'],
      'points' => $points,
    );
  }

  return $items;
}
