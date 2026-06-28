<?php
/**
 * 文件：admin/cdn/edgeone/edge.php
 * 作用：EdgeOne 边缘函数与 EdgeKV
 * @version 1.0.1
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';

$zones = array();
$zoneId = '';
$functions = array();
$namespaces = array();
$error = '';
$functionsError = '';
$kvError = '';

if (vs_edgeone_is_ready()) {
    try {
        $eo = EdgeOne::create();
        $zones = vs_edgeone_fetch_zones($eo);
        $zoneId = vs_edgeone_selected_zone();
        if ($zoneId !== '') {
            $fResult = vs_edgeone_try_call(function () use ($eo, $zoneId) {
                return $eo->function->describeFunctions(array(
                    'ZoneId' => $zoneId,
                    'Offset' => 0,
                    'Limit'  => 50,
                ));
            }, array());
            if ($fResult['ok']) {
                $functions = isset($fResult['data']['Functions']) ? $fResult['data']['Functions'] : array();
            } else {
                $functionsError = $fResult['error'];
            }

            $kvResult = vs_edgeone_try_call(function () use ($eo, $zoneId) {
                return $eo->edgeKv->describeEdgeKVNamespaces(array('ZoneId' => $zoneId));
            }, array());
            if ($kvResult['ok']) {
                $namespaces = isset($kvResult['data']['Namespaces']) ? $kvResult['data']['Namespaces'] : array();
            } else {
                $kvError = $kvResult['error'];
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

vs_admin_layout_start('EdgeOne · 边缘函数', VS_EDGEONE_ACTIVE_MENU);
?>
<link rel="stylesheet" href="<?php echo vs_e($vsBase); ?>/assets/css/edgeone-admin.css">

<div class="vs-edgeone-page">
<?php vs_edgeone_render_setup_notice(); ?>
<?php vs_edgeone_render_error($error); ?>
<?php vs_edgeone_nav('cdn_edgeone_edge'); ?>
<?php if (vs_edgeone_is_ready() && count($zones) > 0): ?><div class="vs-panel"><?php vs_edgeone_render_zone_picker($zones); ?></div><?php endif; ?>

<div class="vs-panel">
    <h3 class="vs-panel__title">边缘函数列表</h3>
    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip">请先选择站点</p>
    <?php elseif ($functionsError !== ''): ?>
        <p class="vs-form-tip">加载失败：<?php echo vs_e($functionsError); ?></p>
    <?php elseif (count($functions) === 0): ?>
        <p class="vs-form-tip">暂无边缘函数</p>
    <?php else: ?>
        <pre class="vs-edgeone-json"><?php echo vs_e(json_encode($functions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
    <?php endif; ?>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">EdgeKV 命名空间</h3>
    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip">请先选择站点</p>
    <?php elseif ($kvError !== ''): ?>
        <p class="vs-form-tip">加载失败：<?php echo vs_e($kvError); ?></p>
    <?php elseif (count($namespaces) === 0): ?>
        <p class="vs-form-tip">暂无 EdgeKV 命名空间</p>
    <?php else: ?>
        <pre class="vs-edgeone-json"><?php echo vs_e(json_encode($namespaces, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
    <?php endif; ?>
</div>
</div>

<script>window.VS_EDGEONE_API = <?php echo json_encode($vsBase . '/admin/cdn/edgeone/api.php', JSON_UNESCAPED_UNICODE); ?>;</script>
<?php vs_admin_layout_end(array('edgeone-admin.js')); ?>
