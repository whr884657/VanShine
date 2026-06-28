<?php
/**
 * 文件：admin/cdn/edgeone/billing.php
 * 作用：套餐与计费数据
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

$ctx = vs_edgeone_page_start('cdn_edgeone_billing', 'EdgeOne · 套餐计费');

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => '套餐列表',
        'require_zone' => false,
        'fetch' => function ($eo) {
            return $eo->billing->describePlans(array('Offset' => 0, 'Limit' => 50));
        },
        'empty_tip' => '暂无套餐',
    ),
    array(
        'title' => '可购买套餐',
        'require_zone' => false,
        'fetch' => function ($eo) {
            return $eo->billing->describeAvailablePlans(array());
        },
        'empty_tip' => '暂无可用套餐信息',
    ),
    array(
        'title' => '近 7 日计费流量（acc_flux）',
        'require_zone' => false,
        'fetch' => function ($eo, $zoneId) {
            return $eo->billing->describeBillingData(vs_edgeone_billing_params(7, 'acc_flux', $zoneId !== '' ? $zoneId : null));
        },
    ),
));

vs_edgeone_page_end();
