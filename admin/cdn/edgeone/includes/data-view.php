<?php
/**
 * 文件：admin/cdn/edgeone/includes/data-view.php
 * 作用：EdgeOne API 响应可视化渲染（表格 / 卡片 / 配额 / 时序）
 */

/**
 * @return array<string, string>
 */
function vs_edgeone_field_labels()
{
    return array(
        'ZoneId'                 => '站点 ID',
        'ZoneName'               => '站点域名',
        'AliasZoneName'          => '站点备注',
        'DomainName'             => '域名',
        'RecordName'             => '记录名',
        'Cname'                  => 'CNAME',
        'Status'                 => '状态',
        'Type'                   => '类型',
        'ActiveStatus'           => '加速状态',
        'Area'                   => '服务区域',
        'JobId'                  => '任务 ID',
        'Target'                 => '目标',
        'CreateTime'             => '创建时间',
        'UpdateTime'             => '更新时间',
        'Subdomain'              => '验证子域',
        'DnsVerificationStatus'  => 'DNS 验证',
        'FileVerificationStatus' => '文件验证',
        'IdentifyPath'           => '验证文件路径',
        'IdentifyContent'        => '验证文件内容',
        'OriginalNameServers'    => '原始 NS',
        'Batch'                  => '单次上限',
        'Daily'                  => '日配额',
        'DailyAvailable'         => '今日剩余',
        'Avg'                    => '平均值',
        'Timestamp'              => '时间',
        'Value'                  => '数值',
        'Metric'                 => '指标',
        'ProxyId'                => '代理 ID',
        'InstanceId'             => '实例 ID',
        'CertId'                 => '证书 ID',
        'FunctionId'             => '函数 ID',
        'FunctionName'           => '函数名称',
        'GroupId'                => '配置组 ID',
        'VersionId'              => '版本 ID',
        'AttackType'             => '攻击类型',
        'AttackStatus'           => '攻击状态',
        'StartTime'              => '开始时间',
        'EndTime'                => '结束时间',
        'PeakBandwidth'          => '峰值带宽',
        'EventId'                => '事件 ID',
        'Tasks'                  => '任务列表',
        'PurgeLogs'              => '刷新记录',
        'PrefetchLogs'           => '预热记录',
    );
}

/**
 * @param string $key
 * @return string
 */
function vs_edgeone_field_label($key)
{
    $labels = vs_edgeone_field_labels();
    if (isset($labels[$key])) {
        return $labels[$key];
    }

    return preg_replace('/([a-z])([A-Z])/', '$1 $2', (string) $key);
}

/**
 * @param string $type
 * @return string
 */
function vs_edgeone_quota_type_label($type)
{
    $map = array(
        'purge_url'                => 'URL 刷新',
        'purge_prefix'             => '目录刷新',
        'purge_host'               => 'Hostname 刷新',
        'purge_all'                => '全部刷新',
        'purge_cache_tag'          => 'Cache Tag 刷新',
        'purge_content_identifier' => '内容标识符刷新',
        'prefetch_url'             => 'URL 预热',
    );

    $type = (string) $type;
    return isset($map[$type]) ? $map[$type] : $type;
}

/**
 * @param string $metric
 * @return string
 */
function vs_edgeone_metric_label($metric)
{
    $map = array(
        'l7Flow_flux'    => '七层流量',
        'l7Flow_request' => '七层请求数',
        'l4Flow_flux'    => '四层流量',
        'l4Flow_conn'    => '四层连接数',
        'acc_flux'       => '加速流量',
        'acc_bandwidth'  => '加速带宽',
    );

    $metric = (string) $metric;
    return isset($map[$metric]) ? $map[$metric] : $metric;
}

/**
 * @param mixed $bytes
 * @return string
 */
function vs_edgeone_format_bytes($bytes)
{
    $n = (float) $bytes;
    if ($n <= 0) {
        return '0 B';
    }
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = 0;
    while ($n >= 1024 && $i < count($units) - 1) {
        $n /= 1024;
        $i++;
    }

    return ($i === 0 ? (string) (int) $n : number_format($n, 2)) . ' ' . $units[$i];
}

/**
 * @param mixed $value
 * @return string
 */
function vs_edgeone_format_number($value)
{
    if (!is_numeric($value)) {
        return (string) $value;
    }

    return number_format((float) $value, is_float($value + 0) && floor($value) != $value ? 2 : 0);
}

/**
 * @param mixed $ts
 * @return string
 */
function vs_edgeone_format_timestamp($ts)
{
    if ($ts === null || $ts === '') {
        return '-';
    }
    if (is_string($ts) && preg_match('/^\d{4}-\d{2}-\d{2}/', $ts)) {
        return $ts;
    }
    $n = (int) $ts;
    if ($n <= 0) {
        return '-';
    }

    return date('Y-m-d H:i', $n);
}

/**
 * @param string $group
 * @param mixed  $value
 * @return string
 */
function vs_edgeone_status_badge_class($group, $value)
{
    $value = strtolower((string) $value);
    $good = array('active', 'online', 'deployed', 'finished', 'success', 'completed');
    $warn = array('pending', 'process', 'processing', 'initializing');
    $bad = array('failed', 'deactivated', 'offline', 'error');

    if (in_array($value, $good, true)) {
        return 'is-success';
    }
    if (in_array($value, $warn, true)) {
        return 'is-warning';
    }
    if (in_array($value, $bad, true)) {
        return 'is-danger';
    }

    return 'is-muted';
}

/**
 * @param string $group
 * @param mixed  $value
 * @return void
 */
function vs_edgeone_render_status_badge($group, $value)
{
    if ($value === null || $value === '') {
        echo '-';
        return;
    }
    $text = vs_edgeone_translate($group, $value);
    $class = vs_edgeone_status_badge_class($group, $value);
    echo '<span class="vs-edgeone-badge ' . vs_e($class) . '">' . vs_e($text) . '</span>';
}

/**
 * @param string $key
 * @param mixed  $value
 * @param array<string, mixed> $ctx
 * @return string
 */
