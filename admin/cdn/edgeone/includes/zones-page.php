<?php
/**
 * 文件：admin/cdn/edgeone/includes/zones-page.php
 * 作用：站点管理页数据与渲染（对标腾讯云站点列表）
 */

/**
 * @return array<int, string>
 */
function vs_edgeone_zones_overview_kpi_ids()
{
    return array(
        'l7Flow_flux',
        'l7Flow_totalBandwidth',
        'l7Flow_request',
        'l7Flow_mitigatedRequest',
    );
}

/**
 * @return array<string, array{label: string, metric: string, unit: string}>
 */
function vs_edgeone_zones_chart_tabs()
{
    return array(
        'flux'      => array('label' => '流量', 'metric' => 'l7Flow_flux', 'unit' => 'bytes'),
        'bandwidth' => array('label' => '带宽', 'metric' => 'l7Flow_totalBandwidth', 'unit' => 'bps'),
        'request'   => array('label' => '请求数', 'metric' => 'l7Flow_request', 'unit' => 'count'),
        'mitigated' => array('label' => '防护命中次数', 'metric' => 'l7Flow_mitigatedRequest', 'unit' => 'count'),
        'hitrate'   => array('label' => '缓存命中率', 'metric' => 'l7Flow_hitRate', 'unit' => 'percent'),
    );
}

/**
 * @param array<int, array<string, mixed>> $plans
 * @return array<int, array<string, mixed>>
 */
function vs_edgeone_plans_available_for_create(array $plans)
{
    $out = array();
    foreach ($plans as $plan) {
        if (!is_array($plan)) {
            continue;
        }
        $status = isset($plan['Status']) ? (string) $plan['Status'] : '';
        if ($status !== '' && $status !== 'normal') {
            continue;
        }
        $zones = isset($plan['ZonesInfo']) && is_array($plan['ZonesInfo']) ? $plan['ZonesInfo'] : array();
        if (count($zones) > 0) {
            continue;
        }
        $out[] = $plan;
    }

    return $out;
}

/**
 * @param array<int, array<string, mixed>> $plans
 * @return array<string, array<string, mixed>>
 */
function vs_edgeone_zone_plan_map(array $plans)
{
    $map = array();
    foreach ($plans as $plan) {
        if (!is_array($plan)) {
            continue;
        }
        $zones = isset($plan['ZonesInfo']) && is_array($plan['ZonesInfo']) ? $plan['ZonesInfo'] : array();
        foreach ($zones as $z) {
            if (!is_array($z) || empty($z['ZoneId'])) {
                continue;
            }
            $map[(string) $z['ZoneId']] = $plan;
        }
    }

    return $map;
}

/**
 * @param EdgeOne $eo
 * @param array<int, array<string, mixed>> $zones
 * @param string $rangeKey
 * @return array{kpi: array, charts: array, chart_tab: string}
 */
function vs_edgeone_fetch_zones_page_metrics(EdgeOne $eo, array $zones, $rangeKey)
{
    $zoneIds = array();
    foreach ($zones as $zone) {
        if (!empty($zone['ZoneId'])) {
            $zoneIds[] = (string) $zone['ZoneId'];
        }
    }
    if (count($zoneIds) === 0) {
        return array(
            'kpi'       => array(),
            'charts'    => array(),
            'chart_tab' => 'flux',
        );
    }

    $charts = vs_edgeone_fetch_overview_kpi_charts($eo, $zoneIds, $rangeKey, '', array());
    $kpiIds = vs_edgeone_zones_overview_kpi_ids();
    $labelMap = array(
        'l7Flow_flux'             => '总流量',
        'l7Flow_totalBandwidth'   => '总带宽峰值',
        'l7Flow_request'          => '总请求数',
        'l7Flow_mitigatedRequest' => '防护命中次数',
    );
    $allItems = vs_edgeone_overview_kpi_items_from_charts($charts, false);
    $byId = array();
    foreach ($allItems as $item) {
        if (isset($item['id'])) {
            $byId[$item['id']] = $item;
        }
    }
    $kpi = array();
    foreach ($kpiIds as $id) {
        if (!isset($byId[$id])) {
            continue;
        }
        $item = $byId[$id];
        if (isset($labelMap[$id])) {
            $item['label'] = $labelMap[$id];
        }
        $kpi[] = $item;
    }

    return array(
        'kpi'       => $kpi,
        'charts'    => $charts,
        'chart_tab' => 'flux',
    );
}

