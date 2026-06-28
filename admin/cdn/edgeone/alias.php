<?php
/**
 * 文件：admin/cdn/edgeone/alias.php
 * 作用：别称域名管理
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_alias', 'EdgeOne · 别称域名');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '别称域名列表',
        'fetch' => function ($eo, $zoneId) {
            return $eo->aliasDomain->describeAliasDomains(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 100,
            )));
        },
        'empty_tip' => '暂无别称域名',
    ),
));

vs_edgeone_page_end();
