<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_l4', 'EdgeOne · 四层代理');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '四层代理实例',
        'fetch' => function ($eo, $zoneId) {
            return $eo->l4Proxy->describeL4Proxy(array('ZoneId' => $zoneId));
        },
        'empty_tip' => '暂无四层代理实例',
    ),
    array(
        'title' => '四层转发规则（首个实例）',
        'fetch' => function ($eo, $zoneId) {
            return vs_edgeone_fetch_l4_proxy_rules($eo, $zoneId);
        },
        'empty_tip' => '暂无转发规则',
    ),
));

vs_edgeone_page_end();
