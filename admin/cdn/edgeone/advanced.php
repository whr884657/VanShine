<?php
/**
 * 文件：admin/cdn/edgeone/advanced.php
 * 作用：扩展功能（多通道网关、内容标识、自定义错误页等）
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_advanced', 'EdgeOne · 扩展功能');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '多通道安全加速网关',
        'fetch' => function ($eo, $zoneId) {
            return $eo->multiPathGateway->describeMultiPathGateways(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无多通道网关',
    ),
    array(
        'title' => '内容标识符',
        'fetch' => function ($eo, $zoneId) {
            return $eo->contentIdentifier->describeContentIdentifiers(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无内容标识符',
    ),
    array(
        'title' => '自定义响应页面',
        'fetch' => function ($eo, $zoneId) {
            return $eo->customErrorPage->describeCustomErrorPages(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无自定义响应页',
    ),
    array(
        'title' => '即时转码模板',
        'fetch' => function ($eo, $zoneId) {
            return $eo->mediaTranscode->describeJustInTimeTranscodeTemplates(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无转码模板',
    ),
    array(
        'title' => '安全 API 资源',
        'fetch' => function ($eo, $zoneId) {
            return $eo->securityResource->describeSecurityAPIResource(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无 API 资源',
    ),
    array(
        'title' => '安全 API 服务',
        'fetch' => function ($eo, $zoneId) {
            return $eo->securityResource->describeSecurityAPIService(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 50,
            )));
        },
        'empty_tip' => '暂无 API 服务',
    ),
));

vs_edgeone_page_end();
