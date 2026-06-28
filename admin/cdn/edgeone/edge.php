<?php
/**
 * 文件：admin/cdn/edgeone/edge.php
 * 作用：EdgeOne 边缘函数
 * @version 1.0.2
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_edge', 'EdgeOne · 边缘函数');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '边缘函数列表',
        'fetch' => function ($eo, $zoneId) {
            return $eo->function->describeFunctions(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无边缘函数',
    ),
    array(
        'title' => '触发规则',
        'fetch' => function ($eo, $zoneId) {
            return $eo->function->describeFunctionRules(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无触发规则',
    ),
    array(
        'title' => '函数副本',
        'fetch' => function ($eo, $zoneId) {
            return $eo->function->describeFunctionReplicas(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无函数副本',
    ),
    array(
        'title' => '运行环境',
        'fetch' => function ($eo, $zoneId) {
            return $eo->function->describeFunctionRuntimeEnvironment(vs_edgeone_zone_params());
        },
    ),
    array(
        'title' => '组件绑定',
        'fetch' => function ($eo, $zoneId) {
            return $eo->function->describeFunctionComponentBindings(vs_edgeone_zone_params());
        },
        'empty_tip' => '暂无组件绑定',
    ),
));

vs_edgeone_page_end();
