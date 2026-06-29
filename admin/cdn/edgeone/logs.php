<?php
/**
 * 文件：admin/cdn/edgeone/logs.php
 * 页面：EdgeOne · 日志服务
 * 路由：/admin/cdn/edgeone/logs.php
 * 菜单：cdn_edgeone_logs
 *
 * 作用：展示实时日志投递等日志服务配置
 *
 * 说明：只读查询页，日志投递任务需在腾讯云控制台创建
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_logs', 'EdgeOne · 日志服务');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '实时日志投递任务',
        'fetch' => function ($eo, $zoneId) {
            return $eo->log->describeRealtimeLogDeliveryTasks(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无实时日志投递任务',
    ),
));

vs_edgeone_page_end();
