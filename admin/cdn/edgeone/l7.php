<?php
/**
 * 文件：admin/cdn/edgeone/l7.php
 * 作用：七层加速规则与全局配置
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_l7', 'EdgeOne · 七层加速');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '七层加速规则',
        'fetch' => function ($eo, $zoneId) {
            return $eo->l7Acc->describeL7AccRules(vs_edgeone_zone_params(array(
                'Entity' => 'domain',
                'Offset' => 0,
                'Limit'  => 100,
            )));
        },
        'empty_tip' => '暂无七层加速规则',
    ),
    array(
        'title' => '七层加速全局配置',
        'fetch' => function ($eo, $zoneId) {
            return $eo->l7Acc->describeL7AccSetting(vs_edgeone_zone_params());
        },
    ),
));

vs_edgeone_page_end();
