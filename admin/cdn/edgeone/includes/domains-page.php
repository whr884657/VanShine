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
 * @param array<string, mixed>|null $zone
 * @return string
 */
function vs_edgeone_zone_root_domain($zone)
{
    if ($zone === null || !is_array($zone)) {
        return '';
    }

    return strtolower(trim((string) (isset($zone['ZoneName']) ? $zone['ZoneName'] : '')));
}

/**
 * @param string                    $input
 * @param array<string, mixed>|null $zone
 * @return string
 */
function vs_edgeone_normalize_acceleration_domain($input, $zone)
{
    $input = strtolower(trim((string) $input));
    if ($input === '') {
        throw new Exception('请填写域名前缀');
    }
    $root = vs_edgeone_zone_root_domain($zone);
    if ($root === '') {
        throw new Exception('无法获取站点主域名');
    }
    if ($input === '@') {
        return $root;
    }
    $input = rtrim($input, '.');
    if (strpos($input, '.') !== false) {
        if ($input === $root || preg_match('/\.' . preg_quote($root, '/') . '$/i', $input)) {
            return $input;
        }
        throw new Exception('加速域名须属于站点 ' . $root);
    }

    return $input . '.' . $root;
}

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @return array{domains: array<int, array<string, mixed>>, error: string, ddos: array<string, mixed>}
 */
function vs_edgeone_fetch_domains_page_data(EdgeOne $eo, $zoneId)
{
    $out = array(
        'domains' => array(),
        'error'   => '',
        'ddos'    => array(),
    );
    if ($zoneId === '') {
        return $out;
    }

    $dResult = vs_edgeone_try_call(function () use ($eo, $zoneId) {
        return $eo->accelerationDomain->describeAccelerationDomains(array(
            'ZoneId' => $zoneId,
            'Offset' => 0,
            'Limit'  => 100,
        ));
    }, array());
    if ($dResult['ok']) {
        $out['domains'] = isset($dResult['data']['AccelerationDomains']) && is_array($dResult['data']['AccelerationDomains'])
            ? $dResult['data']['AccelerationDomains']
            : array();
    } else {
        $out['error'] = $dResult['error'];
    }

    $ddosResult = vs_edgeone_try_call(function () use ($eo, $zoneId) {
        return $eo->security->describeDDoSProtection(array('ZoneId' => $zoneId));
    });
    if ($ddosResult['ok'] && is_array($ddosResult['data'])) {
        $out['ddos'] = $ddosResult['data'];
    }

    return $out;
}

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @param string  $domainName
 * @return array<string, mixed>|null
 */
function vs_edgeone_fetch_single_acceleration_domain(EdgeOne $eo, $zoneId, $domainName)
{
    $domainName = trim((string) $domainName);
    if ($domainName === '') {
        return null;
    }

    $result = vs_edgeone_try_call(function () use ($eo, $zoneId, $domainName) {
        return $eo->accelerationDomain->describeAccelerationDomains(array(
            'ZoneId'  => $zoneId,
            'Offset'  => 0,
            'Limit'   => 20,
            'Filters' => array(
                array(
                    'Name'   => 'domain-name',
                    'Values' => array($domainName),
                ),
            ),
        ));
    }, array());
    if (!$result['ok'] || !is_array($result['data'])) {
        return null;
    }

    $list = isset($result['data']['AccelerationDomains']) && is_array($result['data']['AccelerationDomains'])
        ? $result['data']['AccelerationDomains']
        : array();
    foreach ($list as $row) {
        if (is_array($row) && isset($row['DomainName']) && (string) $row['DomainName'] === $domainName) {
            return $row;
        }
    }

    return null;
}

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @return array{by_id: array<string, array<string, mixed>>, by_domain: array<string, array<string, mixed>>, all: array<int, array<string, mixed>>}
 */
