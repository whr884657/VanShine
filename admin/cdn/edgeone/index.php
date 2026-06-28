<?php
/**
 * 文件：admin/cdn/edgeone/index.php
 * 作用：EdgeOne 概览
 * @version 1.0.2
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_zone') {
    require __DIR__ . '/api.php';
    exit;
}

$ctx = vs_edgeone_page_start('cdn_edgeone', 'EdgeOne');
$zones = $ctx['zones'];
$zoneId = $ctx['zone_id'];
$quotaResult = array('ok' => true, 'data' => null, 'error' => '');

if ($ctx['eo'] !== null && $zoneId !== '') {
    $quotaResult = vs_edgeone_try_call(function () use ($ctx, $zoneId) {
        return $ctx['eo']->content->describeContentQuota(vs_edgeone_zone_params());
    });
}
?>

<div class="vs-panel">
    <h3 class="vs-panel__title">站点概览</h3>
    <?php if (vs_edgeone_is_ready() && count($zones) > 0): ?>
        <p class="vs-form-tip">共 <?php echo count($zones); ?> 个站点。请使用上方分组导航切换功能模块。</p>
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
</div>

<?php
if ($zoneId === '') {
    echo '<div class="vs-panel"><h3 class="vs-panel__title">内容管理配额</h3><p class="vs-form-tip">请先选择站点后查看配额</p></div>';
} else {
    vs_edgeone_render_section('内容管理配额', $quotaResult, true, $zoneId, '暂无配额数据');
}

vs_edgeone_page_end();
