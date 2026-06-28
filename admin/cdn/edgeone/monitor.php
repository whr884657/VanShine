<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';

$zones = array();
$plans = array();
$billing = null;
$error = '';

if (vs_edgeone_is_ready()) {
    try {
        $eo = EdgeOne::create();
        $zones = vs_edgeone_fetch_zones($eo);
        $p = $eo->billing->describePlans(array('Offset' => 0, 'Limit' => 20));
        $plans = isset($p['Plans']) ? $p['Plans'] : array();
        $billing = $eo->billing->describeBillingData(array(
            'StartTime' => date('Y-m-d', strtotime('-7 days')),
            'EndTime'   => date('Y-m-d'),
            'MetricName'=> 'acc_flux',
        ));
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

vs_admin_layout_start('EdgeOne 监控日志', 'cdn_edgeone_monitor');
?>
<link rel="stylesheet" href="<?php echo vs_e($vsBase); ?>/assets/css/edgeone-admin.css">
<?php vs_edgeone_render_setup_notice(); ?>
<?php if ($error !== ''): ?><div class="vs-panel vs-alert vs-alert--error"><?php echo vs_e($error); ?></div><?php endif; ?>
<?php vs_edgeone_nav('cdn_edgeone_monitor'); ?>

<div class="vs-panel">
    <h3 class="vs-panel__title">套餐列表</h3>
    <pre class="vs-edgeone-json"><?php echo vs_e(json_encode($plans, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">近 7 日计费流量（acc_flux）</h3>
    <pre class="vs-edgeone-json"><?php echo vs_e(json_encode($billing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
</div>

<script>window.VS_EDGEONE_API = <?php echo json_encode($vsBase . '/admin/cdn/edgeone/api.php', JSON_UNESCAPED_UNICODE); ?>;</script>
<?php vs_admin_layout_end(array('edgeone-admin.js')); ?>
