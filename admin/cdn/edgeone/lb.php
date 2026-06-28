<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_lb', 'EdgeOne · 负载均衡');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '负载均衡实例列表',
        'fetch' => function ($eo, $zoneId) {
            return $eo->loadBalancer->describeLoadBalancerList(array('ZoneId' => $zoneId));
        },
        'empty_tip' => '暂无负载均衡实例',
    ),
    array(
        'title' => '源站组健康状态（首个实例）',
        'fetch' => function ($eo, $zoneId) {
            return vs_edgeone_fetch_lb_health($eo, $zoneId);
        },
    ),
));

vs_edgeone_page_end();
