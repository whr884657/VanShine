<?php
/**
 * 文件：admin/cdn/edgeone/includes/content-page.php
 * 作用：缓存管理页（刷新 / 预热）专用渲染与配额
 */

/**
 * @param array<int, array<string, mixed>> $plans
 * @param string                           $zoneId
 * @return array<string, mixed>|null
 */
function vs_edgeone_find_plan_for_zone(array $plans, $zoneId)
{
    foreach ($plans as $plan) {
        if (!is_array($plan)) {
            continue;
        }
        $zones = isset($plan['ZonesInfo']) && is_array($plan['ZonesInfo']) ? $plan['ZonesInfo'] : array();
        foreach ($zones as $z) {
            if (is_array($z) && isset($z['ZoneId']) && (string) $z['ZoneId'] === (string) $zoneId) {
                return $plan;
            }
        }
    }

    return null;
}

/**
 * @param array<string, mixed>|null $quotaData
 * @param string                    $type
 * @return array<string, int>|null
 */
function vs_edgeone_content_quota_item($quotaData, $type)
{
    if (!is_array($quotaData)) {
        return null;
    }
    foreach (array('PurgeQuota', 'PrefetchQuota') as $section) {
        if (!isset($quotaData[$section]) || !is_array($quotaData[$section])) {
            continue;
        }
        foreach ($quotaData[$section] as $item) {
            if (is_array($item) && isset($item['Type']) && (string) $item['Type'] === (string) $type) {
                return array(
                    'Batch'          => isset($item['Batch']) ? (int) $item['Batch'] : 0,
                    'Daily'          => isset($item['Daily']) ? (int) $item['Daily'] : 0,
                    'DailyAvailable' => isset($item['DailyAvailable']) ? (int) $item['DailyAvailable'] : 0,
                );
            }
        }
    }

    return null;
}

/**
 * @param string                    $planType
 * @param array<string, mixed>|null $quotaData
 * @return bool
 */
function vs_edgeone_zone_supports_prefetch($planType, $quotaData)
{
    if ($planType === 'plan-free') {
        return false;
    }
    $prefetch = vs_edgeone_content_quota_item($quotaData, 'prefetch_url');
    if ($prefetch === null) {
        return $planType !== 'plan-free' && $planType !== '';
    }

    return ($prefetch['Daily'] > 0 || $prefetch['DailyAvailable'] > 0 || $prefetch['Batch'] > 0);
}

/**
 * @param EdgeOne|null $eo
 * @param string       $zoneId
 * @return array{plan: array|null, quota: array|null, quota_error: string, supports_prefetch: bool}
 */
function vs_edgeone_fetch_cache_page_context($eo, $zoneId)
{
    $out = array(
        'plan'              => null,
        'quota'             => null,
        'quota_error'       => '',
        'supports_prefetch' => false,
    );

    if ($eo === null || $zoneId === '') {
        return $out;
    }

    $plansResult = vs_edgeone_try_call(function () use ($eo) {
        return $eo->billing->describePlans(array('Offset' => 0, 'Limit' => 100));
    }, array());
    $plans = ($plansResult['ok'] && isset($plansResult['data']['Plans']) && is_array($plansResult['data']['Plans']))
        ? $plansResult['data']['Plans']
        : array();
    $out['plan'] = vs_edgeone_find_plan_for_zone($plans, $zoneId);

    $quotaResult = vs_edgeone_try_call(function () use ($eo, $zoneId) {
        return $eo->content->describeContentQuota(array('ZoneId' => $zoneId));
    }, array());
    if ($quotaResult['ok']) {
        $out['quota'] = is_array($quotaResult['data']) ? $quotaResult['data'] : null;
    } else {
        $out['quota_error'] = $quotaResult['error'];
    }

    $planType = $out['plan'] && isset($out['plan']['PlanType']) ? (string) $out['plan']['PlanType'] : '';
    $out['supports_prefetch'] = vs_edgeone_zone_supports_prefetch($planType, $out['quota']);

    return $out;
}

/**
 * @param array<int, array<string, mixed>> $zones
 * @param string                           $zoneId
 * @param EdgeOne|null                     $eo
 * @return void
 */
