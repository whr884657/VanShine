<?php
/**
 * 文件：admin/cdn/edgeone/security.php
 * 页面：EdgeOne · 安全策略
 * 路由：/admin/cdn/edgeone/security.php
 * 菜单：cdn_edgeone_security（安全与证书 → 安全策略）
 *
 * 作用：
 * - 展示 Web 安全防护配置、IP 分组等安全策略数据
 * - 按站点查询 DescribeSecurityPolicy 等 API
 *
 * 说明：
 * - 只读查询页；复杂策略编辑建议在腾讯云控制台完成
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_security', 'EdgeOne · 安全策略');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => 'Web 安全防护配置',
        'fetch' => function ($eo, $zoneId) {
            return $eo->security->describeSecurityPolicy(array(
                'ZoneId' => $zoneId,
                'Entity' => '@ZoneLevel@domain',
            ));
        },
    ),
    array(
        'title' => '安全 IP 组',
        'fetch' => function ($eo, $zoneId) {
            return $eo->security->describeSecurityIPGroup(array('ZoneId' => $zoneId));
        },
        'empty_tip' => '暂无安全 IP 组',
    ),
    array(
        'title' => 'DDoS 防护信息',
        'fetch' => function ($eo, $zoneId) {
            return $eo->security->describeDDoSProtection(array('ZoneId' => $zoneId));
        },
    ),
    array(
        'title' => '安全策略模板',
        'fetch' => function ($eo, $zoneId) {
            return $eo->security->describeWebSecurityTemplates(array(
                'ZoneIds' => vs_edgeone_zone_ids($zoneId),
            ));
        },
        'empty_tip' => '暂无安全策略模板',
    ),
));

vs_edgeone_page_end();
