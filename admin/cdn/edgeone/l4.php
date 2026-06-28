<?php
/**
 * 文件：admin/cdn/edgeone/l4.php
 * 作用：EdgeOne 四层代理
 * @version 1.0.1
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';

$zones = array();
$zoneId = '';
$proxies = array();
$loadBalancers = array();
$error = '';
$proxyError = '';
$lbError = '';

if (vs_edgeone_is_ready()) {
    try {
        $eo = EdgeOne::create();
        $zones = vs_edgeone_fetch_zones($eo);
        $zoneId = vs_edgeone_selected_zone();
        if ($zoneId !== '') {
            $pResult = vs_edgeone_try_call(function () use ($eo, $zoneId) {
                return $eo->l4Proxy->describeL4Proxy(array('ZoneId' => $zoneId));
            }, array());
            if ($pResult['ok']) {
                $proxies = isset($pResult['data']['L4Proxies']) ? $pResult['data']['L4Proxies'] : array();
            } else {
                $proxyError = $pResult['error'];
            }

            $lbResult = vs_edgeone_try_call(function () use ($eo, $zoneId) {
                return $eo->loadBalancer->describeLoadBalancerList(array('ZoneId' => $zoneId));
            }, array());
            if ($lbResult['ok']) {
                $loadBalancers = isset($lbResult['data']['LoadBalancers']) ? $lbResult['data']['LoadBalancers'] : array();
            } else {
                $lbError = $lbResult['error'];
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

vs_admin_layout_start('EdgeOne · 四层代理', VS_EDGEONE_ACTIVE_MENU);
?>
<link rel="stylesheet" href="<?php echo vs_e($vsBase); ?>/assets/css/edgeone-admin.css">

<div class="vs-edgeone-page">
<?php vs_edgeone_render_setup_notice(); ?>
<?php vs_edgeone_render_error($error); ?>
<?php vs_edgeone_nav('cdn_edgeone_l4'); ?>
<?php if (vs_edgeone_is_ready() && count($zones) > 0): ?><div class="vs-panel"><?php vs_edgeone_render_zone_picker($zones); ?></div><?php endif; ?>

<div class="vs-panel">
    <h3 class="vs-panel__title">四层代理实例</h3>
    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip">请先选择站点</p>
    <?php elseif ($proxyError !== ''): ?>
        <p class="vs-form-tip">加载失败：<?php echo vs_e($proxyError); ?></p>
    <?php else: ?>
        <pre class="vs-edgeone-json"><?php echo vs_e(json_encode($proxies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
    <?php endif; ?>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">负载均衡实例</h3>
    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip">请先选择站点</p>
    <?php elseif ($lbError !== ''): ?>
        <p class="vs-form-tip">加载失败：<?php echo vs_e($lbError); ?></p>
    <?php else: ?>
        <pre class="vs-edgeone-json"><?php echo vs_e(json_encode($loadBalancers, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
    <?php endif; ?>
</div>
</div>

<script>window.VS_EDGEONE_API = <?php echo json_encode($vsBase . '/admin/cdn/edgeone/api.php', JSON_UNESCAPED_UNICODE); ?>;</script>
<?php vs_admin_layout_end(array('edgeone-admin.js')); ?>
