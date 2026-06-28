<?php
/**
 * 文件：admin/cdn/edgeone/index.php
 * 作用：EdgeOne 概览仪表盘
 * @version 1.0.4
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
$eo = $ctx['eo'];
$filters = vs_edgeone_overview_filters_from_request();
$rangePreset = vs_edgeone_analytics_range_preset($filters['range']);
$intervals = vs_edgeone_analytics_intervals();
$ranges = vs_edgeone_analytics_ranges();

$charts = array();
$quotaBundle = array('plans' => array(), 'usage' => array(), 'content_quota' => array());
$domainOptions = array();

if ($eo !== null && vs_edgeone_is_ready()) {
    if ($filters['filter_zone'] !== '' && $filters['filter_zone'] !== '*') {
        $domainOptions = vs_edgeone_fetch_domain_names($eo, $filters['filter_zone']);
    }

    $charts = vs_edgeone_fetch_overview_charts($eo, $zones, $filters);
    $quotaBundle = vs_edgeone_fetch_overview_quota($eo, $zones);
}
?>

<div class="vs-panel">
    <h3 class="vs-panel__title">数据概览</h3>
    <p class="vs-form-tip">账号下全部站点的统计数据。统一筛选后，下方所有统计图同步更新。数据约有 10 分钟延迟，时间范围最长 31 天。</p>

    <?php if (!vs_edgeone_is_ready()): ?>
        <p class="vs-form-tip vs-form-tip--highlight">请先完成 EdgeOne 配置。</p>
    <?php elseif (count($zones) === 0): ?>
        <p class="vs-form-tip">暂无站点，请前往「站点管理」创建。</p>
    <?php else: ?>
        <form method="get" class="vs-form vs-edgeone-query-form vs-edgeone-fragment-form" id="edgeoneOverviewForm">
            <div class="vs-form-row vs-form-row--inline">
                <div class="vs-form-col">
                    <label class="vs-label">时间范围</label>
                    <select name="range" class="vs-input">
                        <?php foreach ($ranges as $key => $item): ?>
                            <option value="<?php echo vs_e($key); ?>"<?php echo $key === $filters['range'] ? ' selected' : ''; ?>>
                                <?php echo vs_e($item['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vs-form-col">
                    <label class="vs-label">时间粒度</label>
                    <select name="interval" class="vs-input">
                        <?php foreach ($intervals as $key => $label): ?>
                            <option value="<?php echo vs_e($key); ?>"<?php echo $key === $filters['interval'] ? ' selected' : ''; ?>>
                                <?php echo vs_e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vs-form-col">
                    <label class="vs-label">站点</label>
                    <select name="filter_zone" class="vs-input" id="edgeoneFilterZone">
                        <option value="*"<?php echo $filters['filter_zone'] === '*' ? ' selected' : ''; ?>>全部站点（分线 + 总计）</option>
                        <?php foreach ($zones as $zone):
                            $zid = isset($zone['ZoneId']) ? (string) $zone['ZoneId'] : '';
                        ?>
                            <option value="<?php echo vs_e($zid); ?>"<?php echo $filters['filter_zone'] === $zid ? ' selected' : ''; ?>>
                                <?php echo vs_e(vs_edgeone_zone_display_name($zone)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vs-form-col">
                    <label class="vs-label">域名</label>
                    <select name="filter_domain" class="vs-input" id="edgeoneFilterDomain"<?php echo $filters['filter_zone'] === '*' ? ' disabled' : ''; ?>>
                        <option value="">全部域名</option>
                        <?php foreach ($domainOptions as $domainName): ?>
                            <option value="<?php echo vs_e($domainName); ?>"<?php echo $filters['filter_domain'] === $domainName ? ' selected' : ''; ?>>
                                <?php echo vs_e($domainName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($filters['filter_zone'] === '*'): ?>
                        <p class="vs-form-tip">筛选单个站点后可选择域名</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="vs-form-actions">
                <button type="submit" class="vs-btn vs-btn--primary">查询</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if (vs_edgeone_is_ready() && count($zones) > 0): ?>
<div class="vs-panel">
    <h3 class="vs-panel__title">站点概览</h3>
    <p class="vs-form-tip">共 <?php echo count($zones); ?> 个站点 · <?php echo vs_e($rangePreset['label']); ?></p>
    <div class="vs-edgeone-stat-grid">
        <?php foreach ($zones as $zone):
            $zid = isset($zone['ZoneId']) ? (string) $zone['ZoneId'] : '';
        ?>
            <article class="vs-edgeone-stat-card">
                <h4><?php echo vs_e(vs_edgeone_zone_display_name($zone)); ?></h4>
                <p class="vs-form-tip">域名：<?php echo vs_e(isset($zone['ZoneName']) ? $zone['ZoneName'] : '-'); ?></p>
                <p class="vs-form-tip">ZoneId：<code><?php echo vs_e($zid); ?></code></p>
                <p>运行状态：<?php vs_edgeone_render_zone_runtime_badge($zone); ?></p>
                <p>加速状态：<?php echo vs_e(vs_edgeone_translate('ActiveStatus', isset($zone['ActiveStatus']) ? $zone['ActiveStatus'] : '')); ?></p>
                <p>接入方式：<?php echo vs_e(vs_edgeone_translate('Type', isset($zone['Type']) ? $zone['Type'] : '')); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<div class="vs-edgeone-chart-grid">
    <?php foreach ($charts as $metric => $chart):
        $meta = $chart['meta'];
        $hasData = false;
        foreach ($chart['series'] as $serie) {
            if (!empty($serie['points'])) {
                $hasData = true;
                break;
            }
        }
    ?>
        <div class="vs-panel vs-edgeone-chart-panel">
            <h3 class="vs-panel__title"><?php echo vs_e($meta['label']); ?></h3>
            <?php if (!empty($chart['error'])): ?>
                <p class="vs-form-tip">查询失败：<?php echo vs_e($chart['error']); ?></p>
            <?php elseif (!$hasData): ?>
                <p class="vs-form-tip">该条件下暂无数据</p>
            <?php else: ?>
                <?php if ($chart['sum'] !== null): ?>
                    <p class="vs-edgeone-metric-avg">区间合计：<strong><?php echo vs_e(vs_edgeone_format_metric_value($chart['sum'], $meta['unit'])); ?></strong></p>
                <?php endif; ?>
                <?php vs_edgeone_render_multi_line_chart(array(
                    'unit'   => $meta['unit'],
                    'series' => $chart['series'],
                )); ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">套餐与配额</h3>
    <p class="vs-form-tip">展示账号下各站点绑定的套餐配额与内容刷新/预热配额。</p>
    <?php vs_edgeone_render_overview_quota_sections(
        $zones,
        $quotaBundle['plans'],
        $quotaBundle['usage'],
        $quotaBundle['content_quota']
    ); ?>
</div>
<?php endif; ?>

<?php
vs_edgeone_page_end();
