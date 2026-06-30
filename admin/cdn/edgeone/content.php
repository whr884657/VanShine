<?php
/**
 * 文件：admin/cdn/edgeone/content.php
 * 页面：EdgeOne · 缓存管理
 * 路由：/admin/cdn/edgeone/content.php
 * 菜单：cdn_edgeone_content（缓存与函数 → 缓存管理）
 *
 * 作用：缓存刷新（Purge）与预热（Prefetch）任务提交与列表
 *
 * 说明：调用 CreatePurgeTask / CreatePrefetchTask 等 API
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';
require_once __DIR__ . '/includes/content-page.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api.php';
    exit;
}

$ctx = vs_edgeone_page_start('cdn_edgeone_content', 'EdgeOne · 缓存管理');
$zones = $ctx['zones'];
$zoneId = $ctx['zone_id'];
$eo = $ctx['eo'];

if (vs_edgeone_is_fragment_request()) {
    echo '<div id="edgeoneZonePanelFragment" hidden aria-hidden="true">';
    vs_edgeone_render_cache_zone_panel($zones, $zoneId, $eo);
    echo '</div>';
}

$purges = array();
$prefetches = array();
$purgeError = '';
$prefetchError = '';

$cacheCtx = vs_edgeone_fetch_cache_page_context($eo, $zoneId);
$supportsPrefetch = $cacheCtx['supports_prefetch'];

if ($eo !== null && $zoneId !== '') {
    $range = vs_edgeone_time_range(30);
    $pResult = vs_edgeone_try_call(function () use ($eo, $zoneId, $range) {
        return $eo->content->describePurgeTasks(array_merge(array(
            'ZoneId' => $zoneId,
            'Offset' => 0,
            'Limit'  => 20,
        ), $range));
    }, array());
    if ($pResult['ok']) {
        $purges = isset($pResult['data']['Tasks']) ? $pResult['data']['Tasks'] : (isset($pResult['data']['PurgeLogs']) ? $pResult['data']['PurgeLogs'] : array());
    } else {
        $purgeError = $pResult['error'];
    }

    if ($supportsPrefetch) {
        $fResult = vs_edgeone_try_call(function () use ($eo, $zoneId, $range) {
            return $eo->content->describePrefetchTasks(array_merge(array(
                'ZoneId' => $zoneId,
                'Offset' => 0,
                'Limit'  => 20,
            ), $range));
        }, array());
        if ($fResult['ok']) {
            $prefetches = isset($fResult['data']['Tasks']) ? $fResult['data']['Tasks'] : (isset($fResult['data']['PrefetchLogs']) ? $fResult['data']['PrefetchLogs'] : array());
        } else {
            $prefetchError = $fResult['error'];
        }
    }
}

vs_edgeone_render_cache_task_panel($supportsPrefetch);
?>

<div class="vs-panel">
    <h3 class="vs-panel__title">任务记录（近 30 日）</h3>
    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip">请先选择站点</p>
    <?php else: ?>
        <h4 class="vs-form-subtitle">刷新任务</h4>
        <?php if ($purgeError !== ''): ?>
            <p class="vs-form-tip">加载失败：<?php echo vs_e($purgeError); ?></p>
        <?php elseif (count($purges) === 0): ?>
            <p class="vs-form-tip">暂无刷新记录</p>
        <?php else: ?>
            <?php vs_edgeone_render_api_data(array('Tasks' => $purges), array('task_table' => true)); ?>
        <?php endif; ?>

        <?php if ($supportsPrefetch): ?>
            <h4 class="vs-form-subtitle">预热任务</h4>
            <?php if ($prefetchError !== ''): ?>
                <p class="vs-form-tip">加载失败：<?php echo vs_e($prefetchError); ?></p>
            <?php elseif (count($prefetches) === 0): ?>
                <p class="vs-form-tip">暂无预热记录</p>
            <?php else: ?>
                <?php vs_edgeone_render_api_data(array('Tasks' => $prefetches), array('task_table' => true)); ?>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
vs_edgeone_page_end();
?>
