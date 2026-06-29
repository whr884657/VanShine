<?php
/**
 * 文件：admin/cdn/edgeone/includes/domains-page.php
 * 作用：域名管理页数据解析与渲染
 */

/**
 * @param array<int, array<string, mixed>> $zones
 * @param string                          $zoneId
 * @return array<string, mixed>|null
 */
function vs_edgeone_find_zone_by_id(array $zones, $zoneId)
{
    foreach ($zones as $zone) {
        if (!is_array($zone)) {
            continue;
        }
        if (isset($zone['ZoneId']) && (string) $zone['ZoneId'] === (string) $zoneId) {
            return $zone;
        }
    }

    return null;
}

/**
 * @param EdgeOne|null $eo
 * @param string       $zoneId
 * @return array{label: string, raw: string}
 */
function vs_edgeone_fetch_zone_ddos_label(EdgeOne $eo = null, $zoneId = '')
{
    $fallback = array('label' => '—', 'raw' => '');
    if ($eo === null || $zoneId === '') {
        return $fallback;
    }

    $result = vs_edgeone_try_call(function () use ($eo, $zoneId) {
        return $eo->security->describeDDoSProtection(array('ZoneId' => $zoneId));
    });
    if (!$result['ok'] || !is_array($result['data'])) {
        return $fallback;
    }

    $opt = '';
    if (isset($result['data']['ProtectionOption'])) {
        $opt = (string) $result['data']['ProtectionOption'];
    } elseif (isset($result['data']['DDoSProtection']['ProtectionOption'])) {
        $opt = (string) $result['data']['DDoSProtection']['ProtectionOption'];
    }

    return array(
        'label' => vs_edgeone_ddos_protection_label($opt),
        'raw'   => $opt,
    );
}

/**
 * @param string $option
 * @return string
 */
function vs_edgeone_ddos_protection_label($option)
{
    $map = array(
        'base'     => '基础防护',
        'standard' => '标准防护',
        'core'     => '标准防护',
        'advanced' => '增强防护',
        'ultimate' => '旗舰防护',
        'off'      => '未开启',
        'on'       => '已开启',
    );
    $option = strtolower((string) $option);

    return isset($map[$option]) ? $map[$option] : ($option !== '' ? $option : '—');
}

/**
 * @param array<string, mixed>|null $zone
 * @param array{label: string, raw: string} $ddos
 * @return string
 */
function vs_edgeone_render_domain_zone_config($zone, array $ddos)
{
    if ($zone === null) {
        return '<p class="vs-form-tip">请先选择站点</p>';
    }

    $type = vs_edgeone_translate('Type', isset($zone['Type']) ? $zone['Type'] : '');
    $area = vs_edgeone_translate('Area', isset($zone['Area']) ? $zone['Area'] : '');
    $active = isset($zone['ActiveStatus']) ? (string) $zone['ActiveStatus'] : '';
    $paused = !empty($zone['Paused']);
    $statusLabel = $paused ? '已停用' : ($active === 'active' ? '已启用' : vs_edgeone_translate('ActiveStatus', $active));

    ob_start();
    echo '<div class="vs-edgeone-domain-config" id="edgeoneDomainConfig">';
    echo '<h3 class="vs-edgeone-domain-config__title">域名配置</h3>';
    echo '<dl class="vs-edgeone-domain-config__grid">';
    echo '<div><dt>接入方式</dt><dd>' . vs_e($type) . '</dd></div>';
    echo '<div><dt>加速区域</dt><dd>' . vs_e($area) . '</dd></div>';
    echo '<div><dt>站点级 DDoS 防护</dt><dd><span class="vs-edgeone-domain-config__shield">' . vs_e($ddos['label']) . '</span></dd></div>';
    echo '<div><dt>站点状态</dt><dd>' . vs_e($statusLabel) . '</dd></div>';
    echo '</dl></div>';

    return ob_get_clean();
}

/**
 * @param array<int, array<string, mixed>> $zones
 * @param string                          $zoneId
 * @param bool                            $canManage
 * @return string
 */
