<?php
/**
 * 文件：admin/cdn/edgeone/config.php
 * 作用：配置组版本与环境
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_config', 'EdgeOne · 配置版本');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '环境信息',
        'fetch' => function ($eo, $zoneId) {
            return $eo->configVersion->describeEnvironments(vs_edgeone_zone_params());
        },
    ),
    array(
        'title' => '配置组版本列表',
        'fetch' => function ($eo, $zoneId) {
            return $eo->configVersion->describeConfigGroupVersions(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无配置组版本',
    ),
    array(
        'title' => '版本发布历史',
        'fetch' => function ($eo, $zoneId) {
            return $eo->configVersion->describeDeployHistory(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无发布历史',
    ),
));

vs_edgeone_page_end();
