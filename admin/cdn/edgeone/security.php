<?php
/**
 * 文件：admin/cdn/edgeone/security.php
 * 作用：EdgeOne 安全策略
 * @version 1.0.2
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_security', 'EdgeOne · 安全策略');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => 'Web 安全防护配置',
        'fetch' => function ($eo, $zoneId) {
            return $eo->security->describeSecurityPolicy(vs_edgeone_zone_params(array(
                'Entity' => '@ZoneLevel@domain',
            )));
        },
    ),
    array(
        'title' => '安全 IP 组',
        'fetch' => function ($eo, $zoneId) {
            return $eo->security->describeSecurityIPGroup(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无安全 IP 组',
    ),
    array(
        'title' => 'DDoS 防护信息',
        'fetch' => function ($eo, $zoneId) {
            return $eo->security->describeDDoSProtection(vs_edgeone_zone_params());
        },
    ),
    array(
        'title' => '安全策略模板',
        'fetch' => function ($eo, $zoneId) {
            return $eo->security->describeWebSecurityTemplates(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无安全策略模板',
    ),
    array(
        'title' => '策略模板绑定关系',
        'fetch' => function ($eo, $zoneId) {
            return $eo->security->describeSecurityTemplateBindings(vs_edgeone_zone_params());
        },
        'empty_tip' => '暂无绑定关系',
    ),
));

vs_edgeone_page_end();
