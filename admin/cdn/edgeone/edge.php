<?php
/**
 * 文件：admin/cdn/edgeone/edge.php
 * 页面：EdgeOne · 边缘函数
 * 路由：/admin/cdn/edgeone/edge.php
 * 菜单：cdn_edgeone_edge（内容与函数 → 边缘函数）
 *
 * 作用：
 * - 展示边缘函数列表与触发规则
 * - 调用 DescribeFunctions / DescribeFunctionRules 等 API
 *
 * 说明：
 * - 只读查询页；已移除 EdgeKV 相关入口
 * - 函数创建与代码编辑请在腾讯云控制台完成
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_edge', 'EdgeOne · 边缘函数');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '边缘函数列表',
        'fetch' => function ($eo, $zoneId) {
            return $eo->function->describeFunctions(array(
                'ZoneId' => $zoneId,
                'Offset' => 0,
                'Limit'  => 50,
            ));
        },
        'empty_tip' => '暂无边缘函数',
    ),
    array(
        'title' => '触发规则',
        'fetch' => function ($eo, $zoneId) {
            return $eo->function->describeFunctionRules(array('ZoneId' => $zoneId));
        },
        'empty_tip' => '暂无触发规则',
    ),
));

vs_edgeone_page_end();
