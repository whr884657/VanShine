<?php
/**
 * 文件：admin/cdn/edgeone/config.php
 * 页面：EdgeOne · 配置版本
 * 路由：/admin/cdn/edgeone/config.php
 * 菜单：cdn_edgeone_config（配置与扩展 → 配置版本）
 *
 * 作用：
 * - 展示环境信息、配置组版本列表、版本发布历史
 * - 调用 DescribeEnvironments / 配置组相关 API
 *
 * 说明：
 * - 版本管理需站点已开通 EdgeOne 配置版本能力
 * - 无数据时显示「可能未开通」类提示，非 API 报错
 */
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
