<?php
/**
 * 文件：admin/cdn/edgeone/cert.php
 * 页面：EdgeOne · 证书管理
 * 路由：/admin/cdn/edgeone/cert.php
 * 菜单：cdn_edgeone_cert
 *
 * 作用：展示站点默认 HTTPS 证书列表
 *
 * 说明：域名级免费证书申请在域名管理页 HTTPS 抽屉中完成
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_cert', 'EdgeOne · 证书管理');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '默认证书列表',
        'fetch' => function ($eo, $zoneId) {
            return $eo->certificate->describeDefaultCertificates(vs_edgeone_zone_params());
        },
        'empty_tip' => '暂无证书',
    ),
));

vs_edgeone_page_end();
