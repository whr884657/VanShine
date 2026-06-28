<?php
/**
 * 文件：admin/cdn/edgeone/analytics.php
 * 作用：数据分析与 DDoS 统计
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_analytics', 'EdgeOne · 数据分析');

$range = vs_edgeone_analytics_range(7);

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '七层流量分析（近 7 日）',
        'fetch' => function ($eo, $zoneId) use ($range) {
            return $eo->analytics->describeTimingL7AnalysisData(array_merge(vs_edgeone_zone_params(), $range, array(
                'MetricNames' => array('l7Flow_flux'),
                'Interval'    => 'day',
            )));
        },
    ),
    array(
        'title' => '回源时序数据（近 7 日）',
        'fetch' => function ($eo, $zoneId) use ($range) {
            return $eo->analytics->describeTimingL7OriginPullData(array_merge(vs_edgeone_zone_params(), $range, array(
                'MetricNames' => array('l7Flow_request'),
                'Interval'    => 'day',
            )));
        },
    ),
    array(
        'title' => 'DDoS 攻击事件',
        'fetch' => function ($eo, $zoneId) use ($range) {
            return $eo->analytics->describeDDoSAttackEvent(array_merge(vs_edgeone_zone_params(), $range, array(
                'Offset' => 0,
                'Limit'  => 20,
            )));
        },
        'empty_tip' => '暂无 DDoS 攻击事件',
    ),
    array(
        'title' => '四层流量时序（近 7 日）',
        'fetch' => function ($eo, $zoneId) use ($range) {
            return $eo->analytics->describeTimingL4Data(array_merge(vs_edgeone_zone_params(), $range, array(
                'MetricNames' => array('l4Flow_flux'),
                'Interval'    => 'day',
            )));
        },
    ),
));

vs_edgeone_page_end();
