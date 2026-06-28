<?php
/**
 * 文件：admin/cdn/edgeone/monitor.php
 * 作用：EdgeOne 监控日志与套餐
 * @version 1.0.1
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';

$zones = array();
$plans = array();
$billing = null;
$error = '';
$plansError = '';
$billingError = '';

if (vs_edgeone_is_ready()) {
    try {
        $eo = EdgeOne::create();
        $zones = vs_edgeone_fetch_zones($eo);

        $plansResult = vs_edgeone_try_call(function () use ($eo) {
            return $eo->billing->describePlans(array('Offset' => 0, 'Limit' => 20));
        }, array());
        if ($plansResult['ok']) {
            $plans = isset($plansResult['data']['Plans']) ? $plansResult['data']['Plans'] : array();
        } else {
            $plansError = $plansResult['error'];
        }

        $billingResult = vs_edgeone_try_call(function () use ($eo) {
            return $eo->billing->describeBillingData(vs_edgeone_billing_params(7, 'acc_flux'));
        });
        if ($billingResult['ok']) {
            $billing = $billingResult['data'];
        } else {
            $billingError = $billingResult['error'];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

vs_admin_layout_start('EdgeOne · 监控日志', VS_EDGEONE_ACTIVE_MENU);
?>
<link rel="stylesheet" href="<?php echo vs_e($vsBase); ?>/assets/css/edgeone-admin.css">

<div class="vs-edgeone-page">
<?php vs_edgeone_render_setup_notice(); ?>
<?php vs_edgeone_render_error($error); ?>
<?php vs_edgeone_nav('cdn_edgeone_monitor'); ?>

<div class="vs-panel">
    <h3 class="vs-panel__title">套餐列表</h3>
    <?php if ($plansError !== ''): ?>
        <p class="vs-form-tip">加载失败：<?php echo vs_e($plansError); ?></p>
    <?php else: ?>
        <pre class="vs-edgeone-json"><?php echo vs_e(json_encode($plans, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
    <?php endif; ?>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">近 7 日计费流量（acc_flux）</h3>
    <?php if ($billingError !== ''): ?>
        <p class="vs-form-tip">加载失败：<?php echo vs_e($billingError); ?></p>
    <?php else: ?>
        <pre class="vs-edgeone-json"><?php echo vs_e(json_encode($billing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
    <?php endif; ?>
</div>
</div>

<script>window.VS_EDGEONE_API = <?php echo json_encode($vsBase . '/admin/cdn/edgeone/api.php', JSON_UNESCAPED_UNICODE); ?>;</script>
<?php vs_admin_layout_end(array('edgeone-admin.js')); ?>