/**
 * @param array $metrics
 * @param string $tabKey
 * @return array{unit: string, series: array}
 */
function vs_edgeone_zones_chart_for_tab(array $metrics, $tabKey)
{
    $tabs = vs_edgeone_zones_chart_tabs();
    if (!isset($tabs[$tabKey])) {
        $tabKey = 'flux';
    }
    $tab = $tabs[$tabKey];
    $metric = $tab['metric'];
    $charts = isset($metrics['charts']) ? $metrics['charts'] : array();
    $chart = isset($charts[$metric]) ? $charts[$metric] : null;
    if ($chart === null) {
        return array('unit' => $tab['unit'], 'series' => array());
    }
    $points = isset($chart['spark_points']) ? $chart['spark_points'] : array();
    if (empty($points) && isset($chart['series'][0]['points'])) {
        $points = $chart['series'][0]['points'];
    }
    $label = isset($chart['meta']['label']) ? (string) $chart['meta']['label'] : $tab['label'];

    return array(
        'unit'   => $tab['unit'],
        'series' => array(array('label' => $label, 'points' => $points, 'is_total' => true)),
    );
}

/**
 * @param array $metrics
 * @param string $activeTab
 * @return string
 */
function vs_edgeone_render_zones_overview_panel(array $metrics, $activeTab = 'flux')
{
    $kpi = isset($metrics['kpi']) ? $metrics['kpi'] : array();
    $chart = vs_edgeone_zones_chart_for_tab($metrics, $activeTab);
    $tabs = vs_edgeone_zones_chart_tabs();

    ob_start();
    echo '<div class="vs-panel vs-edgeone-zones-overview" id="edgeoneZonesOverview">';
    echo '<div class="vs-edgeone-zones-overview__head">';
    echo '<h3 class="vs-panel__title">站点数据概览</h3>';
    echo '<div class="vs-edgeone-zones-overview__head-actions">';
    echo '<div class="vs-edgeone-zones-overview__range">';
    echo '<select class="vs-input vs-input--sm" id="edgeoneZonesRange" name="range">';
    $ranges = vs_edgeone_analytics_ranges();
    $preferred = array('today', '6h', '1h', '7d', '30d');
    foreach ($preferred as $key) {
        if (!isset($ranges[$key])) {
            continue;
        }
        echo '<option value="' . vs_e($key) . '">' . vs_e($ranges[$key]['label']) . '</option>';
    }
    echo '</select>';
    echo '</div>';
    echo '<button type="button" class="vs-edgeone-zones-overview__collapse" data-zones-overview-collapse>收起数据概览</button>';
    echo '</div></div>';

    echo '<div class="vs-edgeone-zones-overview__body">';
    echo '<div class="vs-edgeone-zones-kpi-row">';
    foreach ($kpi as $item) {
        echo '<article class="vs-edgeone-kpi vs-edgeone-kpi--zones">';
        echo '<div class="vs-edgeone-kpi__top">';
        echo '<span class="vs-edgeone-kpi__label">' . vs_e($item['label']) . '</span>';
        if (!empty($item['hint'])) {
            echo '<span class="vs-edgeone-kpi__agg">' . vs_e($item['hint']) . '</span>';
        }
        echo '</div>';
        echo '<strong class="vs-edgeone-kpi__value">' . vs_e($item['value']) . '</strong>';
        echo vs_edgeone_render_sparkline_svg(isset($item['points']) ? $item['points'] : array(), 100, 28);
        echo '</article>';
    }
    echo '</div>';

    echo '<div class="vs-edgeone-zones-chart-wrap">';
    echo '<div class="vs-edgeone-zones-chart-tabs" role="tablist">';
    foreach ($tabs as $key => $tab) {
        $active = $key === $activeTab ? ' is-active' : '';
        echo '<button type="button" class="vs-edgeone-zones-chart-tabs__btn' . $active . '" data-zones-chart="' . vs_e($key) . '" role="tab">' . vs_e($tab['label']) . '</button>';
    }
    echo '</div>';
    if (empty($chart['series'][0]['points'])) {
        echo '<p class="vs-form-tip">该时间范围内暂无数据</p>';
    } else {
        vs_edgeone_render_multi_line_chart(array(
            'unit'   => $chart['unit'],
            'series' => $chart['series'],
        ));
    }
    echo '</div></div></div>';

    return ob_get_clean();
}

