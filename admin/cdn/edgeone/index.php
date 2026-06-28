<?php
/**
 * 文件：admin/cdn/edgeone/index.php
 * 作用：EdgeOne 概览
 * @version 1.0.1
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_zone') {
    require __DIR__ . '/api.php';
    exit;
}

$zones = array();
$quota = null;
$quotaError = '';
$error = '';

if (vs_edgeone_is_ready()) {
    try {
        $eo = EdgeOne::create();
        $zones = vs_edgeone_fetch_zones($eo);
        if (vs_edgeone_selected_zone() === '' && count($zones) > 0 && isset($zones[0]['ZoneId'])) {
            vs_edgeone_set_zone($zones[0]['ZoneId']);
        }
        $quotaResult = vs_edgeone_try_call(function () use ($eo) {
            return $eo->content->describeContentQuota();
        });
        if ($quotaResult['ok']) {
            $quota = $quotaResult['data'];
        } else {
            $quotaError = $quotaResult['error'];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

vs_admin_layout_start('EdgeOne', VS_EDGEONE_ACTIVE_MENU);
?>

<link rel="stylesheet" href="<?php echo vs_e($vsBase); ?>/assets/css/edgeone-admin.css">

<div class="vs-edgeone-page">
<?php vs_edgeone_render_setup_notice(); ?>
<?php vs_edgeone_render_error($error); ?>

<?php vs_edgeone_nav('cdn_edgeone'); ?>

<div class="vs-panel">
    <h3 class="vs-panel__title">站点概览</h3>
    <?php if (vs_edgeone_is_ready() && count($zones) > 0): ?>
        <?php vs_edgeone_render_zone_picker($zones); ?>
        <p class="vs-form-tip">共 <?php echo count($zones); ?> 个站点。请使用上方标签切换功能模块。</p>
        <div class="vs-edgeone-stat-grid">
            <?php foreach ($zones as $zone): ?>
                <article class="vs-edgeone-stat-card">
                    <h4><?php echo vs_e(vs_edgeone_zone_display_name($zone)); ?></h4>
                    <?php if (trim((string) (isset($zone['AliasZoneName']) ? $zone['AliasZoneName'] : '')) !== ''): ?>
                        <p class="vs-form-tip">站点备注：<?php echo vs_e($zone['AliasZoneName']); ?></p>
                    <?php endif; ?>
                    <p class="vs-form-tip">域名：<?php echo vs_e(isset($zone['ZoneName']) ? $zone['ZoneName'] : '-'); ?></p>
                    <p class="vs-form-tip">ZoneId：<code><?php echo vs_e(isset($zone['ZoneId']) ? $zone['ZoneId'] : ''); ?></code></p>
                    <p>运行状态：<span class="vs-edgeone-badge"><?php echo vs_e(vs_edgeone_translate('Status', isset($zone['Status']) ? $zone['Status'] : '')); ?></span></p>
                    <p>加速状态：<?php echo vs_e(vs_edgeone_translate('ActiveStatus', isset($zone['ActiveStatus']) ? $zone['ActiveStatus'] : '')); ?></p>
                    <p>接入方式：<?php echo vs_e(vs_edgeone_translate('Type', isset($zone['Type']) ? $zone['Type'] : '')); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php elseif (vs_edgeone_is_ready()): ?>
        <p class="vs-form-tip">暂无站点，请前往「站点管理」创建。</p>
    <?php endif; ?>

    <?php if (is_array($quota)): ?>
        <hr class="vs-divider">
        <h4 class="vs-form-subtitle">内容管理配额</h4>
        <pre class="vs-edgeone-json"><?php echo vs_e(json_encode($quota, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
    <?php elseif ($quotaError !== ''): ?>
        <hr class="vs-divider">
        <p class="vs-form-tip">配额信息暂不可用：<?php echo vs_e($quotaError); ?></p>
    <?php endif; ?>
</div>
</div>

<script>window.VS_EDGEONE_API = <?php echo json_encode($vsBase . '/admin/cdn/edgeone/api.php', JSON_UNESCAPED_UNICODE); ?>;</script>
<?php vs_admin_layout_end(array('edgeone-admin.js')); ?>
