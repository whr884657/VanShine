<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_analytics', 'EdgeOne · 数据分析');
$rangeDays = 7;

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '七层流量分析（近 7 日）',
        'fetch' => function ($eo, $zoneId) use ($rangeDays) {
            return $eo->analytics->describeTimingL7AnalysisData(
                vs_edgeone_analytics_query($rangeDays, 'l7Flow_flux', $zoneId)
            );
        },
    ),
    array(
        'title' => '回源时序数据（近 7 日）',
        'fetch' => function ($eo, $zoneId) use ($rangeDays) {
            return $eo->analytics->describeTimingL7OriginPullData(
                vs_edgeone_analytics_query($rangeDays, 'l7Flow_request', $zoneId)
            );
        },
    ),
    array(
        'title' => 'DDoS 攻击事件',
        'fetch' => function ($eo, $zoneId) use ($rangeDays) {
            return $eo->analytics->describeDDoSAttackEvent(
                vs_edgeone_ddos_event_query($rangeDays, $zoneId)
            );
        },
        'empty_tip' => '暂无 DDoS 攻击事件',
    ),
    array(
        'title' => '四层流量时序（近 7 日）',
        'fetch' => function ($eo, $zoneId) use ($rangeDays) {
            return $eo->analytics->describeTimingL4Data(
                vs_edgeone_analytics_query($rangeDays, 'l4Flow_flux', $zoneId)
            );
        },
    ),
));

vs_edgeone_page_end();