function vs_edgeone_fetch_zone_cert_index(EdgeOne $eo, $zoneId)
{
    static $cache = array();
    if ($zoneId === '') {
        return array('by_id' => array(), 'by_domain' => array(), 'all' => array());
    }
    if (isset($cache[$zoneId])) {
        return $cache[$zoneId];
    }

    $maps = array('by_id' => array(), 'by_domain' => array(), 'all' => array());
    $offset = 0;
    $limit = 200;
    $total = null;

    do {
        $result = vs_edgeone_try_call(function () use ($eo, $zoneId, $offset, $limit) {
            return $eo->certificate->describeDefaultCertificates(array(
                'ZoneId'  => $zoneId,
                'Filters' => array(
                    array('Name' => 'zone-id', 'Values' => array($zoneId)),
                ),
                'Offset' => $offset,
                'Limit'  => $limit,
            ));
        }, array());
        if (!$result['ok'] || !is_array($result['data'])) {
            break;
        }
        if ($total === null && isset($result['data']['TotalCount'])) {
            $total = (int) $result['data']['TotalCount'];
        }
        $items = isset($result['data']['DefaultServerCertInfo']) && is_array($result['data']['DefaultServerCertInfo'])
            ? $result['data']['DefaultServerCertInfo']
            : array();
        foreach ($items as $cert) {
            if (!is_array($cert)) {
                continue;
            }
            $maps['all'][] = $cert;
            $cid = isset($cert['CertId']) ? (string) $cert['CertId'] : '';
            if ($cid !== '') {
                $maps['by_id'][$cid] = $cert;
            }
            $names = array();
            if (isset($cert['CommonName']) && trim((string) $cert['CommonName']) !== '') {
                $names[] = strtolower(trim((string) $cert['CommonName']));
            }
            if (isset($cert['SubjectAltName']) && is_array($cert['SubjectAltName'])) {
                foreach ($cert['SubjectAltName'] as $san) {
                    $san = strtolower(trim((string) $san));
                    if ($san !== '') {
                        $names[] = $san;
                    }
                }
            }
            foreach ($names as $name) {
                if (!isset($maps['by_domain'][$name])) {
                    $maps['by_domain'][$name] = $cert;
                }
            }
        }
        $offset += count($items);
    } while ($total !== null && $offset < $total && count($items) > 0);

    $cache[$zoneId] = $maps;

    return $maps;
}

/**
 * @param array<string, mixed> $cert
 * @param string               $domainName
 * @return bool
 */
