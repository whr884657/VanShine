<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';

$zones = array();
$zoneId = '';
$proxies = array();
$loadBalancers = array();
$error = '';

if (vs_edgeone_is_ready()) {
    try {
        $eo = EdgeOne::create();
        $zones = vs_edgeone_fetch_zones($eo);
        $zoneId = vs_edgeone_selected_zone();
        if ($zoneId !== '') {
            $p = $eo->l4Proxy->describeL4Proxy(array('ZoneId' => $zoneId));
            $proxies = isset($p['L4Proxies']) ? $p['L4Proxies'] : array();
            $lb = $eo->loadBalancer->describeLoadBalancerList(array('ZoneId' => $zoneId));
            $loadBalancers = isset($lb['LoadBalancers']) ? $lb['LoadBalancers'] : array();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

vs_admin_layout_start('EdgeOne 四层代理', 'cdn_edgeone_l4');
?>
<link rel="stylesheet" href="<?php echo vs_e($vsBase); ?>/assets/css/edgeone-admin.css">
<?php vs_edgeone_render_setup_notice(); ?>
<?php if ($error !== ''): ?><div class="vs-panel vs-alert vs-alert--error"><?php echo vs_e($error); ?></div><?php endif; ?>
<?php vs_edgeone_nav('cdn_edgeone_l4'); ?>
<?php if (vs_edgeone_is_ready() && count($zones) > 0): ?><div class="vs-panel"><?php vs_edgeone_render_zone_picker($zones); ?></div><?php endif; ?>

<div class="vs-panel">
    <h3 class="vs-panel__title">四层代理实例</h3>
    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip">请先选择站点</p>
    <?php else: ?>
        <pre class="vs-edgeone-json"><?php echo vs_e(json_encode($proxies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
    <?php endif; ?>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">负载均衡实例</h3>
    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip">请先选择站点</p>
    <?php else: ?>
        <pre class="vs-edgeone-json"><?php echo vs_e(json_encode($loadBalancers, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
    <?php endif; ?>
</div>

<script>window.VS_EDGEONE_API = <?php echo json_encode($vsBase . '/admin/cdn/edgeone/api.php', JSON_UNESCAPED_UNICODE); ?>;</script>
<?php vs_admin_layout_end(array('edgeone-admin.js')); ?>