function vs_edgeone_render_domain_toolbar(array $zones, $zoneId, $canManage = true)
{
    $selected = vs_edgeone_selected_zone();

    ob_start();
    echo '<div class="vs-panel vs-edgeone-domains-toolbar-panel">';
    echo '<form method="post" class="vs-edgeone-domain-toolbar" id="edgeoneZoneForm">';
    echo '<input type="hidden" name="action" value="set_zone">';
    echo '<span class="vs-edgeone-domain-toolbar__label">站点</span>';
    echo '<select name="zone_id" class="vs-input vs-edgeone-zone-select" aria-label="选择站点">';
    echo '<option value="">— 请选择站点 —</option>';
    foreach ($zones as $zone) {
        if (!is_array($zone)) {
            continue;
        }
        $id = isset($zone['ZoneId']) ? (string) $zone['ZoneId'] : '';
        if ($id === '') {
            continue;
        }
        $label = vs_edgeone_zone_display_name($zone);
        $sel = $id === $selected ? ' selected' : '';
        echo '<option value="' . vs_e($id) . '"' . $sel . '>' . vs_e($label) . '</option>';
    }
    echo '</select>';
    echo '<button type="submit" class="vs-btn vs-btn--default">切换站点</button>';
    if ($canManage) {
        echo '<button type="button" class="vs-btn vs-btn--primary" id="edgeoneDomainAddBtn">添加域名</button>';
    } else {
        echo '<button type="button" class="vs-btn vs-btn--primary" disabled title="Pages 云端部署站点不支持在此管理">添加域名</button>';
    }
    echo '</form></div>';

    return ob_get_clean();
}

/**
 * @param string $type
 * @return string
 */
function vs_edgeone_domain_origin_type_label($type)
{
    $type = strtoupper((string) $type);
    $map = array(
        'IP_DOMAIN'    => 'IP/域名',
        'ORIGIN_GROUP' => '源站组',
        'COS'          => 'COS',
        'AWS_S3'       => 'AWS S3',
        'VOD'          => '云点播',
        'SPACE'        => 'EdgeOne Pages',
    );

    return isset($map[$type]) ? $map[$type] : ($type !== '' ? $type : '—');
}

/**
 * @param array<string, mixed> $row
 * @return string
 */
function vs_edgeone_domain_origin_value(array $row)
{
    $detail = isset($row['OriginDetail']) && is_array($row['OriginDetail']) ? $row['OriginDetail'] : array();
    if (isset($detail['Origin']) && trim((string) $detail['Origin']) !== '') {
        return (string) $detail['Origin'];
    }
    if (isset($detail['OriginGroupName']) && trim((string) $detail['OriginGroupName']) !== '') {
        return (string) $detail['OriginGroupName'];
    }

    return '—';
}

/**
 * @param mixed $status
 * @return string
 */
function vs_edgeone_domain_ipv6_label($status)
{
    $map = array(
        'follow' => '遵循站点',
        'on'     => 'IPv6',
        'off'    => 'IPv4',
    );
    $status = strtolower((string) $status);

    return isset($map[$status]) ? $map[$status] : ($status !== '' ? strtoupper($status) : '—');
}

/**
 * @param array<string, mixed> $row
 * @return string
 */
function vs_edgeone_domain_https_label(array $row)
{
    $cert = isset($row['Certificate']) && is_array($row['Certificate']) ? $row['Certificate'] : array();
    $mode = isset($cert['Mode']) ? strtolower((string) $cert['Mode']) : '';
    $list = isset($cert['List']) && is_array($cert['List']) ? $cert['List'] : array();

    if ($mode === 'disable' || $mode === 'off') {
        return '未部署';
    }
    if (count($list) > 0 || in_array($mode, array('sslcert', 'eofreecert', 'eofreecert_manual'), true)) {
        return '已部署';
    }

    return '未部署';
}

/**
 * @param array<string, mixed> $row
 * @param array<string, mixed> $ddosData
 * @return bool
 */
function vs_edgeone_domain_ddos_switch_on(array $row, array $ddosData = array())
{
    $name = isset($row['DomainName']) ? (string) $row['DomainName'] : '';
    if ($name === '') {
        return false;
    }
    $lists = array();
    if (isset($ddosData['DomainDDoSProtections']) && is_array($ddosData['DomainDDoSProtections'])) {
        $lists[] = $ddosData['DomainDDoSProtections'];
    }
    if (isset($ddosData['DDoSProtection']['DomainDDoSProtections']) && is_array($ddosData['DDoSProtection']['DomainDDoSProtections'])) {
        $lists[] = $ddosData['DDoSProtection']['DomainDDoSProtections'];
    }
    foreach ($lists as $items) {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['Domain']) && (string) $item['Domain'] === $name) {
                return isset($item['Switch']) && strtolower((string) $item['Switch']) === 'on';
            }
        }
    }

    return isset($ddosData['ProtectionOption']) && strtolower((string) $ddosData['ProtectionOption']) !== 'off';
}

