<?php
/**
 * 文件：admin/cdn/edgeone/init.php
 * 作用：EdgeOne 后台公共引导
 * @version 1.0.0
 */

require_once dirname(__DIR__, 2) . '/init.php';
require_once VS_ROOT . '/core/cdn/edgeone/EdgeOne.php';

EdgeOne::load();
TencentCloudConfig::migrateLegacyIfNeeded();

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
 * @param string $activeMenu
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
    echo '<label class="vs-label">当前站点 ZoneId</label>';
    echo '<select name="zone_id" class="vs-input vs-edgeone-zone-select">';
    echo '<option value="">— 请选择站点 —</option>';
    foreach ($zones as $zone) {
        $id = isset($zone['ZoneId']) ? $zone['ZoneId'] : '';
        $name = isset($zone['ZoneName']) ? $zone['ZoneName'] : $id;
        $sel = $id === $selected ? ' selected' : '';
        echo '<option value="' . vs_e($id) . '"' . $sel . '>' . vs_e($name) . '</option>';
    }
    echo '</select>';
    echo '<button type="submit" class="vs-btn vs-btn--default">切换站点</button>';
    echo '</form>';
}
