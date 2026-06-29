<?php
/**
 * 文件：admin/cdn/edgeone/advanced.php
 * 页面：EdgeOne · 扩展功能
 * 路由：/admin/cdn/edgeone/advanced.php
 * 菜单：cdn_edgeone_advanced（配置与扩展 → 扩展功能）
 *
 * 作用：
 * - 查询并展示多通道安全加速网关、内容标识符等扩展能力
 * - 通过 vs_edgeone_render_sections() 统一渲染 API 结果为表格/卡片
 *
 * 说明：
 * - 只读查询页，无写操作表单
 * - 需先在 EdgeOne 控制台开通对应增值功能
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_advanced', 'EdgeOne · 扩展功能');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '多通道安全加速网关',
        'fetch' => function ($eo, $zoneId) {
            return $eo->multiPathGateway->describeMultiPathGateways(array(
                'ZoneId' => $zoneId,
                'Offset' => 0,
                'Limit'  => 50,
            ));
        },
        'empty_tip' => '暂无多通道网关',
    ),
    array(
        'title' => '内容标识符',
        'require_zone' => false,
        'fetch' => function ($eo) {
            return $eo->contentIdentifier->describeContentIdentifiers(array(
                'Offset' => 0,
                'Limit'  => 50,
            ));
        },
        'empty_tip' => '暂无内容标识符（可能未开通白名单）',
    ),
    array(
        'title' => '自定义响应页面',
        'fetch' => function ($eo, $zoneId) {
            return $eo->customErrorPage->describeCustomErrorPages(array(
                'ZoneId' => $zoneId,
                'Offset' => 0,
                'Limit'  => 50,
            ));
        },
        'empty_tip' => '暂无自定义响应页',
    ),
    array(
        'title' => '即时转码模板',
        'fetch' => function ($eo, $zoneId) {
            return $eo->mediaTranscode->describeJustInTimeTranscodeTemplates(array(
                'ZoneId' => $zoneId,
                'Offset' => 0,
                'Limit'  => 50,
            ));
        },
        'empty_tip' => '暂无转码模板',
    ),
    array(
        'title' => '安全 API 资源',
        'fetch' => function ($eo, $zoneId) {
            return $eo->securityResource->describeSecurityAPIResource(array(
                'ZoneId' => $zoneId,
                'Offset' => 0,
                'Limit'  => 50,
            ));
        },
        'empty_tip' => '暂无 API 资源',
    ),
    array(
        'title' => '安全 API 服务',
        'fetch' => function ($eo, $zoneId) {
            return $eo->securityResource->describeSecurityAPIService(array(
                'ZoneId' => $zoneId,
                'Offset' => 0,
                'Limit'  => 50,
            ));
        },
        'empty_tip' => '暂无 API 服务',
    ),
));

vs_edgeone_page_end();
