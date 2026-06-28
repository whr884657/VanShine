<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'set_zone') {
        require __DIR__ . '/api.php';
        exit;
    }
    if (vs_edgeone_is_fragment_request()) {
        vs_edgeone_analytics_filters_from_request($_POST);
    }
}

$ctx = vs_edgeone_page_start('cdn_edgeone_analytics', 'EdgeOne · 数据分析');
$zoneId = $ctx['zone_id'];
$eo = $ctx['eo'];

$af = vs_edgeone_analytics_filters_from_request();
$source = $af['source'];
$metric = $af['metric'];
$rangeKey = $af['range'];
$interval = $af['interval'];

$metrics = vs_edgeone_metrics_for_source($source);
$ranges = vs_edgeone_analytics_ranges();
$rangePreset = vs_edgeone_analytics_range_preset($rangeKey);
$intervals = vs_edgeone_analytics_intervals();

$meta = vs_edgeone_metric_meta($metric, $source);
$queryError = '';
$series = array();
$summary = array('avg' => null, 'max' => null, 'sum' => null, 'peak' => null);

if ($eo !== null && $zoneId !== '') {
    $result = vs_edgeone_try_call(function () use ($eo, $zoneId, $source, $metric, $interval, $rangeKey) {
        return vs_edgeone_query_analytics($eo, $zoneId, $source, $metric, $interval, $rangeKey);
    });
    if (!$result['ok']) {
        $queryError = $result['error'];
    } else {
        $series = vs_edgeone_extract_timing_series($result['data']);
        if (count($series) > 0) {
            $first = $series[0];
            $summary['avg'] = isset($first['avg']) ? $first['avg'] : null;
            $summary['max'] = isset($first['max']) ? $first['max'] : null;
            $summary['sum'] = isset($first['sum']) ? $first['sum'] : null;
            if (count($first['points']) > 0) {
                $peak = 0.0;
                foreach ($first['points'] as $p) {
                    if ($p['value'] > $peak) {
                        $peak = $p['value'];
                    }
                }
                $summary['peak'] = $peak;
            }
        }
    }
}
?>

<div class="vs-panel">
    <h3 class="vs-panel__title">七层 / 回源 / 四层数据分析</h3>
    <p class="vs-form-tip">数据约有 10 分钟延迟，建议查询当前时间 10 分钟以前的数据。时间范围不超过 31 天。</p>

    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip vs-form-tip--highlight">请先在上方选择站点后再查询。</p>
    <?php else: ?>
        <form method="post" class="vs-form vs-edgeone-query-form vs-edgeone-fragment-form">
            <div class="vs-form-row vs-form-row--inline">
                <div class="vs-form-col">
                    <label class="vs-label">数据类型</label>
                    <select name="source" class="vs-input">
                        <option value="l7"<?php echo $source === 'l7' ? ' selected' : ''; ?>>七层访问分析</option>
                        <option value="origin"<?php echo $source === 'origin' ? ' selected' : ''; ?>>回源分析</option>
                        <option value="l4"<?php echo $source === 'l4' ? ' selected' : ''; ?>>四层流量</option>
                    </select>
                </div>
                <div class="vs-form-col">
                    <label class="vs-label">指标</label>
                    <select name="metric" class="vs-input">
                        <?php foreach ($metrics as $key => $item): ?>
                            <option value="<?php echo vs_e($key); ?>"<?php echo $key === $metric ? ' selected' : ''; ?>>
                                <?php echo vs_e($item['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vs-form-col">
                    <label class="vs-label">时间范围</label>
                    <select name="range" class="vs-input">
                        <?php foreach ($ranges as $key => $item): ?>
                            <option value="<?php echo vs_e($key); ?>"<?php echo $key === $rangeKey ? ' selected' : ''; ?>>
                                <?php echo vs_e($item['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vs-form-col">
                    <label class="vs-label">时间粒度</label>
                    <select name="interval" class="vs-input">
                        <?php foreach ($intervals as $key => $label): ?>
                            <option value="<?php echo vs_e($key); ?>"<?php echo $key === $interval ? ' selected' : ''; ?>>
                                <?php echo vs_e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="vs-form-actions">
                <button type="submit" class="vs-btn vs-btn--primary">查询</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if ($zoneId !== ''): ?>
<div class="vs-panel">
    <h3 class="vs-panel__title"><?php echo vs_e($meta['label']); ?> · <?php echo vs_e($rangePreset['label']); ?></h3>

    <?php if ($queryError !== ''): ?>
        <p class="vs-form-tip">查询失败：<?php echo vs_e($queryError); ?></p>
    <?php elseif (count($series) === 0 || count($series[0]['points']) === 0): ?>
        <p class="vs-form-tip">该条件下暂无数据，可尝试扩大时间范围或更换指标。</p>
    <?php else: ?>
        <div class="vs-edgeone-kpi-grid">
            <article class="vs-edgeone-kpi">
                <span class="vs-edgeone-kpi__label">合计</span>
                <strong class="vs-edgeone-kpi__value"><?php echo vs_e(vs_edgeone_format_metric_value($summary['sum'], $meta['unit'])); ?></strong>
            </article>
            <article class="vs-edgeone-kpi">
                <span class="vs-edgeone-kpi__label">平均值</span>
                <strong class="vs-edgeone-kpi__value"><?php echo vs_e(vs_edgeone_format_metric_value($summary['avg'], $meta['unit'])); ?></strong>
            </article>
            <article class="vs-edgeone-kpi">
                <span class="vs-edgeone-kpi__label">峰值</span>
                <strong class="vs-edgeone-kpi__value"><?php echo vs_e(vs_edgeone_format_metric_value($summary['peak'], $meta['unit'])); ?></strong>
            </article>
            <article class="vs-edgeone-kpi">
                <span class="vs-edgeone-kpi__label">最大值（API）</span>
                <strong class="vs-edgeone-kpi__value"><?php echo vs_e(vs_edgeone_format_metric_value($summary['max'], $meta['unit'])); ?></strong>
            </article>
        </div>

        <?php
        vs_edgeone_render_line_chart(array(
            'title'  => $meta['label'],
            'unit'   => $meta['unit'],
            'points' => $series[0]['points'],
        ));
        ?>

        <div class="vs-table-wrap vs-edgeone-table-wrap">
            <table class="vs-table vs-edgeone-data-table">
                <thead>
                    <tr>
                        <th>时间</th>
                        <th><?php echo vs_e($meta['label']); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($series[0]['points'] as $point): ?>
                    <tr>
                        <td><?php echo vs_e(vs_edgeone_format_timestamp($point['ts'])); ?></td>
                        <td><?php echo vs_e(vs_edgeone_format_metric_value($point['value'], $meta['unit'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php
vs_edgeone_page_end();