/**
 * @param array<int, array<string, mixed>> $zones
 * @param array<string, array<string, mixed>> $planMap
 * @param array<int, array<string, mixed>> $availablePlans
 * @return string
 */
function vs_edgeone_render_zones_table_panel(array $zones, array $planMap, array $availablePlans)
{
    $canCreate = count($availablePlans) > 0;

    ob_start();
    echo '<div class="vs-panel vs-edgeone-zones-list" id="edgeoneZonesListPanel">';
    echo '<div class="vs-edgeone-zones-toolbar">';
    if ($canCreate) {
        echo '<button type="button" class="vs-btn vs-btn--primary" id="edgeoneZoneCreateBtn">+ 新增站点</button>';
    } else {
        echo '<button type="button" class="vs-btn vs-btn--primary" disabled title="暂无未绑定站点的套餐">+ 新增站点</button>';
        echo '<span class="vs-form-tip vs-edgeone-zones-toolbar__tip">当前套餐均已绑定站点，无法新建</span>';
    }
    echo '<div class="vs-edgeone-zones-search">';
    echo '<input type="search" class="vs-input" id="edgeoneZonesSearch" placeholder="搜索站点名称、ZoneId" aria-label="搜索站点">';
    echo '</div></div>';

    if (count($zones) === 0) {
        echo '<p class="vs-form-tip">暂无站点，请先购买套餐并新增站点</p>';
    } else {
        echo '<div class="vs-edgeone-zones-cards" id="edgeoneZonesCards">';
        foreach ($zones as $zone) {
            if (!is_array($zone)) {
                continue;
            }
            $zid = isset($zone['ZoneId']) ? (string) $zone['ZoneId'] : '';
            $name = isset($zone['ZoneName']) ? (string) $zone['ZoneName'] : '';
            $alias = isset($zone['AliasZoneName']) ? trim((string) $zone['AliasZoneName']) : '';
            $displayName = $alias !== '' ? $alias : $name;
            $area = vs_edgeone_translate('Area', isset($zone['Area']) ? $zone['Area'] : '');
            $type = vs_edgeone_translate('Type', isset($zone['Type']) ? $zone['Type'] : '');
            $active = isset($zone['ActiveStatus']) ? (string) $zone['ActiveStatus'] : '';
            $plan = isset($planMap[$zid]) ? $planMap[$zid] : null;
            $planLabel = $plan ? vs_edgeone_plan_type_label(isset($plan['PlanType']) ? $plan['PlanType'] : '') : '—';
            $tags = isset($zone['Tags']) && is_array($zone['Tags']) ? $zone['Tags'] : array();
            $searchText = strtolower($displayName . ' ' . $name . ' ' . $zid);
            $statusHtml = $active === 'active'
                ? '<span class="vs-edgeone-zones-status is-ok"><svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M3 8l3 3 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>已启用</span>'
                : '<span class="vs-edgeone-zones-status">' . vs_e(vs_edgeone_translate('ActiveStatus', $active)) . '</span>';

            echo '<article class="vs-edgeone-zones-card" data-zone-search="' . vs_e($searchText) . '">';
            echo '<div class="vs-edgeone-zones-card__head">';
            echo '<span class="vs-edgeone-zones-card__title">' . vs_e($displayName) . '</span>';
            echo $statusHtml;
            echo '</div>';
            if ($name !== '' && $displayName !== $name) {
                echo '<p class="vs-edgeone-zones-card__sub">' . vs_e($name) . '</p>';
            }
            echo '<code class="vs-edgeone-zones-table__id">' . vs_e($zid) . '</code>';
            echo '<dl class="vs-edgeone-zones-card__meta">';
            echo '<div><dt>服务区域</dt><dd>' . vs_e($area) . '</dd></div>';
            echo '<div><dt>接入方式</dt><dd>' . vs_e($type) . '</dd></div>';
            echo '<div><dt>套餐</dt><dd>' . vs_e($planLabel) . '</dd></div>';
            echo '<div><dt>标签</dt><dd>';
            if (count($tags) > 0) {
                foreach ($tags as $tag) {
                    if (is_array($tag) && isset($tag['TagValue'])) {
                        echo '<span class="vs-edgeone-filter-chip">' . vs_e($tag['TagValue']) . '</span> ';
                    }
                }
            } else {
                echo '—';
            }
            echo '</dd></div></dl>';
            echo '<div class="vs-edgeone-zones-card__actions">';
            echo '<a href="#" data-set-zone="' . vs_e($zid) . '" data-goto="domains.php">域名加速</a>';
            echo '<a href="#" data-set-zone="' . vs_e($zid) . '" data-goto="l7.php">七层加速</a>';
            echo '<a href="#" data-set-zone="' . vs_e($zid) . '" data-goto="security.php">安全策略</a>';
            echo '</div></article>';
        }
        echo '</div>';

        echo '<div class="vs-table-wrap vs-edgeone-zones-table-wrap">';
        echo '<table class="vs-table vs-edgeone-zones-table" id="edgeoneZonesTable">';
        echo '<thead><tr>';
        echo '<th>站点 / ID</th><th>服务区域</th><th>接入方式</th><th>生效状态</th><th>套餐信息</th><th>标签</th><th>操作</th>';
        echo '</tr></thead><tbody>';
        foreach ($zones as $zone) {
            if (!is_array($zone)) {
                continue;
            }
            $zid = isset($zone['ZoneId']) ? (string) $zone['ZoneId'] : '';
            $name = isset($zone['ZoneName']) ? (string) $zone['ZoneName'] : '';
            $alias = isset($zone['AliasZoneName']) ? trim((string) $zone['AliasZoneName']) : '';
            $displayName = $alias !== '' ? $alias : $name;
            $area = vs_edgeone_translate('Area', isset($zone['Area']) ? $zone['Area'] : '');
            $type = vs_edgeone_translate('Type', isset($zone['Type']) ? $zone['Type'] : '');
            $active = isset($zone['ActiveStatus']) ? (string) $zone['ActiveStatus'] : '';
            $plan = isset($planMap[$zid]) ? $planMap[$zid] : null;
            $planLabel = $plan ? vs_edgeone_plan_type_label(isset($plan['PlanType']) ? $plan['PlanType'] : '') : '—';
            $tags = isset($zone['Tags']) && is_array($zone['Tags']) ? $zone['Tags'] : array();
            $searchText = strtolower($displayName . ' ' . $name . ' ' . $zid);

            echo '<tr data-zone-search="' . vs_e($searchText) . '">';
            echo '<td class="vs-edgeone-zones-table__site">';
            echo '<a class="vs-edgeone-zones-table__name" href="#" data-set-zone="' . vs_e($zid) . '" data-goto="domains.php">' . vs_e($displayName) . '</a>';
            if ($name !== '' && $displayName !== $name) {
                echo '<span class="vs-edgeone-zones-table__sub">' . vs_e($name) . '</span>';
            }
            echo '<code class="vs-edgeone-zones-table__id">' . vs_e($zid) . '</code>';
            echo '</td>';
            echo '<td>' . vs_e($area) . '</td>';
            echo '<td>' . vs_e($type) . '</td>';
            echo '<td>';
            if ($active === 'active') {
                echo '<span class="vs-edgeone-zones-status is-ok"><svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M3 8l3 3 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>已启用</span>';
            } else {
                echo '<span class="vs-edgeone-zones-status">' . vs_e(vs_edgeone_translate('ActiveStatus', $active)) . '</span>';
            }
            echo '</td>';
            echo '<td><span class="vs-edgeone-zones-plan">' . vs_e($planLabel) . '</span></td>';
            echo '<td>';
            if (count($tags) > 0) {
                foreach ($tags as $tag) {
                    if (is_array($tag) && isset($tag['TagValue'])) {
                        echo '<span class="vs-edgeone-filter-chip">' . vs_e($tag['TagValue']) . '</span> ';
                    }
                }
            } else {
                echo '—';
            }
            echo '</td>';
            echo '<td class="vs-edgeone-zones-table__actions">';
            echo '<a href="#" data-set-zone="' . vs_e($zid) . '" data-goto="domains.php">域名加速</a>';
            echo '<a href="#" data-set-zone="' . vs_e($zid) . '" data-goto="l7.php">七层加速</a>';
            echo '<a href="#" data-set-zone="' . vs_e($zid) . '" data-goto="security.php">安全策略</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<p class="vs-edgeone-zones-table__foot" id="edgeoneZonesCount">共 ' . count($zones) . ' 条</p>';
        echo '</div>';
    }
    echo '</div>';

    if ($canCreate) {
        echo '<div class="vs-edgeone-zone-create-drawer" id="edgeoneZoneCreateDrawer" hidden aria-hidden="true">';
        echo '<div class="vs-edgeone-zone-create-drawer__overlay" data-zone-create-close></div>';
        echo '<div class="vs-edgeone-zone-create-drawer__panel" role="dialog" aria-modal="true">';
        echo '<div class="vs-edgeone-zone-create-drawer__handle" aria-hidden="true"></div>';
        echo '<div class="vs-edgeone-zone-create-drawer__head">';
        echo '<h4>新增站点</h4>';
        echo '<button type="button" class="vs-edgeone-filter-drawer__close" data-zone-create-close aria-label="关闭">';
        echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
        echo '</button></div>';
        echo '<form class="vs-form vs-edgeone-api-form" id="edgeoneZoneCreateForm" data-action="zone_create">';
        echo '<div class="vs-edgeone-zone-create-drawer__body">';
        echo '<div class="vs-form-row"><label class="vs-label">站点域名</label>';
        echo '<input type="text" name="zone_name" class="vs-input" placeholder="example.com" required></div>';
        echo '<div class="vs-form-row"><label class="vs-label">接入类型</label>';
        echo '<select name="type" class="vs-input">';
        echo '<option value="full">NS 接入</option>';
        echo '<option value="partial">CNAME 接入</option>';
        echo '<option value="noDomainAccess">无域名接入</option>';
        echo '</select></div>';
        echo '<div class="vs-form-row"><label class="vs-label">绑定套餐</label>';
        echo '<select name="plan_id" class="vs-input" required>';
        foreach ($availablePlans as $plan) {
            $pid = isset($plan['PlanId']) ? (string) $plan['PlanId'] : '';
            $ptype = vs_edgeone_plan_type_label(isset($plan['PlanType']) ? $plan['PlanType'] : '');
            echo '<option value="' . vs_e($pid) . '">' . vs_e($ptype) . '（' . vs_e($pid) . '）</option>';
        }
        echo '</select>';
        echo '<p class="vs-form-tip">仅显示尚未绑定站点的套餐</p></div>';
        echo '</div>';
        echo '<div class="vs-edgeone-zone-create-drawer__foot">';
        echo '<button type="button" class="vs-btn vs-btn--ghost" data-zone-create-close>取消</button>';
        echo '<button type="submit" class="vs-btn vs-btn--primary">创建站点</button>';
        echo '</div></form></div></div>';
    }

    return ob_get_clean();
}
