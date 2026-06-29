<?php
/**
 * 文件：admin/cdn/edgeone/content.php
 * 页面：EdgeOne · 内容管理
 * 路由：/admin/cdn/edgeone/content.php
 * 菜单：cdn_edgeone_content
 *
 * 作用：缓存刷新（Purge）与预热（Prefetch）任务提交与列表
 *
 * 说明：调用 CreatePurgeTask / CreatePrefetchTask 等 API
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api.php';
    exit;
}

$ctx = vs_edgeone_page_start('cdn_edgeone_content', 'EdgeOne · 内容管理');
$zones = $ctx['zones'];
$zoneId = $ctx['zone_id'];
$eo = $ctx['eo'];
$purges = array();
$prefetches = array();
$purgeError = '';
$prefetchError = '';

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
?>

<div class="vs-panel">
    <h3 class="vs-panel__title">清除缓存</h3>
    <form class="vs-form vs-edgeone-api-form" data-action="purge_create">
        <div class="vs-form-row">
            <label class="vs-label">刷新类型</label>
            <select name="purge_type" class="vs-input">
                <option value="purge_url">URL 刷新</option>
                <option value="purge_prefix">目录刷新</option>
                <option value="purge_host">Hostname 刷新</option>
                <option value="purge_all">全部刷新</option>
            </select>
        </div>
        <div class="vs-form-row">
            <label class="vs-label">目标（每行一条 URL 或目录）</label>
            <textarea name="targets" class="vs-textarea" rows="5" placeholder="https://example.com/a.js"></textarea>
        </div>
        <div class="vs-form-actions"><button type="submit" class="vs-btn vs-btn--primary">提交刷新</button></div>
    </form>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">预热缓存</h3>
    <form class="vs-form vs-edgeone-api-form" data-action="prefetch_create">
        <div class="vs-form-row">
            <label class="vs-label">预热 URL（每行一条）</label>
            <textarea name="targets" class="vs-textarea" rows="5"></textarea>
        </div>
        <div class="vs-form-actions"><button type="submit" class="vs-btn vs-btn--primary">提交预热</button></div>
    </form>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">最近刷新 / 预热记录（近 30 日）</h3>
    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip">请先选择站点</p>
    <?php else: ?>
        <h4 class="vs-form-subtitle">刷新</h4>
        <?php if ($purgeError !== ''): ?>
            <p class="vs-form-tip">加载失败：<?php echo vs_e($purgeError); ?></p>
        <?php elseif (count($purges) === 0): ?>
            <p class="vs-form-tip">暂无刷新记录</p>
        <?php else: ?>
            <?php vs_edgeone_render_api_data(array('Tasks' => $purges)); ?>
        <?php endif; ?>
        <h4 class="vs-form-subtitle">预热</h4>
        <?php if ($prefetchError !== ''): ?>
            <p class="vs-form-tip">加载失败：<?php echo vs_e($prefetchError); ?></p>
        <?php elseif (count($prefetches) === 0): ?>
            <p class="vs-form-tip">暂无预热记录</p>
        <?php else: ?>
            <?php vs_edgeone_render_api_data(array('Tasks' => $prefetches)); ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
vs_edgeone_page_end();
