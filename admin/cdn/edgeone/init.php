<?php
/**
 * 文件：admin/cdn/edgeone/init.php
 * 作用：EdgeOne 后台模块公共引导与 Session 站点上下文
 *
 * 说明：
 * - 加载 EdgeOne SDK、各 includes 辅助文件与页面渲染函数
 * - vs_edgeone_is_ready() 检查凭证与开关；vs_edgeone_client() 创建 API 客户端
 * - 所有 edgeone/*.php 页面入口均 require 本文件
 */

require_once dirname(__DIR__, 2) . '/init.php';
require_once VS_ROOT . '/core/cdn/edgeone/EdgeOne.php';

EdgeOne::load();
TencentCloudConfig::migrateLegacyIfNeeded();
require_once __DIR__ . '/includes/api-helpers.php';
require_once __DIR__ . '/includes/data-view.php';
require_once __DIR__ . '/includes/filter-builder.php';
require_once __DIR__ . '/includes/kpi-icons.php';
require_once __DIR__ . '/includes/flux-dimension.php';
require_once __DIR__ . '/includes/overview-kpi.php';
require_once __DIR__ . '/includes/zones-page.php';
require_once __DIR__ . '/includes/domains-page.php';
require_once __DIR__ . '/includes/rules-page.php';
require_once __DIR__ . '/includes/rules-catalog.php';
require_once __DIR__ . '/includes/rules-editor.php';
require_once __DIR__ . '/includes/metrics.php';

/** @var string */
const VS_EDGEONE_ACTIVE_MENU = 'cdn_edgeone';

/**
 * @return bool
 */
function vs_edgeone_is_ready()
{
    return TencentCloudConfig::hasCredentials() && EdgeOneOptions::isEnabled();
}

/**
 * @return EdgeOne|null
 */