function vs_edgeone_format_cell($key, $value, array $ctx = array())
{
    if ($value === null || $value === '') {
        return '-';
    }

    if (is_bool($value)) {
        return $value ? '是' : '否';
    }

    if (is_array($value)) {
        if (count($value) === 0) {
            return '-';
        }
        if (vs_edgeone_array_is_list($value) && count($value) <= 5 && !is_array($value[0])) {
            return implode('、', array_map('strval', $value));
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    $keyLower = strtolower((string) $key);
    $str = (string) $value;

    if ($key === 'Type' && isset($ctx['quota'])) {
        return vs_edgeone_quota_type_label($str);
    }

    if (in_array($key, array('Status', 'ActiveStatus', 'CnameStatus', 'DomainStatus', 'CertStatus', 'DnsVerificationStatus', 'FileVerificationStatus', 'AttackStatus'), true)) {
        return vs_edgeone_translate($key === 'DnsVerificationStatus' || $key === 'FileVerificationStatus' ? 'VerificationStatus' : $key, $str);
    }

    if ($key === 'Type' && !isset($ctx['quota'])) {
        return vs_edgeone_translate('Type', $str);
    }

    if ($key === 'Area') {
        return vs_edgeone_translate('Area', $str);
    }

    if (strpos($keyLower, 'time') !== false || $key === 'Timestamp') {
        return vs_edgeone_format_timestamp($value);
    }

    if ($key === 'Value' && isset($ctx['metric'])) {
        $metric = (string) $ctx['metric'];
        if (stripos($metric, 'flux') !== false || stripos($metric, 'bandwidth') !== false) {
            return vs_edgeone_format_bytes($value);
        }
        return vs_edgeone_format_number($value);
    }

    if ($key === 'Avg' && isset($ctx['metric'])) {
        $metric = (string) $ctx['metric'];
        if (stripos($metric, 'flux') !== false || stripos($metric, 'bandwidth') !== false) {
            return vs_edgeone_format_bytes($value);
        }
        return vs_edgeone_format_number($value);
    }

    if (is_numeric($value) && strlen($str) > 6 && ($key === 'Value' || $key === 'PeakBandwidth')) {
        return vs_edgeone_format_bytes($value);
    }

    if (is_numeric($value) && strlen($str) > 3) {
        return vs_edgeone_format_number($value);
    }

    return $str;
}

/**
 * @param array<mixed> $arr
 * @return bool
 */
function vs_edgeone_array_is_list(array $arr)
{
    if ($arr === array()) {
        return true;
    }
    return array_keys($arr) === range(0, count($arr) - 1);
}

/**
 * @param mixed $data
 * @return bool
 */
function vs_edgeone_response_is_empty($data)
{
    if ($data === null || $data === '') {
        return true;
    }
    if (!is_array($data)) {
        return false;
    }
    if (count($data) === 0) {
        return true;
    }

    $skip = array('RequestId');
    $hasContent = false;
    foreach ($data as $key => $val) {
        if (in_array($key, $skip, true)) {
            continue;
        }
        if ($key === 'Note' && is_string($val) && $val !== '') {
            return false;
        }
        if (is_array($val)) {
            if (count($val) > 0) {
                $hasContent = true;
                break;
            }
            continue;
        }
        if ($val !== null && $val !== '') {
            $hasContent = true;
            break;
        }
    }

    return !$hasContent;
}

/**
 * @param string $planType
 * @return string
 */
function vs_edgeone_plan_type_label($planType)
{
    $map = array(
        'plan-standard'      => '标准版',
        'plan-enterprise'    => '企业版',
        'plan-enterprise-v2' => '企业版 V2',
        'plan-basic'         => '基础版',
        'plan-personal'      => '个人版',
    );

    $planType = (string) $planType;
    return isset($map[$planType]) ? $map[$planType] : $planType;
}

/**
 * @param string $status
 * @return string
 */
function vs_edgeone_plan_status_label($status)
{
    $map = array(
        'normal'    => '正常',
        'expired'   => '已过期',
        'isolated'  => '已隔离',
        'destroyed' => '已销毁',
    );

    $status = (string) $status;
    return isset($map[$status]) ? $map[$status] : $status;
}

/**
 * @param mixed $data
 * @return void
 */
function vs_edgeone_render_raw_toggle($data)
{
    // 不向用户展示原始 API JSON
}

/**
 * @param array{title?: string, unit?: string, points: array<int, array{ts: int, value: float}>} $cfg
 * @return void
 */
function vs_edgeone_render_line_chart(array $cfg)
{
    $points = isset($cfg['points']) && is_array($cfg['points']) ? $cfg['points'] : array();
    $unit = isset($cfg['unit']) ? (string) $cfg['unit'] : 'number';
    if (count($points) === 0) {
        return;
    }

    $chartId = 'eo-chart-' . substr(md5(uniqid('', true)), 0, 10);
    echo '<div class="vs-edgeone-chart-wrap">';
    echo '<canvas id="' . vs_e($chartId) . '" class="vs-edgeone-chart" height="260"></canvas>';
    echo '<script type="application/json" class="vs-edgeone-chart-data" data-target="' . vs_e($chartId) . '" data-unit="' . vs_e($unit) . '">';
    echo json_encode($points, JSON_UNESCAPED_UNICODE);
    echo '</script>';
    echo '</div>';
}

/**
 * @param array<int, array{label: string, value: string, hint?: string, icon?: string}> $items
 * @return string
 */
function vs_edgeone_render_overview_summary_sidebar(array $items)
{
    ob_start();
    echo '<aside class="vs-edgeone-overview__sidebar" id="edgeoneSummaryHost">';
    $idx = 0;
    foreach ($items as $item) {
        $icon = isset($item['icon']) ? (string) $item['icon'] : 'flux';
        $hint = isset($item['hint']) ? (string) $item['hint'] : '';
        echo '<article class="vs-edgeone-kpi vs-edgeone-kpi--sidebar vs-edgeone-kpi--animate" style="--kpi-delay:' . (int) $idx . '">';
        echo '<div class="vs-edgeone-kpi__head">';
        echo vs_edgeone_kpi_icon_svg($icon);
        echo '<div class="vs-edgeone-kpi__meta">';
        echo '<span class="vs-edgeone-kpi__label">' . vs_e($item['label']) . '</span>';
        if ($hint !== '') {
            echo '<span class="vs-edgeone-kpi__hint">' . vs_e($hint) . '</span>';
        }
        echo '</div>';
        echo '</div>';
        echo '<strong class="vs-edgeone-kpi__value">' . vs_e($item['value']) . '</strong>';
        echo '</article>';
        $idx++;
    }
    echo '</aside>';

    return ob_get_clean();
}

/**
 * 纯 SVG 环形图（不依赖外网地图资源）
 *
 * @param array<int, array{key: string, value: float}> $rows
 * @param float $total
 * @return string
 */
function vs_edgeone_render_country_donut_svg(array $rows, $total)
{
    $total = (float) $total;
    if ($total <= 0 || empty($rows)) {
        return '';
    }

    $colors = array('#1677ff', '#69b1ff', '#95de64', '#ffc069', '#ff7875', '#b37feb', '#5cdbd3', '#ffd666');
    $cx = 90;
    $cy = 90;
    $ro = 72;
    $ri = 46;
    $start = -90.0;
    $segments = array();
    $i = 0;

    foreach ($rows as $row) {
        $val = (float) $row['value'];
        if ($val <= 0) {
            continue;
        }
        $pct = $val / $total;
        $angle = $pct * 360.0;
        if ($angle >= 359.99) {
            $angle = 359.99;
        }
        $end = $start + $angle;
        $large = $angle > 180 ? 1 : 0;

        $sRad = deg2rad($start);
        $eRad = deg2rad($end);
        $x1 = $cx + $ro * cos($sRad);
        $y1 = $cy + $ro * sin($sRad);
        $x2 = $cx + $ro * cos($eRad);
        $y2 = $cy + $ro * sin($eRad);
        $x3 = $cx + $ri * cos($eRad);
        $y3 = $cy + $ri * sin($eRad);
        $x4 = $cx + $ri * cos($sRad);
        $y4 = $cy + $ri * sin($sRad);

        $color = $colors[$i % count($colors)];
        $d = sprintf(
            'M %.2f %.2f A %.2f %.2f 0 %d 1 %.2f %.2f L %.2f %.2f A %.2f %.2f 0 %d 0 %.2f %.2f Z',
            $x1, $y1, $ro, $ro, $large, $x2, $y2,
            $x3, $y3, $ri, $ri, $large, $x4, $y4
        );
        $segments[] = array('d' => $d, 'color' => $color, 'pct' => round($pct * 100, 1));
        $start = $end;
        $i++;
        if ($i >= 8) {
            break;
        }
    }

    $topPct = isset($segments[0]['pct']) ? $segments[0]['pct'] : 0;
    $topLabel = isset($rows[0]['key']) ? vs_edgeone_format_top_label($rows[0]['key']) : '';

    $svg = '<svg class="vs-edgeone-country-donut" viewBox="0 0 180 180" width="180" height="180" role="img" aria-label="访问区域占比">';
    foreach ($segments as $seg) {
        $svg .= '<path d="' . $seg['d'] . '" fill="' . $seg['color'] . '" class="vs-edgeone-country-donut__seg"/>';
    }
    $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $ri . '" fill="#fff"/>';
    $svg .= '<text x="' . $cx . '" y="' . ($cy - 4) . '" text-anchor="middle" class="vs-edgeone-country-donut__pct">' . $topPct . '%</text>';
    $svg .= '<text x="' . $cx . '" y="' . ($cy + 14) . '" text-anchor="middle" class="vs-edgeone-country-donut__label">' . htmlspecialchars(mb_substr($topLabel, 0, 8), ENT_QUOTES, 'UTF-8') . '</text>';
    $svg .= '</svg>';

    return $svg;
}

/**
 * @param array $panel
 * @return void
 */
function vs_edgeone_render_top_rank_panel(array $panel)
{
    echo '<div class="vs-panel vs-edgeone-top-panel">';
    echo '<h3 class="vs-panel__title vs-edgeone-top-panel__title">' . vs_e($panel['title']) . '</h3>';
    if (!empty($panel['error'])) {
        echo '<p class="vs-form-tip">查询失败：' . vs_e($panel['error']) . '</p>';
        echo '</div>';
        return;
    }
    if (empty($panel['rows'])) {
        echo '<p class="vs-form-tip">该条件下暂无数据</p>';
        echo '</div>';
        return;
    }

    $unit = isset($panel['unit']) ? (string) $panel['unit'] : 'bytes';
    $max = 0.0;
    foreach ($panel['rows'] as $row) {
        $max = max($max, (float) $row['value']);
    }

    echo '<ul class="vs-edgeone-rank-list">';
    foreach ($panel['rows'] as $row) {
        $pct = $max > 0 ? round(((float) $row['value'] / $max) * 100, 1) : 0;
        $label = vs_edgeone_format_top_label($row['key']);
        $val = vs_edgeone_format_metric_value($row['value'], $unit);
        echo '<li class="vs-edgeone-rank-list__item">';
        echo '<div class="vs-edgeone-rank-list__meta">';
        echo '<span class="vs-edgeone-rank-list__name" title="' . vs_e($label) . '">' . vs_e($label) . '</span>';
        echo '<span class="vs-edgeone-rank-list__value">' . vs_e($val) . '</span>';
        echo '</div>';
        echo '<div class="vs-edgeone-rank-list__bar"><span style="width:' . (float) $pct . '%"></span></div>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

/**
 * @param string $key
 * @return string
 */
function vs_edgeone_format_top_label($key)
{
    $key = trim((string) $key);
    if ($key === '') {
        return '-';
    }

    static $countries = array(
        'CN' => '中国大陆', 'US' => '美国', 'HK' => '中国香港', 'TW' => '中国台湾',
        'JP' => '日本', 'SG' => '新加坡', 'KR' => '韩国', 'DE' => '德国', 'GB' => '英国',
    );

    if (isset($countries[$key])) {
        return $countries[$key] . ' (' . $key . ')';
    }

    return $key;
}

/**
 * @param array $dashboard
 * @return string
 */
function vs_edgeone_render_overview_dashboard(array $dashboard)
{
    ob_start();
    echo '<div class="vs-edgeone-overview" id="edgeoneDashboardHost">';
    echo vs_edgeone_render_overview_summary_sidebar(isset($dashboard['summary']) ? $dashboard['summary'] : array());

    echo '<div class="vs-edgeone-overview__main">';

    $flux = isset($dashboard['flux_chart']) ? $dashboard['flux_chart'] : array();
    echo '<div class="vs-panel vs-edgeone-chart-panel vs-edgeone-overview-chart">';
    echo '<h3 class="vs-panel__title">L7 访问流量</h3>';
    if (!empty($flux['error'])) {
        echo '<p class="vs-form-tip">查询失败：' . vs_e($flux['error']) . '</p>';
    } elseif (empty($flux['series'])) {
        echo '<p class="vs-form-tip">该条件下暂无数据</p>';
    } else {
        if (isset($flux['sum']) && $flux['sum'] !== null) {
            echo '<p class="vs-edgeone-metric-avg">区间合计：<strong>' . vs_e(vs_edgeone_format_metric_value($flux['sum'], 'bytes')) . '</strong></p>';
        }
        vs_edgeone_render_multi_line_chart(array(
            'unit'   => 'bytes',
            'series' => $flux['series'],
        ));
    }
    echo '</div>';

    $country = isset($dashboard['country_top']) ? $dashboard['country_top'] : array();
    echo '<div class="vs-panel vs-edgeone-country-panel">';
    echo '<h3 class="vs-panel__title">' . vs_e(isset($country['title']) ? $country['title'] : '访问区域分布') . '</h3>';
    if (!empty($country['error'])) {
        echo '<p class="vs-form-tip">查询失败：' . vs_e($country['error']) . '</p>';
    } elseif (empty($country['rows'])) {
        echo '<p class="vs-form-tip">该条件下暂无数据</p>';
    } else {
        $total = 0.0;
        foreach ($country['rows'] as $row) {
            $total += (float) $row['value'];
        }
        echo '<div class="vs-edgeone-country-layout">';
        echo '<div class="vs-edgeone-country-map">' . vs_edgeone_render_country_donut_svg($country['rows'], $total) . '</div>';
        echo '<div class="vs-edgeone-country-list">';
        foreach ($country['rows'] as $row) {
            $pct = $total > 0 ? round(((float) $row['value'] / $total) * 100, 2) : 0;
            $label = vs_edgeone_format_top_label($row['key']);
            echo '<div class="vs-edgeone-country-list__item">';
            echo '<span class="vs-edgeone-country-list__name">' . vs_e($label) . '</span>';
            echo '<span class="vs-edgeone-country-list__value">' . vs_e(vs_edgeone_format_metric_value($row['value'], 'bytes')) . ' (' . vs_e(number_format($pct, 2)) . '%)</span>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';

    echo '<div class="vs-edgeone-top-grid">';
    $panels = isset($dashboard['top_panels']) && is_array($dashboard['top_panels']) ? $dashboard['top_panels'] : array();
    foreach ($panels as $panel) {
        vs_edgeone_render_top_rank_panel($panel);
    }
    echo '</div>';

    echo '</div>';
    echo '</div>';

    return ob_get_clean();
}

/**
 * @param array<string, array{meta: array, series: array, sum: float|null, error: string}> $charts
 * @return string
 */
function vs_edgeone_render_overview_summary_grid(array $charts)
{
    $items = vs_edgeone_overview_summary_from_charts($charts);

    ob_start();
    echo '<div class="vs-edgeone-kpi-grid vs-edgeone-kpi-grid--overview" id="edgeoneSummaryHost">';
    foreach ($items as $item) {
        echo '<article class="vs-edgeone-kpi">';
        echo '<span class="vs-edgeone-kpi__label">' . vs_e($item['label']) . '</span>';
        echo '<strong class="vs-edgeone-kpi__value">' . vs_e($item['value']) . '</strong>';
        echo '</article>';
    }
    echo '</div>';

    return ob_get_clean();
}

/**
 * @param array<string, array{meta: array, series: array, sum: float|null, error: string}> $charts
 * @return string
 */
function vs_edgeone_render_overview_charts_grid(array $charts)
{
    ob_start();
    echo '<div class="vs-edgeone-chart-grid" id="edgeoneChartsHost">';
    foreach ($charts as $metric => $chart) {
        $meta = $chart['meta'];
        $hasData = false;
        foreach ($chart['series'] as $serie) {
            if (!empty($serie['points'])) {
                $hasData = true;
                break;
            }
        }
        echo '<div class="vs-panel vs-edgeone-chart-panel">';
        echo '<h3 class="vs-panel__title">' . vs_e($meta['label']) . '</h3>';
        if (!empty($chart['error'])) {
            echo '<p class="vs-form-tip">查询失败：' . vs_e($chart['error']) . '</p>';
        } elseif (!$hasData) {
            echo '<p class="vs-form-tip">该条件下暂无数据</p>';
        } else {
            if ($chart['sum'] !== null) {
                echo '<p class="vs-edgeone-metric-avg">区间合计：<strong>' . vs_e(vs_edgeone_format_metric_value($chart['sum'], $meta['unit'])) . '</strong></p>';
            }
            vs_edgeone_render_multi_line_chart(array(
                'unit'   => $meta['unit'],
                'series' => $chart['series'],
            ));
        }
        echo '</div>';
    }
    echo '</div>';

    return ob_get_clean();
}

/**
 * @param array<int, array<string, mixed>> $zones
 * @param array $quotaBundle
 * @return string
 */
function vs_edgeone_render_overview_quota_block(array $zones, array $quotaBundle)
{
    ob_start();
    echo '<div id="edgeoneQuotaHost">';
    vs_edgeone_render_overview_quota_sections(
        $zones,
        isset($quotaBundle['plans']) ? $quotaBundle['plans'] : array(),
        isset($quotaBundle['usage']) ? $quotaBundle['usage'] : array(),
        isset($quotaBundle['content_quota']) ? $quotaBundle['content_quota'] : array()
    );
    echo '</div>';

    return ob_get_clean();
}

/**
 * @param array{title?: string, unit?: string, series: array<int, array{label: string, points: array<int, array{ts: int, value: float}>, is_total?: bool}>} $cfg
 * @return void
 */
function vs_edgeone_render_multi_line_chart(array $cfg)
{
    $series = isset($cfg['series']) && is_array($cfg['series']) ? $cfg['series'] : array();
    $unit = isset($cfg['unit']) ? (string) $cfg['unit'] : 'number';
    $hasPoints = false;
    foreach ($series as $item) {
        if (!empty($item['points']) && is_array($item['points']) && count($item['points']) > 0) {
            $hasPoints = true;
            break;
        }
    }
    if (!$hasPoints) {
        return;
    }

    $chartId = 'eo-mchart-' . substr(md5(uniqid('', true)), 0, 10);
    echo '<div class="vs-edgeone-chart-wrap">';
    echo '<canvas id="' . vs_e($chartId) . '" class="vs-edgeone-chart vs-edgeone-chart--multi" height="260"></canvas>';
    echo '<script type="application/json" class="vs-edgeone-multi-chart-data" data-target="' . vs_e($chartId) . '" data-unit="' . vs_e($unit) . '">';
    echo json_encode($series, JSON_UNESCAPED_UNICODE);
    echo '</script>';
    echo '</div>';
}

/**
 * @param array<int, array<string, mixed>> $plans
 * @param string                          $selectedZoneId
 * @param array<string, array{label: string, value: float, unit: string}> $usage
 * @return void
 */
function vs_edgeone_render_package_quota_dashboard(array $plans, $selectedZoneId, array $usage = array())
{
    $matched = array();
    foreach ($plans as $plan) {
        if (!is_array($plan)) {
            continue;
        }
        $zones = isset($plan['ZonesInfo']) && is_array($plan['ZonesInfo']) ? $plan['ZonesInfo'] : array();
        foreach ($zones as $z) {
            if (is_array($z) && isset($z['ZoneId']) && (string) $z['ZoneId'] === (string) $selectedZoneId) {
                $matched[] = $plan;
                break;
            }
        }
    }

    if (count($matched) === 0) {
        echo '<p class="vs-form-tip">该站点未绑定套餐或暂无配额信息</p>';
        return;
    }

    echo '<div class="vs-edgeone-quota-grid">';
    foreach ($matched as $plan) {
        $planType = isset($plan['PlanType']) ? (string) $plan['PlanType'] : '';
        $items = array(
            array(
                'title'    => '加速流量配额',
                'capacity' => isset($plan['AccTrafficCapacity']) ? (float) $plan['AccTrafficCapacity'] : 0,
                'used'     => isset($usage['acc_flux']['value']) ? (float) $usage['acc_flux']['value'] : 0,
                'unit'     => 'bytes',
                'usageKey' => 'acc_flux',
            ),
            array(
                'title'    => '安全请求配额',
                'capacity' => isset($plan['SecRequestCapacity']) ? (float) $plan['SecRequestCapacity'] : 0,
                'used'     => isset($usage['sec_request']['value']) ? (float) $usage['sec_request']['value'] : 0,
                'unit'     => 'count',
                'usageKey' => 'sec_request',
            ),
            array(
                'title'    => '四层流量配额',
                'capacity' => isset($plan['L4TrafficCapacity']) ? (float) $plan['L4TrafficCapacity'] : 0,
                'used'     => 0,
                'unit'     => 'bytes',
                'usageKey' => '',
            ),
        );

        foreach ($items as $item) {
            if ($item['capacity'] <= 0) {
                continue;
            }
            $cap = $item['capacity'];
            $used = min($cap, max(0, $item['used']));
            $pct = $cap > 0 ? min(100, round($used / $cap * 100)) : 0;
            $capLabel = $item['unit'] === 'bytes'
                ? vs_edgeone_format_bytes($cap)
                : vs_edgeone_format_number($cap);
            $usedLabel = $item['unit'] === 'bytes'
                ? vs_edgeone_format_bytes($used)
                : vs_edgeone_format_number($used);

            echo '<article class="vs-edgeone-quota-card">';
            echo '<h5>' . vs_e($item['title']) . '</h5>';
            if ($planType !== '') {
                echo '<p class="vs-form-tip">' . vs_e(vs_edgeone_plan_type_label($planType)) . ' 套餐</p>';
            }
            echo '<div class="vs-edgeone-quota-meta">';
            echo '<span>套餐配额 <strong>' . vs_e($capLabel) . '</strong></span>';
            if ($item['usageKey'] !== '' && isset($usage[$item['usageKey']])) {
                echo '<span>今日已用 <strong>' . vs_e($usedLabel) . '</strong></span>';
            }
            echo '</div>';
            if ($item['usageKey'] !== '' && isset($usage[$item['usageKey']])) {
                echo '<div class="vs-edgeone-progress" title="已用 ' . vs_e($usedLabel) . ' / ' . vs_e($capLabel) . '">';
                echo '<div class="vs-edgeone-progress__bar" style="width:' . (int) $pct . '%"></div>';
                echo '</div>';
                echo '<p class="vs-edgeone-quota-foot">今日用量 ' . (int) $pct . '%（套餐周期配额以腾讯云账单为准）</p>';
            } else {
                echo '<p class="vs-edgeone-quota-foot">套餐周期总量</p>';
            }
            echo '</article>';
        }
    }
    echo '</div>';
}

/**
 * @param array<int, array<string, mixed>> $zones
 * @param array<int, array<string, mixed>> $plans
 * @param array<string, array<string, array{label: string, value: float, unit: string}>> $usageByZone
 * @param array<string, array{ok: bool, data: mixed, error: string}> $contentQuotaByZone
 * @return void
 */
function vs_edgeone_render_overview_quota_sections(array $zones, array $plans, array $usageByZone, array $contentQuotaByZone)
{
    if (count($zones) === 0) {
        echo '<p class="vs-form-tip">暂无站点配额数据</p>';
        return;
    }

    foreach ($zones as $zone) {
        if (!is_array($zone)) {
            continue;
        }
        $zid = isset($zone['ZoneId']) ? (string) $zone['ZoneId'] : '';
        if ($zid === '') {
            continue;
        }

        echo '<div class="vs-edgeone-quota-zone-block">';
        echo '<h4 class="vs-edgeone-subtitle">' . vs_e(vs_edgeone_zone_display_name($zone)) . '</h4>';

        $usage = isset($usageByZone[$zid]) && is_array($usageByZone[$zid]) ? $usageByZone[$zid] : array();
        vs_edgeone_render_package_quota_dashboard($plans, $zid, $usage);

        echo '<h5 class="vs-edgeone-subtitle">内容刷新 / 预热配额</h5>';
        $quotaResult = isset($contentQuotaByZone[$zid]) ? $contentQuotaByZone[$zid] : array('ok' => false, 'data' => null, 'error' => '');
        if (empty($quotaResult['ok'])) {
            echo '<p class="vs-form-tip">加载失败：' . vs_e(isset($quotaResult['error']) ? $quotaResult['error'] : '未知错误') . '</p>';
        } elseif (!is_array($quotaResult['data'])) {
            echo '<p class="vs-form-tip">暂无配额数据</p>';
        } else {
            vs_edgeone_render_quota($quotaResult['data']);
        }
        echo '</div>';
    }
}

/**
 * @param array<int, array<string, mixed>> $plans
 * @param string                          $selectedZoneId
 * @return void
 */
function vs_edgeone_render_plans_dashboard(array $plans, $selectedZoneId = '')
{
    echo '<div class="vs-edgeone-plan-grid">';
    foreach ($plans as $plan) {
        if (!is_array($plan)) {
            continue;
        }
        $planId = isset($plan['PlanId']) ? (string) $plan['PlanId'] : '';
        $planType = isset($plan['PlanType']) ? (string) $plan['PlanType'] : '';
        $status = isset($plan['Status']) ? (string) $plan['Status'] : '';
        $expired = isset($plan['ExpiredTime']) ? (string) $plan['ExpiredTime'] : '';
        $enabled = isset($plan['EnabledTime']) ? (string) $plan['EnabledTime'] : '';
        $area = isset($plan['Area']) ? vs_edgeone_translate('Area', $plan['Area']) : '-';
        $zones = isset($plan['ZonesInfo']) && is_array($plan['ZonesInfo']) ? $plan['ZonesInfo'] : array();
        $isCurrent = false;
        foreach ($zones as $z) {
            if (is_array($z) && isset($z['ZoneId']) && (string) $z['ZoneId'] === (string) $selectedZoneId) {
                $isCurrent = true;
                break;
            }
        }
        $cardClass = 'vs-edgeone-plan-card' . ($isCurrent ? ' is-current' : '');

        echo '<article class="' . vs_e($cardClass) . '">';
        if ($isCurrent) {
            echo '<span class="vs-edgeone-stat-card__tag">当前站点套餐</span>';
        }
        echo '<h4>' . vs_e(vs_edgeone_plan_type_label($planType)) . '</h4>';
        echo '<p class="vs-edgeone-plan-id">套餐编号：<code>' . vs_e($planId) . '</code></p>';
        echo '<dl class="vs-edgeone-dl">';
        echo '<dt>状态</dt><dd>';
        vs_edgeone_render_status_badge('Status', $status === 'normal' ? 'active' : $status);
        echo ' ' . vs_e(vs_edgeone_plan_status_label($status)) . '</dd>';
        echo '<dt>生效时间</dt><dd>' . vs_e(vs_edgeone_format_timestamp($enabled)) . '</dd>';
        echo '<dt>到期时间</dt><dd>' . vs_e(vs_edgeone_format_timestamp($expired)) . '</dd>';
        echo '<dt>服务区域</dt><dd>' . vs_e($area) . '</dd>';
        echo '<dt>加速流量配额</dt><dd>' . vs_e(vs_edgeone_format_bytes(isset($plan['AccTrafficCapacity']) ? $plan['AccTrafficCapacity'] : 0)) . '</dd>';
        echo '<dt>安全请求配额</dt><dd>' . vs_e(vs_edgeone_format_number(isset($plan['SecRequestCapacity']) ? $plan['SecRequestCapacity'] : 0)) . '</dd>';
        echo '<dt>四层流量配额</dt><dd>' . vs_e(vs_edgeone_format_bytes(isset($plan['L4TrafficCapacity']) ? $plan['L4TrafficCapacity'] : 0)) . '</dd>';
        echo '</dl>';

        if (count($zones) > 0) {
            echo '<div class="vs-edgeone-plan-zones">';
            echo '<p class="vs-edgeone-subtitle">绑定站点</p>';
            echo '<ul class="vs-edgeone-zone-list">';
            foreach ($zones as $z) {
                if (!is_array($z)) {
                    continue;
                }
                $zid = isset($z['ZoneId']) ? (string) $z['ZoneId'] : '';
                $zname = isset($z['ZoneName']) ? (string) $z['ZoneName'] : $zid;
                $paused = !empty($z['Paused']);
                echo '<li>';
                echo vs_e($zname);
                if ($zid !== '') {
                    echo ' <code>' . vs_e($zid) . '</code>';
                }
                if ($paused) {
                    echo ' <span class="vs-edgeone-badge is-warning">已暂停</span>';
                }
                echo '</li>';
            }
            echo '</ul></div>';
        }
        echo '</article>';
    }
    echo '</div>';
}

/**
 * @param string $text
 * @return void
 */
function vs_edgeone_render_note($text)
{
    echo '<p class="vs-form-tip vs-form-tip--highlight">' . vs_e($text) . '</p>';
}

/**
 * @param array<int, array<string, mixed>> $rows
 * @param array<int, string>|null          $columns
 * @param array<string, mixed>             $ctx
 * @return void
 */
function vs_edgeone_render_data_table(array $rows, $columns = null, array $ctx = array())
{
    if (count($rows) === 0) {
        return;
    }

    if ($columns === null) {
        $columns = vs_edgeone_infer_columns($rows);
    }

    echo '<div class="vs-table-wrap vs-edgeone-table-wrap">';
    echo '<table class="vs-table vs-edgeone-data-table">';
    echo '<thead><tr>';
    foreach ($columns as $col) {
        echo '<th>' . vs_e(vs_edgeone_field_label($col)) . '</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        echo '<tr>';
        foreach ($columns as $col) {
            $val = isset($row[$col]) ? $row[$col] : null;
            echo '<td>';
            if (in_array($col, array('Status', 'ActiveStatus', 'CnameStatus', 'DomainStatus', 'CertStatus', 'DnsVerificationStatus', 'FileVerificationStatus', 'AttackStatus'), true)) {
                vs_edgeone_render_status_badge($col === 'DnsVerificationStatus' || $col === 'FileVerificationStatus' ? 'VerificationStatus' : $col, $val);
            } else {
                echo vs_e(vs_edgeone_format_cell($col, $val, $ctx));
            }
            echo '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

/**
 * @param array<int, array<string, mixed>> $rows
 * @return array<int, string>
 */
function vs_edgeone_infer_columns(array $rows)
{
    $priority = array(
        'AliasZoneName', 'ZoneName', 'DomainName', 'RecordName', 'Cname', 'Type', 'Status',
        'JobId', 'Target', 'CreateTime', 'Subdomain', 'DnsVerificationStatus', 'FileVerificationStatus',
        'Metric', 'Timestamp', 'Value', 'Avg', 'FunctionName', 'CertId', 'ProxyId', 'InstanceId',
        'AttackType', 'StartTime', 'EndTime', 'ZoneId',
    );
    $keys = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        foreach (array_keys($row) as $k) {
            $keys[$k] = true;
        }
    }
    $all = array_keys($keys);
    $ordered = array();
    foreach ($priority as $p) {
        if (isset($keys[$p])) {
            $ordered[] = $p;
        }
    }
    foreach ($all as $k) {
        if (!in_array($k, $ordered, true) && !in_array($k, array('RequestId', 'FileAscription'), true)) {
            $ordered[] = $k;
        }
    }

    return count($ordered) > 0 ? $ordered : $all;
}

/**
 * @param array<string, mixed> $data
 * @return void
 */
function vs_edgeone_render_quota($data)
{
    $sections = array();
    if (isset($data['PurgeQuota']) && is_array($data['PurgeQuota'])) {
        $sections['刷新配额'] = $data['PurgeQuota'];
    }
    if (isset($data['PrefetchQuota']) && is_array($data['PrefetchQuota'])) {
        $sections['预热配额'] = $data['PrefetchQuota'];
    }

    foreach ($sections as $title => $items) {
        if (count($items) === 0) {
            continue;
        }
        echo '<h4 class="vs-edgeone-subtitle">' . vs_e($title) . '</h4>';
        echo '<div class="vs-edgeone-quota-grid">';
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $type = isset($item['Type']) ? (string) $item['Type'] : '';
            $daily = isset($item['Daily']) ? (int) $item['Daily'] : 0;
            $avail = isset($item['DailyAvailable']) ? (int) $item['DailyAvailable'] : 0;
            $batch = isset($item['Batch']) ? (int) $item['Batch'] : 0;
            $used = max(0, $daily - $avail);
            $pct = $daily > 0 ? min(100, round($used / $daily * 100)) : 0;

            echo '<article class="vs-edgeone-quota-card">';
            echo '<h5>' . vs_e(vs_edgeone_quota_type_label($type)) . '</h5>';
            echo '<div class="vs-edgeone-quota-meta">';
            echo '<span>单次上限 <strong>' . vs_e(vs_edgeone_format_number($batch)) . '</strong></span>';
            echo '<span>日配额 <strong>' . vs_e(vs_edgeone_format_number($daily)) . '</strong></span>';
            echo '<span>今日剩余 <strong>' . vs_e(vs_edgeone_format_number($avail)) . '</strong></span>';
            echo '</div>';
            echo '<div class="vs-edgeone-progress" title="已用 ' . vs_e((string) $used) . ' / ' . vs_e((string) $daily) . '">';
            echo '<div class="vs-edgeone-progress__bar" style="width:' . (int) $pct . '%"></div>';
            echo '</div>';
            echo '<p class="vs-edgeone-quota-foot">已用 ' . vs_e(vs_edgeone_format_number($used)) . '（' . (int) $pct . '%）</p>';
            echo '</article>';
        }
        echo '</div>';
    }
}

/**
 * @param array<int, array<string, mixed>> $dataItems
 * @return void
 */
function vs_edgeone_render_timing_data(array $dataItems)
{
    foreach ($dataItems as $block) {
        if (!is_array($block)) {
            continue;
        }
        $type = isset($block['Type']) ? (string) $block['Type'] : '';
        $metric = $type;
        $title = vs_edgeone_metric_label($type);
        echo '<h4 class="vs-edgeone-subtitle">' . vs_e($title) . '</h4>';

        $series = isset($block['TypeValue']) && is_array($block['TypeValue']) ? $block['TypeValue'] : array();
        if (count($series) === 0 && isset($block['Detail'])) {
            $series = array($block);
        }

        foreach ($series as $serie) {
            if (!is_array($serie)) {
                continue;
            }
            if (isset($serie['Metric']) && $serie['Metric'] !== '') {
                $metric = (string) $serie['Metric'];
            }
            $ctx = array('metric' => $metric);
            if (isset($serie['Avg'])) {
                echo '<p class="vs-edgeone-metric-avg">周期平均：<strong>' . vs_e(vs_edgeone_format_cell('Avg', $serie['Avg'], $ctx)) . '</strong></p>';
            }
            $details = isset($serie['Detail']) && is_array($serie['Detail']) ? $serie['Detail'] : array();
            if (count($details) > 0) {
                vs_edgeone_render_data_table($details, array('Timestamp', 'Value'), $ctx);
            }
        }
    }
}

/**
 * @param array<int, array<string, mixed>> $items
 * @return void
 */
function vs_edgeone_render_identifications(array $items)
{
    echo '<div class="vs-edgeone-id-grid">';
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        echo '<article class="vs-edgeone-id-card">';
        echo '<h5>' . vs_e(isset($item['Subdomain']) ? (string) $item['Subdomain'] : '站点验证') . '</h5>';
        echo '<dl class="vs-edgeone-dl">';
        $pairs = array(
            'DnsVerificationStatus'  => isset($item['DnsVerificationStatus']) ? $item['DnsVerificationStatus'] : null,
            'FileVerificationStatus' => isset($item['FileVerificationStatus']) ? $item['FileVerificationStatus'] : null,
        );
        foreach ($pairs as $k => $v) {
            echo '<dt>' . vs_e(vs_edgeone_field_label($k)) . '</dt><dd>';
            vs_edgeone_render_status_badge('VerificationStatus', $v);
            echo '</dd>';
        }
        if (isset($item['FileAscription']) && is_array($item['FileAscription'])) {
            $fa = $item['FileAscription'];
            if (isset($fa['IdentifyPath'])) {
                echo '<dt>' . vs_e(vs_edgeone_field_label('IdentifyPath')) . '</dt><dd><code class="vs-edgeone-code">' . vs_e((string) $fa['IdentifyPath']) . '</code></dd>';
            }
            if (isset($fa['IdentifyContent'])) {
                echo '<dt>' . vs_e(vs_edgeone_field_label('IdentifyContent')) . '</dt><dd><code class="vs-edgeone-code vs-edgeone-code--break">' . vs_e((string) $fa['IdentifyContent']) . '</code></dd>';
            }
        }
        if (isset($item['OriginalNameServers']) && is_array($item['OriginalNameServers']) && count($item['OriginalNameServers']) > 0) {
            echo '<dt>' . vs_e(vs_edgeone_field_label('OriginalNameServers')) . '</dt><dd>' . vs_e(implode('、', $item['OriginalNameServers'])) . '</dd>';
        }
        echo '</dl></article>';
    }
    echo '</div>';
}

/**
 * @param array<string, mixed> $obj
 * @return void
 */
function vs_edgeone_render_kv_grid(array $obj)
{
    $skip = array('RequestId');
    echo '<dl class="vs-edgeone-kv-grid">';
    foreach ($obj as $key => $val) {
        if (in_array($key, $skip, true)) {
            continue;
        }
        if (is_array($val)) {
            continue;
        }
        echo '<dt>' . vs_e(vs_edgeone_field_label($key)) . '</dt>';
        echo '<dd>' . vs_e(vs_edgeone_format_cell($key, $val)) . '</dd>';
    }
    echo '</dl>';
}

/**
 * @return array<int, string>
 */
function vs_edgeone_list_keys()
{
    return array(
        'Identifications', 'CnameStatus', 'AccelerationDomains', 'DnsRecords', 'AliasDomains',
        'Functions', 'FunctionRules', 'Tasks', 'PurgeLogs', 'PrefetchLogs', 'SecurityTemplates',
        'Certificates', 'L4Proxies', 'LoadBalancers', 'AttackEvents', 'DDoSAttackEvents',
        'ConfigGroups', 'Versions', 'LogTopics', 'OriginProtectionResources', 'BillingData',
        'TopData', 'RealtimeLogDeliveryTasks', 'Zones', 'Records', 'Rules', 'Entities',
    );
}

/**
 * @param mixed $data
 * @return void
 */
function vs_edgeone_render_api_data($data)
{
    if ($data === null || $data === '') {
        echo '<p class="vs-form-tip">暂无数据</p>';
        return;
    }

    if (!is_array($data)) {
        echo '<p class="vs-form-tip">' . vs_e((string) $data) . '</p>';
        return;
    }

    if (vs_edgeone_array_is_list($data)) {
        vs_edgeone_render_data_table($data);
        return;
    }

    if (isset($data['Note']) && is_string($data['Note']) && $data['Note'] !== '') {
        vs_edgeone_render_note($data['Note']);
    }

    $rendered = false;

    if ((isset($data['PurgeQuota']) && is_array($data['PurgeQuota'])) || (isset($data['PrefetchQuota']) && is_array($data['PrefetchQuota']))) {
        vs_edgeone_render_quota($data);
        $rendered = true;
    }

    if (isset($data['Plans']) && is_array($data['Plans'])) {
        if (count($data['Plans']) === 0) {
            echo '<p class="vs-form-tip">暂无套餐</p>';
        } else {
            vs_edgeone_render_plans_dashboard($data['Plans'], vs_edgeone_selected_zone());
        }
        $rendered = true;
    }

    if (isset($data['Data']) && is_array($data['Data']) && count($data['Data']) > 0) {
        vs_edgeone_render_timing_data($data['Data']);
        $rendered = true;
    }

    if (isset($data['Identifications']) && is_array($data['Identifications'])) {
        if (count($data['Identifications']) === 0) {
            echo '<p class="vs-form-tip">暂无验证信息</p>';
        } else {
            vs_edgeone_render_identifications($data['Identifications']);
        }
        $rendered = true;
    }

    if (isset($data['CnameStatus']) && is_array($data['CnameStatus'])) {
        if (count($data['CnameStatus']) === 0) {
            echo '<p class="vs-form-tip">暂无 CNAME 记录</p>';
        } else {
            vs_edgeone_render_data_table($data['CnameStatus'], array('RecordName', 'Cname', 'Status'));
        }
        $rendered = true;
    }

    foreach (vs_edgeone_list_keys() as $listKey) {
        if (in_array($listKey, array('Identifications', 'CnameStatus', 'Data'), true)) {
            continue;
        }
        if (!isset($data[$listKey]) || !is_array($data[$listKey])) {
            continue;
        }
        if (count($data[$listKey]) === 0) {
            continue;
        }
        if (!vs_edgeone_array_is_list($data[$listKey])) {
            continue;
        }
        if (!is_array($data[$listKey][0])) {
            continue;
        }
        echo '<h4 class="vs-edgeone-subtitle">' . vs_e(vs_edgeone_field_label($listKey)) . '</h4>';
        vs_edgeone_render_data_table($data[$listKey]);
        $rendered = true;
    }

    if (!$rendered) {
        $scalarCount = 0;
        foreach ($data as $key => $val) {
            if (in_array($key, array('RequestId', 'Note'), true)) {
                continue;
            }
            if (!is_array($val)) {
                $scalarCount++;
            }
        }
        if ($scalarCount > 0) {
            vs_edgeone_render_kv_grid($data);
            $rendered = true;
        } else {
            foreach ($data as $key => $val) {
                if (in_array($key, array('RequestId', 'Note', 'PurgeQuota', 'PrefetchQuota', 'Data', 'Identifications', 'CnameStatus'), true)) {
                    continue;
                }
                if (is_array($val) && count($val) > 0) {
                    echo '<h4 class="vs-edgeone-subtitle">' . vs_e(vs_edgeone_field_label($key)) . '</h4>';
                    if (vs_edgeone_array_is_list($val) && isset($val[0]) && is_array($val[0])) {
                        vs_edgeone_render_data_table($val);
                    } else {
                        vs_edgeone_render_kv_grid($val);
                    }
                    $rendered = true;
                }
            }
        }
    }

    if (!$rendered && (!isset($data['Note']) || $data['Note'] === '')) {
        echo '<p class="vs-form-tip">暂无结构化数据</p>';
    }
}