function vs_edgeone_render_cache_zone_panel(array $zones, $zoneId, $eo)
{
    $cacheCtx = vs_edgeone_fetch_cache_page_context($eo, $zoneId);
    $plan = $cacheCtx['plan'];
    $quota = $cacheCtx['quota'];
    $planType = $plan && isset($plan['PlanType']) ? (string) $plan['PlanType'] : '';
    $planLabel = $planType !== '' ? vs_edgeone_plan_type_label($planType) : '';

    echo '<div class="vs-edgeone-cache-zone-head">';
    vs_edgeone_render_zone_picker($zones);

    if ($zoneId !== '') {
        echo '<div class="vs-edgeone-content-quota-strip" aria-label="本月刷新配额">';
        if ($planLabel !== '') {
            echo '<p class="vs-edgeone-content-quota-strip__plan">';
            echo '<span class="vs-edgeone-content-quota-strip__plan-label">当前套餐</span>';
            echo '<strong>' . vs_e($planLabel) . '</strong>';
            echo '</p>';
        }

        if ($cacheCtx['quota_error'] !== '') {
            echo '<p class="vs-form-tip">配额加载失败：' . vs_e($cacheCtx['quota_error']) . '</p>';
        } else {
            $types = array(
                'purge_url'    => 'URL 刷新',
                'purge_prefix' => '目录刷新',
                'purge_host'   => 'Hostname 刷新',
                'purge_all'    => '全部刷新',
            );
            if ($cacheCtx['supports_prefetch']) {
                $types['prefetch_url'] = '预热缓存';
            }

            echo '<div class="vs-edgeone-content-quota-grid">';
            foreach ($types as $typeKey => $typeLabel) {
                $item = vs_edgeone_content_quota_item($quota, $typeKey);
                $avail = $item ? $item['DailyAvailable'] : null;
                $daily = $item ? $item['Daily'] : null;
                echo '<article class="vs-edgeone-content-quota-item">';
                echo '<span class="vs-edgeone-content-quota-item__label">' . vs_e($typeLabel) . '</span>';
                if ($avail === null && $daily === null) {
                    echo '<strong class="vs-edgeone-content-quota-item__value">—</strong>';
                    echo '<span class="vs-edgeone-content-quota-item__hint">暂无数据</span>';
                } else {
                    echo '<strong class="vs-edgeone-content-quota-item__value">' . vs_e(vs_edgeone_format_number($avail)) . '</strong>';
                    echo '<span class="vs-edgeone-content-quota-item__hint">今日剩余 / 日配额 ' . vs_e(vs_edgeone_format_number($daily)) . '</span>';
                }
                echo '</article>';
            }
            echo '</div>';
        }
    } else {
        echo '<p class="vs-form-tip vs-edgeone-content-quota-strip__empty">选择站点后显示刷新与预热配额</p>';
    }

    echo '</div>';
}

/**
 * @param bool $supportsPrefetch
 * @return void
 */
function vs_edgeone_render_cache_task_panel($supportsPrefetch = true)
{
    echo '<div class="vs-panel vs-edgeone-cache-panel" id="edgeoneCachePanel">';
    echo '<h3 class="vs-panel__title">缓存任务</h3>';

    echo '<div class="vs-edgeone-cache-mode" role="tablist" aria-label="缓存任务类型">';
    echo '<button type="button" class="vs-edgeone-cache-mode__btn is-active" data-cache-mode="purge" role="tab" aria-selected="true">清除缓存</button>';
    if ($supportsPrefetch) {
        echo '<button type="button" class="vs-edgeone-cache-mode__btn" data-cache-mode="prefetch" role="tab" aria-selected="false">预热缓存</button>';
    } else {
        echo '<span class="vs-edgeone-cache-mode__placeholder">当前套餐不支持预热缓存</span>';
    }
    echo '</div>';

    echo '<form class="vs-form vs-edgeone-api-form vs-edgeone-cache-form is-active" id="edgeonePurgeForm" data-action="purge_create" data-cache-form="purge">';
    echo '<div class="vs-form-row vs-edgeone-purge-type-row">';
    echo '<label class="vs-label" for="edgeonePurgeType">刷新类型</label>';
    echo '<select name="purge_type" class="vs-input" id="edgeonePurgeType">';
    echo '<option value="purge_url">URL 刷新</option>';
    echo '<option value="purge_prefix">目录刷新</option>';
    echo '<option value="purge_host">Hostname 刷新</option>';
    echo '<option value="purge_all">全部刷新</option>';
    echo '</select>';
    echo '<div class="vs-edgeone-purge-hint" id="edgeonePurgeTypeTip" data-for="purge_url">';
    echo '<span class="vs-edgeone-purge-hint__icon" aria-hidden="true">i</span>';
    echo '<span class="vs-edgeone-purge-hint__text">每行填写完整 URL，例如 https://example.com/path/file.js</span>';
    echo '</div>';
    echo '</div>';
    echo '<div class="vs-form-row vs-edgeone-purge-targets" id="edgeonePurgeTargetsRow">';
    echo '<label class="vs-label" for="edgeonePurgeTargets">目标</label>';
    echo '<textarea name="targets" class="vs-textarea" id="edgeonePurgeTargets" rows="5" placeholder="https://example.com/a.js"></textarea>';
    echo '</div>';
    echo '<div class="vs-form-actions"><button type="submit" class="vs-btn vs-btn--primary">提交刷新</button></div>';
    echo '</form>';

    if ($supportsPrefetch) {
        echo '<form class="vs-form vs-edgeone-api-form vs-edgeone-cache-form" id="edgeonePrefetchForm" data-action="prefetch_create" data-cache-form="prefetch" hidden>';
        echo '<div class="vs-form-row">';
        echo '<label class="vs-label" for="edgeonePrefetchTargets">预热 URL</label>';
        echo '<textarea name="targets" class="vs-textarea" id="edgeonePrefetchTargets" rows="5" placeholder="https://example.com/static/app.js"></textarea>';
        echo '<div class="vs-edgeone-purge-hint vs-edgeone-purge-hint--neutral">';
        echo '<span class="vs-edgeone-purge-hint__icon" aria-hidden="true">i</span>';
        echo '<span class="vs-edgeone-purge-hint__text">每行一条完整 URL，提交后节点将提前缓存资源</span>';
        echo '</div>';
        echo '</div>';
        echo '<div class="vs-form-actions"><button type="submit" class="vs-btn vs-btn--primary">提交预热</button></div>';
        echo '</form>';
    }

    echo '</div>';
}