/**
 * @param string $status
 * @return array{label: string, class: string}
 */
function vs_edgeone_domain_status_badge($status)
{
    $status = strtolower((string) $status);
    if ($status === 'online') {
        return array('label' => '已生效', 'class' => 'is-ok');
    }
    if ($status === 'offline') {
        return array('label' => '已停用', 'class' => 'is-muted');
    }
    if ($status === 'process') {
        return array('label' => '部署中', 'class' => 'is-warning');
    }

    return array(
        'label' => vs_edgeone_translate('DomainStatus', $status),
        'class' => 'is-muted',
    );
}

/**
 * @param array<int, array<string, mixed>> $domains
 * @param array<string, mixed>             $ddosData
 * @param string                           $error
 * @param string                           $zoneId
 * @param bool                             $canManage
 * @return string
 */
function vs_edgeone_render_domain_list_panel(array $domains, array $ddosData, $error, $zoneId, $canManage = true)
{
    ob_start();
    echo '<div class="vs-panel vs-edgeone-domains-list" id="edgeoneDomainsListPanel">';

    if ($zoneId === '') {
        echo '<p class="vs-form-tip">请先选择站点</p>';
        echo '</div>';
        return ob_get_clean();
    }
    if (!$canManage) {
        echo '<p class="vs-form-tip">当前站点为 Pages 云端部署，请在 EdgeOne Pages 控制台管理域名。</p>';
        echo '</div>';
        return ob_get_clean();
    }
    if ($error !== '') {
        echo '<p class="vs-form-tip">加载失败：' . vs_e($error) . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    echo '<div class="vs-edgeone-domains-toolbar-inner">';
    echo '<div class="vs-edgeone-domains-search">';
    echo '<input type="search" class="vs-input" id="edgeoneDomainsSearch" placeholder="搜索加速域名 / CNAME / 源站" aria-label="搜索域名">';
    echo '</div></div>';

    if (count($domains) === 0) {
        echo '<p class="vs-form-tip">暂无加速域名，点击「添加域名」创建</p>';
    } else {
        echo '<div class="vs-edgeone-domain-cards" id="edgeoneDomainCards">';
        foreach ($domains as $row) {
            if (!is_array($row)) {
                continue;
            }
            echo vs_edgeone_render_domain_card($row, $ddosData);
        }
        echo '</div>';

        echo '<div class="vs-table-wrap vs-edgeone-domain-table-wrap">';
        echo '<table class="vs-table vs-edgeone-domain-table" id="edgeoneDomainTable">';
        echo '<thead><tr>';
        echo '<th>加速域名</th><th>状态</th><th>CNAME</th><th>源站类型</th><th>源站配置</th><th>拓展服务</th><th>HTTPS 配置</th><th>操作</th>';
        echo '</tr></thead><tbody>';
        foreach ($domains as $row) {
            if (!is_array($row)) {
                continue;
            }
            echo vs_edgeone_render_domain_table_row($row, $ddosData);
        }
        echo '</tbody></table>';
        echo '<p class="vs-edgeone-domains-table__foot" id="edgeoneDomainsCount">共 ' . count($domains) . ' 条</p>';
        echo '</div>';
    }

    echo '</div>';
    return ob_get_clean();
}

/**
 * @param array<string, mixed> $row
 * @param array<string, mixed> $ddosData
 * @return string
 */
function vs_edgeone_render_domain_table_row(array $row, array $ddosData)
{
    $name = isset($row['DomainName']) ? (string) $row['DomainName'] : '';
    $status = isset($row['DomainStatus']) ? (string) $row['DomainStatus'] : '';
    $badge = vs_edgeone_domain_status_badge($status);
    $cname = isset($row['Cname']) ? (string) $row['Cname'] : '—';
    $originType = isset($row['OriginDetail']['OriginType']) ? $row['OriginDetail']['OriginType'] : '';
    $originVal = vs_edgeone_domain_origin_value($row);
    $https = vs_edgeone_domain_https_label($row);
    $ipv6 = vs_edgeone_domain_ipv6_label(isset($row['IPv6Status']) ? $row['IPv6Status'] : '');
    $ddosOn = vs_edgeone_domain_ddos_switch_on($row, $ddosData);
    $searchText = strtolower($name . ' ' . $cname . ' ' . $originVal);
    $isOnline = strtolower($status) === 'online';

    ob_start();
    echo '<tr data-domain-search="' . vs_e($searchText) . '" data-domain-name="' . vs_e($name) . '" data-domain-status="' . vs_e($status) . '">';
    echo '<td class="vs-edgeone-domain-table__name">' . vs_e($name) . '</td>';
    echo '<td><span class="vs-edgeone-domain-status ' . vs_e($badge['class']) . '">' . vs_e($badge['label']) . '</span></td>';
    echo '<td><code class="vs-edgeone-domain-cname">' . vs_e($cname !== '' ? $cname : '—') . '</code></td>';
    echo '<td>' . vs_e(vs_edgeone_domain_origin_type_label($originType)) . '</td>';
    echo '<td>' . vs_e($originVal) . '</td>';
    echo '<td class="vs-edgeone-domain-ext">';
    if ($ddosOn) {
        echo '<span class="vs-edgeone-domain-ext__tag" title="DDoS 防护">DDoS</span>';
    }
    if ($ipv6 !== '—' && $ipv6 !== 'IPv4') {
        echo '<span class="vs-edgeone-domain-ext__tag is-ipv6">' . vs_e($ipv6) . '</span>';
    }
    if (!$ddosOn && ($ipv6 === '—' || $ipv6 === 'IPv4')) {
        echo '—';
    }
    echo '</td>';
    echo '<td>' . vs_e($https) . '</td>';
    echo '<td class="vs-edgeone-domain-table__actions">';
    echo '<a href="#" class="vs-edgeone-domain-edit" data-domain="' . vs_e($name) . '">编辑</a>';
    if ($isOnline) {
        echo '<a href="#" class="vs-edgeone-domain-status-toggle" data-domain="' . vs_e($name) . '" data-status="offline">停用</a>';
        echo '<span class="vs-edgeone-zones-action is-disabled" title="请先停用域名">删除</span>';
    } else {
        echo '<a href="#" class="vs-edgeone-domain-status-toggle" data-domain="' . vs_e($name) . '" data-status="online">启用</a>';
        echo '<a href="#" class="vs-edgeone-domain-delete" data-domain="' . vs_e($name) . '">删除</a>';
    }
    echo '</td></tr>';

    return ob_get_clean();
}

/**
 * @param array<string, mixed> $row
 * @param array<string, mixed> $ddosData
 * @return string
 */
function vs_edgeone_render_domain_card(array $row, array $ddosData)
{
    $name = isset($row['DomainName']) ? (string) $row['DomainName'] : '';
    $status = isset($row['DomainStatus']) ? (string) $row['DomainStatus'] : '';
    $badge = vs_edgeone_domain_status_badge($status);
    $cname = isset($row['Cname']) ? (string) $row['Cname'] : '—';
    $originType = vs_edgeone_domain_origin_type_label(isset($row['OriginDetail']['OriginType']) ? $row['OriginDetail']['OriginType'] : '');
    $originVal = vs_edgeone_domain_origin_value($row);
    $https = vs_edgeone_domain_https_label($row);
    $ipv6 = vs_edgeone_domain_ipv6_label(isset($row['IPv6Status']) ? $row['IPv6Status'] : '');
    $ddosOn = vs_edgeone_domain_ddos_switch_on($row, $ddosData);
    $searchText = strtolower($name . ' ' . $cname . ' ' . $originVal);
    $isOnline = strtolower($status) === 'online';

    ob_start();
    echo '<article class="vs-edgeone-domain-card" data-domain-search="' . vs_e($searchText) . '" data-domain-name="' . vs_e($name) . '">';
    echo '<div class="vs-edgeone-domain-card__head">';
    echo '<span class="vs-edgeone-domain-card__title">' . vs_e($name) . '</span>';
    echo '<span class="vs-edgeone-domain-status ' . vs_e($badge['class']) . '">' . vs_e($badge['label']) . '</span>';
    echo '</div>';
    echo '<code class="vs-edgeone-domain-cname">' . vs_e($cname !== '' ? $cname : '—') . '</code>';
    echo '<dl class="vs-edgeone-domain-card__meta">';
    echo '<div><dt>源站类型</dt><dd>' . vs_e($originType) . '</dd></div>';
    echo '<div><dt>源站配置</dt><dd>' . vs_e($originVal) . '</dd></div>';
    echo '<div><dt>HTTPS</dt><dd>' . vs_e($https) . '</dd></div>';
    echo '<div><dt>拓展</dt><dd>';
    if ($ddosOn) {
        echo '<span class="vs-edgeone-domain-ext__tag">DDoS</span> ';
    }
    if ($ipv6 !== '—' && $ipv6 !== 'IPv4') {
        echo '<span class="vs-edgeone-domain-ext__tag is-ipv6">' . vs_e($ipv6) . '</span>';
    }
    if (!$ddosOn && ($ipv6 === '—' || $ipv6 === 'IPv4')) {
        echo '—';
    }
    echo '</dd></div></dl>';
    echo '<div class="vs-edgeone-domain-card__actions">';
    echo '<a href="#" class="vs-edgeone-domain-edit" data-domain="' . vs_e($name) . '">编辑</a>';
    if ($isOnline) {
        echo '<a href="#" class="vs-edgeone-domain-status-toggle" data-domain="' . vs_e($name) . '" data-status="offline">停用</a>';
        echo '<span class="vs-edgeone-zones-action is-disabled">删除</span>';
    } else {
        echo '<a href="#" class="vs-edgeone-domain-status-toggle" data-domain="' . vs_e($name) . '" data-status="online">启用</a>';
        echo '<a href="#" class="vs-edgeone-domain-delete" data-domain="' . vs_e($name) . '">删除</a>';
    }
    echo '</div></article>';

    return ob_get_clean();
}

/**
 * @param array<string, mixed>|null $zone
 * @param array<int, array<string, mixed>> $domains
 * @return string
 */
function vs_edgeone_render_domain_drawers($zone, array $domains = array())
{
    ob_start();
    echo '<div class="vs-edgeone-zone-create-drawer" id="edgeoneDomainCreateDrawer" hidden aria-hidden="true">';
    echo '<div class="vs-edgeone-zone-create-drawer__overlay" data-domain-drawer-close></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__panel" role="dialog" aria-modal="true">';
    echo '<div class="vs-edgeone-zone-create-drawer__handle" aria-hidden="true"></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__head">';
    echo '<h4>添加域名</h4>';
    echo '<button type="button" class="vs-edgeone-filter-drawer__close" data-domain-drawer-close aria-label="关闭">';
    echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
    echo '</button></div>';
    echo '<form class="vs-form vs-edgeone-api-form" id="edgeoneDomainCreateForm" data-action="domain_create">';
    echo '<div class="vs-edgeone-zone-create-drawer__body">';
    echo '<p class="vs-form-tip">请添加域名，以开启安全加速服务</p>';
    echo '<div class="vs-form-row"><label class="vs-label">加速域名</label>';
    echo '<input type="text" name="domain_name" class="vs-input" placeholder="www.example.com" required></div>';
    echo '<div class="vs-form-row"><label class="vs-label">IPv6 访问</label>';
    echo '<select name="ipv6_status" class="vs-input">';
    echo '<option value="follow">遵循站点配置</option>';
    echo '<option value="off">关闭</option>';
    echo '<option value="on">开启</option>';
    echo '</select></div>';
    echo '<h5 class="vs-edgeone-subtitle">回源配置</h5>';
    echo '<div class="vs-form-row vs-form-row--inline">';
    echo '<div class="vs-form-col"><label class="vs-label">源站类型</label>';
    echo '<select name="origin_type" class="vs-input"><option value="IP_DOMAIN">IP/域名</option></select></div>';
    echo '<div class="vs-form-col"><label class="vs-label">源站地址</label>';
    echo '<input type="text" name="origin" class="vs-input" placeholder="IP 或域名" required></div></div>';
    echo '<div class="vs-form-row"><label class="vs-label">回源协议</label>';
    echo '<select name="origin_protocol" class="vs-input">';
    echo '<option value="FOLLOW">协议跟随</option><option value="HTTP">HTTP</option><option value="HTTPS">HTTPS</option>';
    echo '</select></div>';
    echo '<div class="vs-form-row vs-form-row--inline">';
    echo '<div class="vs-form-col"><label class="vs-label">HTTP 端口</label><input type="number" name="http_port" class="vs-input" value="80" min="1" max="65535"></div>';
    echo '<div class="vs-form-col"><label class="vs-label">HTTPS 端口</label><input type="number" name="https_port" class="vs-input" value="443" min="1" max="65535"></div>';
    echo '</div>';
    echo '<div class="vs-form-row"><label class="vs-label">回源 HOST 头</label>';
    echo '<select name="host_header_mode" class="vs-input"><option value="domain">使用加速域名</option><option value="custom">自定义</option></select>';
    echo '<input type="text" name="host_header" class="vs-input" placeholder="留空则使用加速域名" style="margin-top:8px"></div>';
    echo '</div>';
    echo '<div class="vs-edgeone-zone-create-drawer__foot">';
    echo '<button type="button" class="vs-btn vs-btn--ghost" data-domain-drawer-close>取消</button>';
    echo '<button type="submit" class="vs-btn vs-btn--primary">创建</button>';
    echo '</div></form></div></div>';

    echo '<div class="vs-edgeone-zone-create-drawer" id="edgeoneDomainEditDrawer" hidden aria-hidden="true">';
    echo '<div class="vs-edgeone-zone-create-drawer__overlay" data-domain-drawer-close></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__panel" role="dialog" aria-modal="true">';
    echo '<div class="vs-edgeone-zone-create-drawer__head"><h4>编辑域名</h4>';
    echo '<button type="button" class="vs-edgeone-filter-drawer__close" data-domain-drawer-close aria-label="关闭">';
    echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
    echo '</button></div>';
    echo '<form class="vs-form vs-edgeone-api-form" id="edgeoneDomainEditForm" data-action="domain_modify">';
    echo '<div class="vs-edgeone-zone-create-drawer__body">';
    echo '<input type="hidden" name="domain_name" id="edgeoneDomainEditName" value="">';
    echo '<p class="vs-form-tip" id="edgeoneDomainEditTitle"></p>';
    echo '<div class="vs-form-row"><label class="vs-label">IPv6 访问</label><select name="ipv6_status" class="vs-input" id="edgeoneDomainEditIpv6">';
    echo '<option value="follow">遵循站点配置</option><option value="off">关闭</option><option value="on">开启</option>';
    echo '</select></div>';
    echo '<div class="vs-form-row vs-form-row--inline">';
    echo '<div class="vs-form-col"><label class="vs-label">源站类型</label><select name="origin_type" class="vs-input" id="edgeoneDomainEditOriginType"><option value="IP_DOMAIN">IP/域名</option></select></div>';
    echo '<div class="vs-form-col"><label class="vs-label">源站地址</label><input type="text" name="origin" class="vs-input" id="edgeoneDomainEditOrigin"></div></div>';
    echo '<div class="vs-form-row"><label class="vs-label">回源协议</label><select name="origin_protocol" class="vs-input" id="edgeoneDomainEditProtocol">';
    echo '<option value="FOLLOW">协议跟随</option><option value="HTTP">HTTP</option><option value="HTTPS">HTTPS</option></select></div>';
    echo '<div class="vs-form-row vs-form-row--inline">';
    echo '<div class="vs-form-col"><label class="vs-label">HTTP 端口</label><input type="number" name="http_port" class="vs-input" id="edgeoneDomainEditHttpPort" min="1" max="65535"></div>';
    echo '<div class="vs-form-col"><label class="vs-label">HTTPS 端口</label><input type="number" name="https_port" class="vs-input" id="edgeoneDomainEditHttpsPort" min="1" max="65535"></div>';
    echo '</div></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__foot">';
    echo '<button type="button" class="vs-btn vs-btn--ghost" data-domain-drawer-close>取消</button>';
    echo '<button type="submit" class="vs-btn vs-btn--primary">保存</button>';
    echo '</div></form></div></div>';

    return ob_get_clean();
}
