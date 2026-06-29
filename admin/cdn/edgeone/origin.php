<?php
/**
 * 文件：admin/cdn/edgeone/origin.php
 * 页面：EdgeOne · 源站防护
 * 路由：/admin/cdn/edgeone/origin.php
 * 菜单：cdn_edgeone_origin
 *
 * 作用：展示源站 ACL 与源站组列表
 *
 * 说明：只读查询页，策略编辑建议在腾讯云控制台完成
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_origin', 'EdgeOne · 源站防护');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '源站防护详情',
        'fetch' => function ($eo, $zoneId) {
            return $eo->originAcl->describeOriginACL(vs_edgeone_zone_params());
        },
    ),
    array(
        'title' => '源站组列表',
        'fetch' => function ($eo, $zoneId) {
            return $eo->loadBalancer->describeOriginGroup(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 100,
            )));
        },
        'empty_tip' => '暂无源站组',
    ),
));

vs_edgeone_page_end();
