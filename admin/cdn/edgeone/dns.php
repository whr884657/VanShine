<?php
/**
 * 文件：admin/cdn/edgeone/dns.php
 * 页面：EdgeOne · DNS 记录
 * 路由：/admin/cdn/edgeone/dns.php
 * 菜单：cdn_edgeone_dns
 *
 * 作用：展示与创建站点 DNS 记录（调用 EdgeOne DNS API）
 *
 * 说明：需站点使用 EdgeOne DNS 托管方可管理记录
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api.php';
    exit;
}

$ctx = vs_edgeone_page_start('cdn_edgeone_dns', 'EdgeOne · DNS 记录');

?>
<div class="vs-panel">
    <h3 class="vs-panel__title">新增 DNS 记录</h3>
    <form class="vs-form vs-edgeone-api-form" data-action="dns_create">
        <div class="vs-form-grid">
            <div class="vs-form-row"><label class="vs-label">主机记录</label><input type="text" name="name" class="vs-input" required></div>
            <div class="vs-form-row"><label class="vs-label">类型</label>
                <select name="type" class="vs-input"><option>A</option><option>AAAA</option><option>CNAME</option><option>TXT</option></select>
            </div>
            <div class="vs-form-row"><label class="vs-label">记录值</label><input type="text" name="content" class="vs-input" required></div>
            <div class="vs-form-row"><label class="vs-label">TTL</label><input type="number" name="ttl" class="vs-input" value="600"></div>
        </div>
        <div class="vs-form-actions"><button type="submit" class="vs-btn vs-btn--primary">添加 DNS</button></div>
    </form>
</div>
<?php

vs_edgeone_render_sections($ctx['eo'], $ctx['zone_id'], array(
    array(
        'title' => 'DNS 记录列表',
        'fetch' => function ($eo, $zoneId) {
            return $eo->dns->describeDnsRecords(vs_edgeone_zone_params(array(
                'Offset' => 0,
                'Limit'  => 100,
            )));
        },
        'empty_tip' => '暂无 DNS 记录',
    ),
));

vs_edgeone_page_end();
