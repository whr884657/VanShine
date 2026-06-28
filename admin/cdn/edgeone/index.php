<?php
/**
 * 文件：admin/cdn/edgeone/index.php
 * 作用：EdgeOne 概览
 * @version 1.0.0
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_zone') {
    require __DIR__ . '/api.php';
    exit;
}

$zones = array();
$quota = null;
$error = '';

if (vs_edgeone_is_ready()) {
    try {
        $eo = EdgeOne::create();
        $zones = vs_edgeone_fetch_zones($eo);
        if (vs_edgeone_selected_zone() === '' && count($zones) > 0 && isset($zones[0]['ZoneId'])) {
            vs_edgeone_set_zone($zones[0]['ZoneId']);
        }
        $quota = $eo->content->describeContentQuota(array());
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

vs_admin_layout_start('EdgeOne 概览', 'cdn_edgeone');
?>

<link rel="stylesheet" href="<?php echo vs_e($vsBase); ?>/assets/css/edgeone-admin.css">

<?php vs_edgeone_render_setup_notice(); ?>

<?php if ($error !== ''): ?>
    <div class="vs-panel vs-alert vs-alert--error"><?php echo vs_e($error); ?></div>
<?php endif; ?>

<?php vs_edgeone_nav('cdn_edgeone'); ?>

<div class="vs-panel">
    <h3 class="vs-panel__title">站点概览</h3>
    <?php if (vs_edgeone_is_ready() && count($zones) > 0): ?>
        <?php vs_edgeone_render_zone_picker($zones); ?>
        <p class="vs-form-tip">共 <?php echo count($zones); ?> 个站点。请在左侧菜单进入各功能模块操作。</p>
        <div class="vs-edgeone-stat-grid">
            <?php foreach ($zones as $zone): ?>
                <article class="vs-edgeone-stat-card">
                    <h4><?php echo vs_e(isset($zone['ZoneName']) ? $zone['ZoneName'] : ''); ?></h4>
                    <p class="vs-form-tip">ZoneId: <?php echo vs_e(isset($zone['ZoneId']) ? $zone['ZoneId'] : ''); ?></p>
                    <p>状态: <?php echo vs_e(isset($zone['ActiveStatus']) ? $zone['ActiveStatus'] : '-'); ?></p>
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
    <?php endif; ?>
</div>

<script>window.VS_EDGEONE_API = <?php echo json_encode($vsBase . '/admin/cdn/edgeone/api.php', JSON_UNESCAPED_UNICODE); ?>;</script>
<?php vs_admin_layout_end(array('edgeone-admin.js')); ?>