function vs_edgeone_cert_covers_domain(array $cert, $domainName)
{
    $domainName = strtolower(trim($domainName));
    if ($domainName === '') {
        return false;
    }

    $matchName = function ($pattern) use ($domainName) {
        $pattern = strtolower(trim((string) $pattern));
        if ($pattern === '') {
            return false;
        }
        if ($pattern === $domainName) {
            return true;
        }
        if (strpos($pattern, '*.') === 0) {
            $suffix = substr($pattern, 1);
            if ($suffix !== '' && strlen($domainName) > strlen($suffix)) {
                return substr($domainName, -strlen($suffix)) === $suffix;
            }
        }

        return false;
    };

    if ($matchName(isset($cert['CommonName']) ? $cert['CommonName'] : '')) {
        return true;
    }
    if (isset($cert['SubjectAltName']) && is_array($cert['SubjectAltName'])) {
        foreach ($cert['SubjectAltName'] as $san) {
            if ($matchName($san)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * @param array{by_id: array<string, array<string, mixed>>, by_domain: array<string, array<string, mixed>>, all: array<int, array<string, mixed>>} $certMaps
 * @param string $domainName
 * @return array<string, mixed>|null
 */
function vs_edgeone_find_default_cert_for_domain(array $certMaps, $domainName)
{
    $domainName = strtolower(trim($domainName));
    if ($domainName === '') {
        return null;
    }
    if (isset($certMaps['by_domain'][$domainName])) {
        return $certMaps['by_domain'][$domainName];
    }
    if (!isset($certMaps['all']) || !is_array($certMaps['all'])) {
        return null;
    }
    foreach ($certMaps['all'] as $cert) {
        if (is_array($cert) && vs_edgeone_cert_covers_domain($cert, $domainName)) {
            return $cert;
        }
    }

    return null;
}

/**
 * @param string $status
 * @param string $mode
 * @param string $expire
 * @return array{label: string, class: string, mode: string, expire: string, state: string}|null
 */
function vs_edgeone_map_cert_status_to_info($status, $mode, $expire = '')
{
    $status = strtolower(trim((string) $status));
    if ($status === 'deployed' || $status === '') {
        return vs_edgeone_https_info_with_expire('已部署', 'is-ok', 'deployed', $mode, $expire);
    }
    if ($status === 'processing') {
        return array('label' => '部署中', 'class' => 'is-warning', 'mode' => $mode, 'expire' => $expire, 'state' => 'processing');
    }
    if ($status === 'applying') {
        return array('label' => '申请中', 'class' => 'is-warning', 'mode' => $mode, 'expire' => $expire, 'state' => 'applying');
    }
    if ($status === 'failed') {
        return array('label' => '部署失败', 'class' => 'is-danger', 'mode' => $mode, 'expire' => $expire, 'state' => 'failed');
    }
    if ($status === 'issued') {
        return array('label' => '绑定失败', 'class' => 'is-danger', 'mode' => $mode, 'expire' => $expire, 'state' => 'failed');
    }

    return null;
}

/**
 * @param string $label
 * @param string $class
 * @param string $state
 * @param string $mode
 * @param string $expire
 * @return array{label: string, class: string, mode: string, expire: string, state: string}
 */
function vs_edgeone_https_info_with_expire($label, $class, $state, $mode, $expire)
{
    if ($expire !== '') {
        $expTs = strtotime($expire);
        if ($expTs !== false && $expTs < time()) {
            return array('label' => '已过期', 'class' => 'is-danger', 'mode' => $mode, 'expire' => $expire, 'state' => 'expired');
        }
        if ($expTs !== false && $expTs < time() + 30 * 86400) {
            return array('label' => '即将过期', 'class' => 'is-warning', 'mode' => $mode, 'expire' => $expire, 'state' => 'expiring');
        }
    }

    return array('label' => $label, 'class' => $class, 'mode' => $mode, 'expire' => $expire, 'state' => $state);
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
        'base'                      => '基础防护',
        'standard'                  => '标准防护',
        'core'                      => '标准防护',
        'advanced'                  => '增强防护',
        'ultimate'                  => '旗舰防护',
        'off'                       => '未开启',
        'on'                        => '已开启',
        'platform_protect'          => '平台默认防护',
        'protect_all_domains'       => '全部域名防护',
        'protect_specified_domains' => '指定域名防护',
        'unprotected'               => '未开启防护',
    );
    $option = strtolower((string) $option);

    return isset($map[$option]) ? $map[$option] : ($option !== '' ? vs_edgeone_humanize_api_token($option) : '—');
}

/**
 * @param string $token
 * @return string
 */
function vs_edgeone_humanize_api_token($token)
{
    $token = str_replace(array('-', '_'), ' ', strtolower((string) $token));

    return $token !== '' ? $token : '—';
}

/**
 * @param array<string, mixed>|null $zone
 * @param array{label: string, raw: string} $ddos
 * @return string
 */
function vs_edgeone_render_domain_site_meta($zone, array $ddos)
{
    if ($zone === null) {
        return '';
    }

    $type = vs_edgeone_translate('Type', isset($zone['Type']) ? $zone['Type'] : '');
    $area = vs_edgeone_translate('Area', isset($zone['Area']) ? $zone['Area'] : '');
    $active = isset($zone['ActiveStatus']) ? (string) $zone['ActiveStatus'] : '';
    $paused = !empty($zone['Paused']);
    $statusLabel = $paused ? '已停用' : ($active === 'active' ? '已启用' : vs_edgeone_translate('ActiveStatus', $active));

    ob_start();
    echo '<div class="vs-edgeone-domain-site-meta" id="edgeoneDomainSiteMeta">';
    echo '<dl class="vs-edgeone-domain-site-meta__grid">';
    echo '<div><dt>接入方式</dt><dd>' . vs_e($type) . '</dd></div>';
    echo '<div><dt>加速区域</dt><dd>' . vs_e($area) . '</dd></div>';
    echo '<div><dt>站点级 DDoS 防护</dt><dd><span class="vs-edgeone-domain-site-meta__shield">' . vs_e($ddos['label']) . '</span></dd></div>';
    echo '<div><dt>站点状态</dt><dd>' . vs_e($statusLabel) . '</dd></div>';
    echo '</dl></div>';

    return ob_get_clean();
}

/**
 * @param array<int, array<string, mixed>> $zones
 * @param string                          $zoneId
 * @param array<string, mixed>|null       $zone
 * @param array{label: string, raw: string} $ddos
 * @param bool                            $canManage
 * @return string
 */
function vs_edgeone_render_domain_site_panel(array $zones, $zoneId, $zone, array $ddos, $canManage = true)
{
    $selected = vs_edgeone_selected_zone();

    ob_start();
    echo '<div class="vs-panel vs-edgeone-domain-site-panel">';
    echo '<form method="post" class="vs-edgeone-domain-toolbar" id="edgeoneZoneForm">';
    echo '<input type="hidden" name="action" value="set_zone">';
    echo '<div class="vs-edgeone-domain-toolbar__row vs-edgeone-domain-toolbar__row--select">';
    echo '<div class="vs-edgeone-domain-toolbar__field">';
    echo '<label class="vs-edgeone-domain-toolbar__label" for="edgeoneDomainZoneSelect">站点</label>';
    echo '<select id="edgeoneDomainZoneSelect" name="zone_id" class="vs-input vs-edgeone-zone-select" aria-label="选择站点">';
    echo '<option value="">— 请选择站点 —</option>';
    foreach ($zones as $z) {
        if (!is_array($z)) {
            continue;
        }
        $id = isset($z['ZoneId']) ? (string) $z['ZoneId'] : '';
        if ($id === '') {
            continue;
        }
        $label = vs_edgeone_zone_display_name($z);
        $sel = $id === $selected ? ' selected' : '';
        echo '<option value="' . vs_e($id) . '"' . $sel . '>' . vs_e($label) . '</option>';
    }
    echo '</select></div></div>';
    echo '<div class="vs-edgeone-domain-toolbar__row vs-edgeone-domain-toolbar__row--actions">';
    echo '<button type="submit" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-domain-toolbar__switch">切换站点</button>';
    if ($canManage) {
        echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--primary vs-edgeone-domain-toolbar__add" id="edgeoneDomainAddBtn">添加域名</button>';
    } else {
        echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--primary vs-edgeone-domain-toolbar__add" disabled title="Pages 云端部署站点不支持在此管理">添加域名</button>';
    }
    echo '</div>';
    if ($zoneId !== '' && $zone !== null) {
        echo vs_edgeone_render_domain_site_meta($zone, $ddos);
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
 * @param string $cname
 * @return string
 */
function vs_edgeone_render_domain_cname_cell($cname)
{
    $cname = trim((string) $cname);
    if ($cname === '' || $cname === '—') {
        return '—';
    }

    return '<button type="button" class="vs-edgeone-domain-cname-copy" data-copy="' . vs_e($cname) . '" title="点击复制 CNAME">'
        . '<code class="vs-edgeone-domain-cname">' . vs_e($cname) . '</code>'
        . '<span class="vs-edgeone-domain-cname-copy__hint">复制</span>'
        . '</button>';
}

/**
 * @param array<string, mixed> $row
 * @param array{by_id?: array<string, array<string, mixed>>, by_domain?: array<string, array<string, mixed>>, all?: array<int, array<string, mixed>>} $certMaps
 * @return array{label: string, class: string, mode: string, expire: string, state: string}
 */
function vs_edgeone_domain_https_info(array $row, array $certMaps = array())
{
    $domainName = isset($row['DomainName']) ? (string) $row['DomainName'] : '';
    $cert = isset($row['Certificate']) && is_array($row['Certificate']) ? $row['Certificate'] : array();
    $mode = strtolower((string) (isset($cert['Mode']) ? $cert['Mode'] : ''));
    $list = isset($cert['List']) && is_array($cert['List']) ? $cert['List'] : array();
    $domainStatus = strtolower((string) (isset($row['DomainStatus']) ? $row['DomainStatus'] : ''));

    if ($mode === '' || $mode === 'disable' || $mode === 'off') {
        return array('label' => '未部署', 'class' => 'is-muted', 'mode' => $mode, 'expire' => '', 'state' => 'off');
    }

    $byId = isset($certMaps['by_id']) && is_array($certMaps['by_id']) ? $certMaps['by_id'] : array();

    foreach ($list as $item) {
        if (!is_array($item)) {
            continue;
        }
        $expire = isset($item['ExpireTime']) ? (string) $item['ExpireTime'] : '';
        if (isset($item['Status']) && trim((string) $item['Status']) !== '') {
            $info = vs_edgeone_map_cert_status_to_info((string) $item['Status'], $mode, $expire);
            if ($info !== null) {
                return $info;
            }
        }
        $cid = isset($item['CertId']) ? (string) $item['CertId'] : '';
        if ($cid !== '' && isset($byId[$cid])) {
            $detail = $byId[$cid];
            $detailExpire = isset($detail['ExpireTime']) ? (string) $detail['ExpireTime'] : $expire;
            $detailStatus = isset($detail['Status']) ? (string) $detail['Status'] : 'deployed';
            $info = vs_edgeone_map_cert_status_to_info($detailStatus, $mode, $detailExpire);
            if ($info !== null) {
                return $info;
            }
        }
    }

    if ($domainName !== '') {
        $defaultCert = vs_edgeone_find_default_cert_for_domain($certMaps, $domainName);
        if ($defaultCert !== null) {
            $expire = isset($defaultCert['ExpireTime']) ? (string) $defaultCert['ExpireTime'] : '';
            $st = isset($defaultCert['Status']) ? (string) $defaultCert['Status'] : 'deployed';
            $info = vs_edgeone_map_cert_status_to_info($st, $mode, $expire);
            if ($info !== null) {
                return $info;
            }
        }
    }

    if (in_array($mode, array('eofreecert', 'eofreecert_manual', 'sslcert'), true)) {
        if ($domainStatus === 'process') {
            return array('label' => '部署中', 'class' => 'is-warning', 'mode' => $mode, 'expire' => '', 'state' => 'processing');
        }
        if ($domainStatus === 'online') {
            return array('label' => '已部署', 'class' => 'is-ok', 'mode' => $mode, 'expire' => '', 'state' => 'deployed');
        }

        return array('label' => '申请中', 'class' => 'is-warning', 'mode' => $mode, 'expire' => '', 'state' => 'applying');
    }

    return array('label' => '未部署', 'class' => 'is-muted', 'mode' => $mode, 'expire' => '', 'state' => 'off');
}

/**
 * @param array<string, mixed> $row
 * @param array<string, array<string, mixed>> $certIndex
 * @return string
 */
function vs_edgeone_render_domain_https_cell(array $row, array $certIndex = array())
{
    $name = isset($row['DomainName']) ? (string) $row['DomainName'] : '';
    $info = vs_edgeone_domain_https_info($row, $certIndex);
    $cert = isset($row['Certificate']) && is_array($row['Certificate']) ? $row['Certificate'] : array();
    $mode = isset($cert['Mode']) ? (string) $cert['Mode'] : '';

    ob_start();
    echo '<div class="vs-edgeone-https-cell">';
    echo '<span class="vs-edgeone-domain-status ' . vs_e($info['class']) . '">' . vs_e($info['label']) . '</span>';
    if ($name !== '') {
        echo '<button type="button" class="vs-edgeone-domain-cert-config" data-domain="' . vs_e($name) . '" data-cert-mode="' . vs_e($mode) . '">配置</button>';
    }
    echo '</div>';

    return ob_get_clean();
}

/**
 * @param string $name
 * @param string $selected
 * @return string
 */
function vs_edgeone_render_ipv6_segment($name, $selected = 'follow')
{
    $selected = strtolower((string) $selected);
    $options = array(
        'follow' => '遵循站点',
        'on'     => '开启',
        'off'    => '关闭',
    );

    ob_start();
    echo '<div class="vs-edgeone-segment" role="radiogroup" aria-label="IPv6 访问">';
    foreach ($options as $value => $label) {
        $checked = $selected === $value ? ' checked' : '';
        echo '<label class="vs-edgeone-segment__item">';
        echo '<input type="radio" name="' . vs_e($name) . '" value="' . vs_e($value) . '"' . $checked . '>';
        echo '<span>' . vs_e($label) . '</span></label>';
    }
    echo '</div>';

    return ob_get_clean();
}

/**
 * @param array<string, mixed> $row
 * @return string
 */
function vs_edgeone_domain_https_label(array $row, array $certIndex = array())
{
    $info = vs_edgeone_domain_https_info($row, $certIndex);

    return $info['label'];
}

/**
 * @param array<string, mixed> $row
 * @return string
 */
function vs_edgeone_render_domain_status_cell(array $row)
{
    $name = isset($row['DomainName']) ? (string) $row['DomainName'] : '';
    $status = isset($row['DomainStatus']) ? (string) $row['DomainStatus'] : '';
    $badge = vs_edgeone_domain_status_badge($status);
    $showRefresh = strtolower($status) === 'process';

    ob_start();
    echo '<span class="vs-edgeone-domain-status-cell">';
    echo '<span class="vs-edgeone-domain-status ' . vs_e($badge['class']) . '">' . vs_e($badge['label']) . '</span>';
    if ($showRefresh && $name !== '') {
        echo '<button type="button" class="vs-edgeone-domain-row-refresh" data-domain="' . vs_e($name) . '" title="刷新状态" aria-label="刷新状态">';
        echo '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M13.5 8A5.5 5.5 0 1 1 8 2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M8 1v3.5H11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        echo '</button>';
    }
    echo '</span>';

    return ob_get_clean();
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
 * @param array<string, array<string, mixed>> $certIndex
 * @return string
 */
function vs_edgeone_render_domain_list_body(array $domains, array $ddosData, array $certIndex = array())
{
    ob_start();
    if (count($domains) === 0) {
        echo '<p class="vs-form-tip">暂无加速域名，点击「添加域名」创建</p>';
    } else {
        echo '<div class="vs-edgeone-domain-cards" id="edgeoneDomainCards">';
        foreach ($domains as $row) {
            if (!is_array($row)) {
                continue;
            }
            echo vs_edgeone_render_domain_card($row, $ddosData, $certIndex);
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
            echo vs_edgeone_render_domain_table_row($row, $ddosData, $certIndex);
        }
        echo '</tbody></table>';
        echo '<p class="vs-edgeone-domains-table__foot" id="edgeoneDomainsCount">共 ' . count($domains) . ' 条</p>';
        echo '</div>';
    }

    return ob_get_clean();
}

/**
 * @param array<int, array<string, mixed>> $domains
 * @param array<string, mixed>             $ddosData
 * @param string                           $error
 * @param string                           $zoneId
 * @param bool                             $canManage
 * @param array<string, array<string, mixed>> $certIndex
 * @return string
 */
function vs_edgeone_render_domain_list_panel(array $domains, array $ddosData, $error, $zoneId, $canManage = true, array $certIndex = array())
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
    echo '</div>';
    echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-domains-refresh" id="edgeoneDomainsRefreshBtn" title="刷新域名列表">';
    echo '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M13.5 8A5.5 5.5 0 1 1 8 2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M8 1v3.5H11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    echo '<span>刷新列表</span></button>';
    echo '</div>';

    echo '<div id="edgeoneDomainsListBody" class="vs-edgeone-domains-list-body">';
    echo vs_edgeone_render_domain_list_body($domains, $ddosData, $certIndex);
    echo '</div>';

    echo '</div>';
    return ob_get_clean();
}

/**
 * @param array<string, mixed> $row
 * @param array<string, mixed> $ddosData
 * @param array<string, array<string, mixed>> $certIndex
 * @return string
 */
function vs_edgeone_render_domain_table_row(array $row, array $ddosData, array $certIndex = array())
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
    echo '<td>' . vs_edgeone_render_domain_status_cell($row) . '</td>';
    echo '<td>' . vs_edgeone_render_domain_cname_cell($cname) . '</td>';
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
    echo '<td>' . vs_edgeone_render_domain_https_cell($row, $certIndex) . '</td>';
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
 * @param array<string, array<string, mixed>> $certIndex
 * @return string
 */
function vs_edgeone_render_domain_card(array $row, array $ddosData, array $certIndex = array())
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
    echo vs_edgeone_render_domain_status_cell($row);
    echo '</div>';
    echo '<div class="vs-edgeone-domain-card__cname">' . vs_edgeone_render_domain_cname_cell($cname) . '</div>';
    echo '<dl class="vs-edgeone-domain-card__meta">';
    echo '<div><dt>源站类型</dt><dd>' . vs_e($originType) . '</dd></div>';
    echo '<div><dt>源站配置</dt><dd>' . vs_e($originVal) . '</dd></div>';
    echo '<div><dt>HTTPS</dt><dd>' . vs_edgeone_render_domain_https_cell($row, $certIndex) . '</dd></div>';
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
    echo '<div class="vs-edgeone-zone-create-drawer vs-edgeone-domain-form-drawer" id="edgeoneDomainCreateDrawer" hidden aria-hidden="true">';
    echo '<div class="vs-edgeone-zone-create-drawer__overlay" data-domain-drawer-close></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__panel" role="dialog" aria-modal="true">';
    echo '<div class="vs-edgeone-zone-create-drawer__handle" aria-hidden="true"></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__head">';
    echo '<h4>添加域名</h4>';
    echo '<button type="button" class="vs-edgeone-zone-create-drawer__close" data-domain-drawer-close aria-label="关闭">';
    echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
    echo '</button></div>';
    echo '<form class="vs-form vs-edgeone-api-form" id="edgeoneDomainCreateForm" data-action="domain_create" data-reload="none">';
    echo '<div class="vs-edgeone-zone-create-drawer__body">';
    echo '<p class="vs-form-tip">请添加域名，以开启安全加速服务</p>';
    $rootDomain = vs_edgeone_zone_root_domain($zone);
    echo '<div class="vs-form-row"><label class="vs-label">加速域名</label>';
    if ($rootDomain !== '') {
        echo '<p class="vs-form-tip vs-edgeone-domain-prefix-tip">仅需填写子域名前缀，主域名 <strong>' . vs_e($rootDomain) . '</strong> 已与站点绑定</p>';
        echo '<div class="vs-edgeone-domain-prefix-field">';
        echo '<input type="text" name="domain_prefix" class="vs-input" placeholder="www 或 api" required autocomplete="off">';
        echo '<span class="vs-edgeone-domain-prefix-suffix">.' . vs_e($rootDomain) . '</span>';
        echo '</div>';
    } else {
        echo '<input type="text" name="domain_prefix" class="vs-input" placeholder="www" required>';
    }
    echo '</div>';
    echo '<div class="vs-form-row"><label class="vs-label">IPv6 访问</label>';
    echo vs_edgeone_render_ipv6_segment('ipv6_status', 'follow');
    echo '</div>';
    echo '<h5 class="vs-edgeone-subtitle">回源配置</h5>';
    echo '<input type="hidden" name="origin_type" value="IP_DOMAIN">';
    echo '<div class="vs-form-row"><label class="vs-label">源站类型</label>';
    echo '<p class="vs-edgeone-field-static">IP / 域名</p></div>';
    echo '<div class="vs-form-row"><label class="vs-label">源站地址</label>';
    echo '<input type="text" name="origin" class="vs-input" placeholder="IP 或域名" required></div>';
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
    echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--default" data-domain-drawer-close>取消</button>';
    echo '<button type="submit" class="vs-btn vs-btn--rect vs-btn--primary">创建</button>';
    echo '</div></form></div></div>';

    echo '<div class="vs-edgeone-zone-create-drawer vs-edgeone-domain-form-drawer" id="edgeoneDomainEditDrawer" hidden aria-hidden="true">';
    echo '<div class="vs-edgeone-zone-create-drawer__overlay" data-domain-drawer-close></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__panel" role="dialog" aria-modal="true">';
    echo '<div class="vs-edgeone-zone-create-drawer__handle" aria-hidden="true"></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__head"><h4>编辑域名</h4>';
    echo '<button type="button" class="vs-edgeone-zone-create-drawer__close" data-domain-drawer-close aria-label="关闭">';
    echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
    echo '</button></div>';
    echo '<form class="vs-form vs-edgeone-api-form" id="edgeoneDomainEditForm" data-action="domain_modify" data-reload="none">';
    echo '<div class="vs-edgeone-zone-create-drawer__body">';
    echo '<input type="hidden" name="domain_name" id="edgeoneDomainEditName" value="">';
    echo '<p class="vs-form-tip" id="edgeoneDomainEditTitle"></p>';
    echo '<div class="vs-form-row"><label class="vs-label">IPv6 访问</label>';
    echo '<div id="edgeoneDomainEditIpv6Wrap">' . vs_edgeone_render_ipv6_segment('ipv6_status', 'follow') . '</div></div>';
    echo '<input type="hidden" name="origin_type" value="IP_DOMAIN">';
    echo '<div class="vs-form-row"><label class="vs-label">源站类型</label>';
    echo '<p class="vs-edgeone-field-static" id="edgeoneDomainEditOriginTypeLabel">IP / 域名</p></div>';
    echo '<div class="vs-form-row"><label class="vs-label">源站地址</label>';
    echo '<input type="text" name="origin" class="vs-input" id="edgeoneDomainEditOrigin"></div>';
    echo '<div class="vs-form-row"><label class="vs-label">回源协议</label><select name="origin_protocol" class="vs-input" id="edgeoneDomainEditProtocol">';
    echo '<option value="FOLLOW">协议跟随</option><option value="HTTP">HTTP</option><option value="HTTPS">HTTPS</option></select></div>';
    echo '<div class="vs-form-row vs-form-row--inline">';
    echo '<div class="vs-form-col"><label class="vs-label">HTTP 端口</label><input type="number" name="http_port" class="vs-input" id="edgeoneDomainEditHttpPort" min="1" max="65535"></div>';
    echo '<div class="vs-form-col"><label class="vs-label">HTTPS 端口</label><input type="number" name="https_port" class="vs-input" id="edgeoneDomainEditHttpsPort" min="1" max="65535"></div>';
    echo '</div></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__foot">';
    echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--default" data-domain-drawer-close>取消</button>';
    echo '<button type="submit" class="vs-btn vs-btn--rect vs-btn--primary">保存</button>';
    echo '</div></form></div></div>';

    $zoneType = $zone !== null && isset($zone['Type']) ? strtolower((string) $zone['Type']) : '';
    $autoFreeCert = in_array($zoneType, array('full', 'partial', 'dnspod'), true);

    echo '<div class="vs-edgeone-zone-create-drawer vs-edgeone-domain-cert-drawer" id="edgeoneDomainCertDrawer" hidden aria-hidden="true">';
    echo '<div class="vs-edgeone-zone-create-drawer__overlay" data-domain-drawer-close></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__panel" role="dialog" aria-modal="true">';
    echo '<div class="vs-edgeone-zone-create-drawer__handle" aria-hidden="true"></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__head"><h4>HTTPS 配置</h4>';
    echo '<button type="button" class="vs-edgeone-zone-create-drawer__close" data-domain-drawer-close aria-label="关闭">';
    echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
    echo '</button></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__body">';
    echo '<p class="vs-form-tip" id="edgeoneDomainCertDomain"></p>';
    echo '<div class="vs-edgeone-cert-status" id="edgeoneDomainCertStatus"></div>';
    echo '<div class="vs-edgeone-cert-actions">';
    echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-cert-status-refresh" id="edgeoneDomainCertRefreshBtn">刷新证书状态</button>';
    if ($autoFreeCert) {
        echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--primary vs-edgeone-cert-deploy" data-mode="eofreecert">部署 EdgeOne 免费证书</button>';
    } else {
        echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--primary vs-edgeone-cert-apply" data-method="http_challenge">申请免费证书（HTTP 验证）</button>';
        echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-cert-apply" data-method="dns_challenge">申请免费证书（DNS 验证）</button>';
    }
    echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-cert-check" id="edgeoneDomainCertCheckBtn" hidden>验证并部署</button>';
    echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-cert-deploy" data-mode="disable">关闭 HTTPS</button>';
    echo '</div>';
    echo '<div class="vs-edgeone-cert-verify" id="edgeoneDomainCertVerify" hidden></div>';
    echo '</div>';
    echo '<div class="vs-edgeone-zone-create-drawer__foot">';
    echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--default" data-domain-drawer-close>关闭</button>';
    echo '</div></div></div>';

    return ob_get_clean();
}
