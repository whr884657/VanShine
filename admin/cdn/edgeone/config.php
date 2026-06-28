<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_config', 'EdgeOne · 配置版本');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '环境信息',
        'fetch' => function ($eo, $zoneId) {
            return $eo->configVersion->describeEnvironments(array('ZoneId' => $zoneId));
        },
        'empty_tip' => '暂无环境信息（版本管理可能未开通）',
    ),
    array(
        'title' => '配置组版本列表',
        'fetch' => function ($eo, $zoneId) {
            return vs_edgeone_fetch_config_group_versions($eo, $zoneId);
        },
        'empty_tip' => '暂无配置组版本',
    ),
    array(
        'title' => '版本发布历史',
        'fetch' => function ($eo, $zoneId) {
            return vs_edgeone_fetch_deploy_history($eo, $zoneId);
        },
        'empty_tip' => '暂无发布历史',
    ),
));

vs_edgeone_page_end();
