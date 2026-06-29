<?php
/**
 * 文件：admin/cdn/edgeone/billing.php
 * 页面：EdgeOne · 套餐计费
 * 路由：/admin/cdn/edgeone/billing.php
 * 菜单：cdn_edgeone_billing
 *
 * 作用：套餐卡片、站点用量、计费趋势与配额进度
 *
 * 说明：筛选条件存 Session；底部展示全部站点套餐与刷新/预热配额
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
        vs_edgeone_billing_filters_from_request($_POST);
    }
}

$ctx = vs_edgeone_page_start('cdn_edgeone_billing', 'EdgeOne · 套餐计费');
$zoneId = $ctx['zone_id'];
$zones = $ctx['zones'];
$eo = $ctx['eo'];

$plans = array();
$plansError = '';
$usageToday = array();
$usageMonth = array();
$usageTrend = array();
$usageError = '';

$bf = vs_edgeone_billing_filters_from_request();
$billingMetric = $bf['metric'];
$rangeKey = $bf['range'];

$billingMetrics = vs_edgeone_billing_metrics();
$billingMeta = vs_edgeone_metric_meta($billingMetric, 'l7');
$rangePreset = vs_edgeone_analytics_range_preset($rangeKey);

if ($eo !== null) {
    $planResult = vs_edgeone_try_call(function () use ($eo) {
        return $eo->billing->describePlans(array('Offset' => 0, 'Limit' => 100));
    });
    if ($planResult['ok']) {
        $plans = isset($planResult['data']['Plans']) && is_array($planResult['data']['Plans'])
            ? $planResult['data']['Plans']
            : array();
    } else {
        $plansError = $planResult['error'];
    }

    if ($zoneId !== '') {
        $todayWindow = vs_edgeone_billing_window('today');
        $monthWindow = vs_edgeone_billing_window('month');

        foreach (array(
            'acc_flux'    => '今日加速流量',
            'sec_request' => '今日安全请求',
            'l7Flow_hitRequest' => '今日缓存命中',
        ) as $mKey => $label) {
            if ($mKey === 'l7Flow_hitRequest') {
                $hitResult = vs_edgeone_try_call(function () use ($eo, $zoneId, $todayWindow) {
                    $params = array_merge($todayWindow, array(
                        'ZoneIds'     => vs_edgeone_zone_ids($zoneId),
                        'MetricNames' => array('l7Flow_request'),
                        'Filters'     => array(
                            vs_edgeone_analytics_filter('cacheType', 'hit', 'equals'),
                        ),
                    ));
                    return $eo->analytics->describeTimingL7AnalysisData($params);
                });
                if ($hitResult['ok']) {
                    $hitSeries = vs_edgeone_extract_timing_series($hitResult['data']);
                    $sum = 0.0;
                    if (count($hitSeries) > 0 && isset($hitSeries[0]['sum'])) {
                        $sum = (float) $hitSeries[0]['sum'];
                    }
                    $usageToday[$mKey] = array(
                        'label' => $label,
                        'value' => vs_edgeone_format_metric_value($sum, 'count'),
                    );
                }
                continue;
            }

            $metricName = $mKey;
            $unit = isset($billingMetrics[$metricName]) ? $billingMetrics[$metricName]['unit'] : 'number';
            $todayResult = vs_edgeone_try_call(function () use ($eo, $zoneId, $metricName, $todayWindow) {
                return vs_edgeone_query_billing_series($eo, $zoneId, $metricName, $todayWindow);
            });
            if ($todayResult['ok']) {
                $rows = isset($todayResult['data']['Data']) && is_array($todayResult['data']['Data'])
                    ? $todayResult['data']['Data']
                    : array();
                $usageToday[$mKey] = array(
                    'label' => $label,
                    'value' => vs_edgeone_format_metric_value(vs_edgeone_sum_series_values($rows), $unit),
                );
            }

            $monthResult = vs_edgeone_try_call(function () use ($eo, $zoneId, $metricName, $monthWindow) {
                return vs_edgeone_query_billing_series($eo, $zoneId, $metricName, $monthWindow);
            });
            if ($monthResult['ok'] && $mKey === 'acc_flux') {
                $rows = isset($monthResult['data']['Data']) && is_array($monthResult['data']['Data'])
                    ? $monthResult['data']['Data']
                    : array();
                $usageMonth['acc_flux'] = array(
                    'label' => '本月加速流量',
                    'value' => vs_edgeone_format_metric_value(vs_edgeone_sum_series_values($rows), 'bytes'),
                );
            }
        }

        $trendResult = vs_edgeone_try_call(function () use ($eo, $zoneId, $billingMetric, $rangeKey) {
            $range = vs_edgeone_analytics_range_preset($rangeKey);
            return vs_edgeone_query_billing_series($eo, $zoneId, $billingMetric, $range['times']);
        });
        if (!$trendResult['ok']) {
            $usageError = $trendResult['error'];
        } else {
            $rows = isset($trendResult['data']['Data']) && is_array($trendResult['data']['Data'])
                ? $trendResult['data']['Data']
                : array();
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $ts = isset($row['Time']) ? strtotime((string) $row['Time']) : 0;
                $usageTrend[] = array(
                    'ts'    => $ts > 0 ? $ts : 0,
                    'value' => (float) (isset($row['Value']) ? $row['Value'] : 0),
                );
            }
        }
    }
}
?>

<div class="vs-panel">
    <h3 class="vs-panel__title">我的套餐</h3>
    <?php if ($plansError !== ''): ?>
        <p class="vs-form-tip">加载失败：<?php echo vs_e($plansError); ?></p>
    <?php elseif (count($plans) === 0): ?>
        <p class="vs-form-tip">暂无套餐，请前往腾讯云 EdgeOne 控制台购买。</p>
    <?php else: ?>
        <?php vs_edgeone_render_plans_dashboard($plans, $zoneId); ?>
    <?php endif; ?>
</div>

<?php if ($zoneId === ''): ?>
<div class="vs-panel">
    <h3 class="vs-panel__title">站点用量</h3>
    <p class="vs-form-tip vs-form-tip--highlight">请先在上方选择站点后查看今日 / 本月用量与趋势。</p>
</div>
<?php else: ?>
<div class="vs-panel">
    <h3 class="vs-panel__title">站点用量概览</h3>
    <div class="vs-edgeone-kpi-grid">
        <?php
        $kpiOrder = array('acc_flux', 'l7Flow_hitRequest', 'sec_request');
        foreach ($kpiOrder as $k):
            if (!isset($usageToday[$k])) {
                continue;
            }
            $item = $usageToday[$k];
        ?>
            <article class="vs-edgeone-kpi">
                <span class="vs-edgeone-kpi__label"><?php echo vs_e($item['label']); ?></span>
                <strong class="vs-edgeone-kpi__value"><?php echo vs_e($item['value']); ?></strong>
            </article>
        <?php endforeach; ?>
        <?php if (isset($usageMonth['acc_flux'])): ?>
            <article class="vs-edgeone-kpi">
                <span class="vs-edgeone-kpi__label"><?php echo vs_e($usageMonth['acc_flux']['label']); ?></span>
                <strong class="vs-edgeone-kpi__value"><?php echo vs_e($usageMonth['acc_flux']['value']); ?></strong>
            </article>
        <?php endif; ?>
    </div>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">计费用量趋势</h3>
    <form method="post" class="vs-form vs-edgeone-query-form vs-edgeone-fragment-form">
        <div class="vs-form-row vs-form-row--inline">
            <div class="vs-form-col">
                <label class="vs-label">计费指标</label>
                <select name="metric" class="vs-input">
                    <?php foreach ($billingMetrics as $key => $item): ?>
                        <option value="<?php echo vs_e($key); ?>"<?php echo $key === $billingMetric ? ' selected' : ''; ?>>
                            <?php echo vs_e($item['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="vs-form-col">
                <label class="vs-label">时间范围</label>
                <select name="range" class="vs-input">
                    <?php foreach (vs_edgeone_analytics_ranges() as $key => $item): ?>
                        <option value="<?php echo vs_e($key); ?>"<?php echo $key === $rangeKey ? ' selected' : ''; ?>>
                            <?php echo vs_e($item['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="vs-form-actions">
            <button type="submit" class="vs-btn vs-btn--primary">查询</button>
        </div>
    </form>

    <?php if ($usageError !== ''): ?>
        <p class="vs-form-tip">查询失败：<?php echo vs_e($usageError); ?></p>
    <?php elseif (count($usageTrend) === 0): ?>
        <p class="vs-form-tip">暂无计费趋势数据。</p>
    <?php else: ?>
        <?php
        $trendSum = 0.0;
        $trendPeak = 0.0;
        foreach ($usageTrend as $p) {
            $trendSum += $p['value'];
            if ($p['value'] > $trendPeak) {
                $trendPeak = $p['value'];
            }
        }
        ?>
        <div class="vs-edgeone-kpi-grid">
            <article class="vs-edgeone-kpi">
                <span class="vs-edgeone-kpi__label">区间合计</span>
                <strong class="vs-edgeone-kpi__value"><?php echo vs_e(vs_edgeone_format_metric_value($trendSum, $billingMeta['unit'])); ?></strong>
            </article>
            <article class="vs-edgeone-kpi">
                <span class="vs-edgeone-kpi__label">区间峰值</span>
                <strong class="vs-edgeone-kpi__value"><?php echo vs_e(vs_edgeone_format_metric_value($trendPeak, $billingMeta['unit'])); ?></strong>
            </article>
        </div>
        <?php
        vs_edgeone_render_line_chart(array(
            'title'  => $billingMeta['label'],
            'unit'   => $billingMeta['unit'],
            'points' => $usageTrend,
        ));
        ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($eo !== null && count($zones) > 0): ?>
<div class="vs-panel">
    <h3 class="vs-panel__title">套餐配额与内容配额</h3>
    <p class="vs-form-tip">展示账号下各站点绑定的套餐流量/请求配额，以及内容刷新、预热配额。</p>
    <?php
    $quotaBundle = vs_edgeone_fetch_overview_quota($eo, $zones);
    vs_edgeone_render_overview_quota_sections(
        $zones,
        isset($quotaBundle['plans']) ? $quotaBundle['plans'] : array(),
        isset($quotaBundle['usage']) ? $quotaBundle['usage'] : array(),
        isset($quotaBundle['content_quota']) ? $quotaBundle['content_quota'] : array()
    );
    ?>
</div>
<?php endif; ?>

<?php
vs_edgeone_page_end();
