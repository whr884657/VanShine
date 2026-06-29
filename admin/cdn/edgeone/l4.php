<?php
/**
 * 文件：admin/cdn/edgeone/l4.php
 * 页面：EdgeOne · 四层代理
 * 路由：/admin/cdn/edgeone/l4.php
 * 菜单：cdn_edgeone_l4（网络与负载 → 四层代理）
 *
 * 作用：
 * - 展示四层代理实例列表
 * - 展示首个实例下的四层转发规则
 *
 * 说明：
 * - 只读查询页；转发规则依赖 DescribeL4Proxy 返回的 ProxyId
 */
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
