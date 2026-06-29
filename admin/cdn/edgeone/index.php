<?php
/**
 * 文件：admin/cdn/edgeone/index.php
 * 作用：EdgeOne 概览仪表盘
 * @version 1.1.0
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'set_zone') {
        require __DIR__ . '/api.php';
        exit;
    }
    if (vs_edgeone_is_fragment_request()) {
        vs_edgeone_overview_filters_from_request($_POST);
    }
}

$ctx = vs_edgeone_page_start('cdn_edgeone', 'EdgeOne');
$zones = $ctx['zones'];
$eo = $ctx['eo'];
$filters = vs_edgeone_overview_filters_from_request();
$ranges = vs_edgeone_analytics_ranges();

$domainOptions = array();
if ($eo !== null && vs_edgeone_is_ready() && $filters['filter_zone'] !== '' && $filters['filter_zone'] !== '*') {
    $domainOptions = vs_edgeone_fetch_domain_names($eo, $filters['filter_zone']);
}
?>

<div class="vs-panel">
    <h3 class="vs-panel__title">查询条件</h3>
    <p class="vs-form-tip">参考腾讯云指标分析：时间范围 + 站点/域名 + 自定义 Filters.N。数据约有 10 分钟延迟。</p>

    <?php if (!vs_edgeone_is_ready()): ?>
        <p class="vs-form-tip vs-form-tip--highlight">请先完成 EdgeOne 配置。</p>
    <?php elseif (count($zones) === 0): ?>
        <p class="vs-form-tip">暂无站点，请前往「站点管理」创建。</p>
    <?php else: ?>
        <form method="post" class="vs-form vs-edgeone-query-form vs-edgeone-fragment-form vs-edgeone-overview-form" id="edgeoneOverviewForm">
            <div class="vs-edgeone-range-tabs" role="tablist" aria-label="时间范围">
                <?php foreach ($ranges as $key => $item): ?>
                    <label class="vs-edgeone-range-tabs__item<?php echo $key === $filters['range'] ? ' is-active' : ''; ?>">
                        <input type="radio" name="range" value="<?php echo vs_e($key); ?>"<?php echo $key === $filters['range'] ? ' checked' : ''; ?>>
                        <span><?php echo vs_e($item['label']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="vs-form-row vs-form-row--inline vs-edgeone-filter-grid">
                <div class="vs-form-col">
                    <label class="vs-label">站点</label>
                    <select name="filter_zone" class="vs-input" id="edgeoneFilterZone">
                        <option value="*"<?php echo $filters['filter_zone'] === '*' ? ' selected' : ''; ?>>全部站点</option>
                        <?php foreach ($zones as $zone):
                            $zid = isset($zone['ZoneId']) ? (string) $zone['ZoneId'] : '';
                        ?>
                            <option value="<?php echo vs_e($zid); ?>"<?php echo $filters['filter_zone'] === $zid ? ' selected' : ''; ?>>
                                <?php echo vs_e(vs_edgeone_zone_display_name($zone)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vs-form-col vs-form-col--domain">
                    <label class="vs-label">域名 (Host)</label>
                    <select name="filter_domain" class="vs-input" id="edgeoneFilterDomain"<?php echo $filters['filter_zone'] === '*' ? ' disabled' : ''; ?>>
                        <option value="">全部域名</option>
                        <?php foreach ($domainOptions as $domainName): ?>
                            <option value="<?php echo vs_e($domainName); ?>"<?php echo $filters['filter_domain'] === $domainName ? ' selected' : ''; ?>>
                                <?php echo vs_e($domainName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vs-form-col vs-form-col--actions">
                    <button type="submit" class="vs-btn vs-btn--primary">查询</button>
                </div>
            </div>

            <?php vs_edgeone_render_overview_custom_filters($filters); ?>
        </form>
    <?php endif; ?>
</div>

<?php if (vs_edgeone_is_ready() && count($zones) > 0): ?>
<div class="vs-edgeone-overview vs-edgeone-overview--loading" id="edgeoneDashboardHost">
    <aside class="vs-edgeone-overview__sidebar" id="edgeoneSummaryHost">
        <article class="vs-edgeone-kpi vs-edgeone-kpi--sidebar vs-edgeone-kpi--loading">
            <span class="vs-edgeone-kpi__label">加载中</span>
            <strong class="vs-edgeone-kpi__value">—</strong>
        </article>
    </aside>
    <div class="vs-edgeone-overview__main">
        <div class="vs-panel vs-edgeone-chart-panel vs-edgeone-chart-panel--loading">
            <p class="vs-form-tip">数据加载中…</p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
vs_edgeone_page_end();