function vs_edgeone_client()
{
    if (!vs_edgeone_is_ready()) {
        return null;
    }

    try {
        return EdgeOne::create();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * @return string
 */
function vs_edgeone_selected_zone()
{
    return isset($_SESSION['vs_edgeone_zone_id']) ? (string) $_SESSION['vs_edgeone_zone_id'] : '';
}

/**
 * @param string $zoneId
 * @return void
 */
function vs_edgeone_set_zone($zoneId)
{
    $_SESSION['vs_edgeone_zone_id'] = trim($zoneId);
}

/**
 * @return void
 */
function vs_edgeone_render_setup_notice()
{
    if (vs_edgeone_is_ready()) {
        return;
    }

    $base = vs_base_url();
    echo '<div class="vs-panel vs-alert vs-alert--warning">';
    echo '<p><strong>EdgeOne 尚未就绪</strong></p>';
    if (!TencentCloudConfig::hasCredentials()) {
        echo '<p>请先在 <a href="' . vs_e($base) . '/admin/settings.php">系统设置 → CDN 配置</a> 填写腾讯云 SecretId / SecretKey。</p>';
    } else {
        echo '<p>请先在 <a href="' . vs_e($base) . '/admin/settings.php">系统设置 → CDN 配置</a> 启用腾讯云 EdgeOne。</p>';
    }
    echo '</div>';
}

/**
 * @param array<string, mixed> $zone
 * @return string
 */
function vs_edgeone_zone_display_name(array $zone)
{
    $alias = trim(isset($zone['AliasZoneName']) ? (string) $zone['AliasZoneName'] : '');
    $name = trim(isset($zone['ZoneName']) ? (string) $zone['ZoneName'] : '');

    if ($alias !== '' && $name !== '') {
        return $alias . '（' . $name . '）';
    }
    if ($alias !== '') {
        return $alias;
    }
    if ($name !== '') {
        return $name;
    }

    return isset($zone['ZoneId']) ? (string) $zone['ZoneId'] : '';
}

/**
 * 综合 API 字段判断站点实际运行状态（避免 CNAME 接入时 Status=pending 误显示为待配置）
 *
 * @param array<string, mixed> $zone
 * @return array{label: string, value: string, class: string}
 */
function vs_edgeone_zone_runtime_status(array $zone)
{
    $status = isset($zone['Status']) ? strtolower((string) $zone['Status']) : '';
    $active = isset($zone['ActiveStatus']) ? strtolower((string) $zone['ActiveStatus']) : '';
    $cname = isset($zone['CnameStatus']) ? strtolower((string) $zone['CnameStatus']) : '';

    if ($active === 'active' || ($status === 'active' && $active !== 'inactive')) {
        return array('label' => '运行中', 'value' => 'active', 'class' => 'is-success');
    }
    if ($status === 'active') {
        return array('label' => '已生效', 'value' => 'active', 'class' => 'is-success');
    }
    if ($status === 'initializing') {
        return array('label' => '初始化中', 'value' => 'initializing', 'class' => 'is-warning');
    }
    if ($status === 'deactivated') {
        return array('label' => '已停用', 'value' => 'deactivated', 'class' => 'is-danger');
    }
    if ($status === 'moved') {
        return array('label' => '已迁移', 'value' => 'moved', 'class' => 'is-muted');
    }
    if ($status === 'pending') {
        $type = isset($zone['Type']) ? strtolower((string) $zone['Type']) : '';
        if ($active === 'active' || $cname === 'finished' || $type === 'partial') {
            return array('label' => '运行中', 'value' => 'active', 'class' => 'is-success');
        }
        return array('label' => '待配置', 'value' => 'pending', 'class' => 'is-warning');
    }

    $raw = vs_edgeone_translate('Status', $status);
    return array(
        'label' => $raw,
        'value' => $status !== '' ? $status : 'unknown',
        'class' => vs_edgeone_status_badge_class('Status', $status),
    );
}

/**
 * @param array<string, mixed> $zone
 * @return void
 */
function vs_edgeone_render_zone_runtime_badge(array $zone)
{
    $runtime = vs_edgeone_zone_runtime_status($zone);
    echo '<span class="vs-edgeone-badge ' . vs_e($runtime['class']) . '">' . vs_e($runtime['label']) . '</span>';
}

/**
 * @return bool
 */
function vs_edgeone_is_fragment_request()
{
    if (isset($_POST['fragment']) && (string) $_POST['fragment'] === '1') {
        return true;
    }

    return isset($_GET['fragment']) && (string) $_GET['fragment'] === '1';
}

/**
 * @param string $group
 * @param mixed  $value
 * @return string
 */
function vs_edgeone_translate($group, $value)
{
    $value = (string) $value;
    $maps = array(
        'ActiveStatus' => array(
            'active'   => '已启用',
            'inactive' => '未启用',
        ),
        'Status' => array(
            'active'      => '已生效',
            'pending'     => '待配置',
            'deactivated' => '已停用',
            'moved'       => '已迁移',
            'initializing'=> '初始化中',
        ),
        'Type' => array(
            'full'            => 'NS 接入',
            'partial'         => 'CNAME 接入',
            'noDomainAccess'  => '无域名接入',
            'dnsPodAccess'    => 'DNSPod 托管',
            'pages'           => 'Pages 部署',
        ),
        'CnameStatus' => array(
            'finished' => '已完成',
            'pending'  => '待完成',
        ),
        'Area' => array(
            'mainland' => '中国大陆',
            'overseas' => '全球（不含中国大陆）',
            'global'   => '全球',
        ),
        'DomainStatus' => array(
            'online'  => '已生效',
            'offline' => '已停用',
            'process' => '部署中',
        ),
        'CertStatus' => array(
            'deployed' => '已部署',
            'processing' => '部署中',
            'failed' => '部署失败',
        ),
        'VerificationStatus' => array(
            'finished' => '已完成',
            'pending'  => '待完成',
            'failed'   => '失败',
        ),
        'TaskStatus' => array(
            'success'    => '成功',
            'processing' => '处理中',
            'failed'     => '失败',
            'timeout'    => '超时',
            'completed'  => '成功',
            'finished'   => '成功',
            'pending'    => '处理中',
            'process'    => '处理中',
        ),
    );

    if (isset($maps[$group][$value])) {
        return $maps[$group][$value];
    }

    return $value !== '' ? $value : '-';
}

/**
 * @param EdgeOne $eo
 * @return array<int, array<string, mixed>>
 */
function vs_edgeone_fetch_zones(EdgeOne $eo)
{
    $resp = $eo->zone->describeZones(array('Offset' => 0, 'Limit' => 200));
    return isset($resp['Zones']) && is_array($resp['Zones']) ? $resp['Zones'] : array();
}

/**
 * @param array<int, array<string, mixed>> $zones
 * @return void
 */
function vs_edgeone_render_zone_picker(array $zones)
{
    $selected = vs_edgeone_selected_zone();
    echo '<form method="post" class="vs-edgeone-zone-bar" id="edgeoneZoneForm">';
    echo '<input type="hidden" name="action" value="set_zone">';
    echo '<label class="vs-label">当前站点</label>';
    echo '<select name="zone_id" class="vs-input vs-edgeone-zone-select">';
    echo '<option value="">— 请选择站点 —</option>';
    foreach ($zones as $zone) {
        $id = isset($zone['ZoneId']) ? $zone['ZoneId'] : '';
        $label = vs_edgeone_zone_display_name($zone);
        $sel = $id === $selected ? ' selected' : '';
        echo '<option value="' . vs_e($id) . '"' . $sel . '>' . vs_e($label) . '</option>';
    }
    echo '</select>';
    echo '<button type="submit" class="vs-btn vs-btn--default">切换站点</button>';
    echo '</form>';
}

/**
 * @param callable(): mixed $callback
 * @param mixed             $default
 * @return array{ok: bool, data: mixed, error: string}
 */
function vs_edgeone_try_call($callback, $default = null)
{
    try {
        return array('ok' => true, 'data' => call_user_func($callback), 'error' => '');
    } catch (Exception $e) {
        return array('ok' => false, 'data' => $default, 'error' => $e->getMessage());
    }
}

/**
 * @param string $error
 * @return void
 */
function vs_edgeone_render_error($error)
{
    if ($error === '') {
        return;
    }
    echo '<div class="vs-panel vs-alert vs-alert--error">' . vs_e($error) . '</div>';
}

/**
 * 缓存管理类查询常用时间范围
 *
 * @param int $days
 * @return array{StartTime: string, EndTime: string}
 */
function vs_edgeone_time_range($days = 30)
{
    return array(
        'StartTime' => date('Y-m-d\T00:00:00+08:00', strtotime('-' . (int) $days . ' days')),
        'EndTime'   => date('Y-m-d\T23:59:59+08:00'),
    );
}

/**
 * DescribeBillingData 常用参数
 *
 * @param int          $days
 * @param string       $metric
 * @param array|string|null $zoneIds 站点 ID 列表，或 '*' 查账号下全部
 * @return array<string, mixed>
 */
function vs_edgeone_billing_params($days = 7, $metric = 'acc_flux', $zoneIds = null)
{
    $range = vs_edgeone_time_range($days);

    if ($zoneIds === null) {
        $selected = vs_edgeone_selected_zone();
        $zoneIds = $selected !== '' ? array($selected) : array('*');
    } elseif (is_string($zoneIds)) {
        $zoneIds = array($zoneIds);
    }

    return array_merge($range, array(
        'Interval'   => 'day',
        'MetricName' => $metric,
        'ZoneIds'    => $zoneIds,
    ));
}

/**
 * 带 ZoneId 的常用参数
 *
 * @param array<string, mixed> $extra
 * @return array<string, mixed>
 */
function vs_edgeone_zone_params(array $extra = array())
{
    return array_merge(array('ZoneId' => vs_edgeone_selected_zone()), $extra);
}

/**
 * 数据分析类查询时间范围（ISO8601）
 *
 * @param int $days
 * @return array{StartTime: string, EndTime: string}
 */
function vs_edgeone_analytics_range($days = 7)
{
    return array(
        'StartTime' => date('Y-m-d\T00:00:00+08:00', strtotime('-' . (int) $days . ' days')),
        'EndTime'   => date('Y-m-d\T23:59:59+08:00'),
    );
}
