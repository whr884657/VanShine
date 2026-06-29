<?php
/**
 * 文件：admin/cdn/edgeone/includes/filter-builder.php
 * 作用：EdgeOne L7 指标分析自定义筛选（Filters.N / QueryCondition）
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

/**
 * @return array<string, array{label: string, value_type: string, operators?: array<int, string>, options?: array<string, string>}>
 */
function vs_edgeone_l7_filter_definitions()
{
    return array(
        'country'                => array('label' => '国家/地区', 'value_type' => 'text', 'operators' => array('equals', 'notEquals')),
        'statusCode'             => array(
            'label'     => '状态码',
            'value_type'=> 'enum',
            'operators' => array('equals', 'notEquals'),
            'options'   => array(
                '1XX' => '1XX', '2XX' => '2XX', '3XX' => '3XX', '4XX' => '4XX', '5XX' => '5XX',
            ),
        ),
        'protocol'               => array(
            'label'     => 'HTTP 协议',
            'value_type'=> 'enum',
            'operators' => array('equals', 'notEquals'),
            'options'   => array(
                'HTTP/1.0' => 'HTTP/1.0', 'HTTP/1.1' => 'HTTP/1.1', 'HTTP/2.0' => 'HTTP/2.0',
                'HTTP/3' => 'HTTP/3', 'WebSocket' => 'WebSocket',
            ),
        ),
        'isp'                    => array(
            'label'     => '运营商',
            'value_type'=> 'enum',
            'operators' => array('equals', 'notEquals'),
            'options'   => array(
                '2' => '中国电信', '26' => '中国联通', '1046' => '中国移动', '3947' => '中国铁通',
                '38' => '教育网', '43' => '长城宽带', '0' => '其他运营商',
            ),
        ),
        'province'               => array('label' => '省份', 'value_type' => 'text', 'operators' => array('equals', 'notEquals')),
        'tlsVersion'             => array(
            'label'     => 'TLS 版本',
            'value_type'=> 'enum',
            'operators' => array('equals', 'notEquals'),
            'options'   => array(
                'TLS1.0' => 'TLS1.0', 'TLS1.1' => 'TLS1.1', 'TLS1.2' => 'TLS1.2', 'TLS1.3' => 'TLS1.3',
            ),
        ),
        'url'                    => array(
            'label'     => 'URL Path',
            'value_type'=> 'text',
            'operators' => array('equals', 'notEquals', 'contains', 'notContains', 'startsWith', 'notStartsWith', 'endsWith', 'notEndsWith'),
        ),
        'referer'                => array(
            'label'     => 'Referer',
            'value_type'=> 'text',
            'operators' => array('equals', 'notEquals', 'contains', 'notContains', 'startsWith', 'notStartsWith', 'endsWith', 'notEndsWith'),
        ),
        'resourceType'           => array(
            'label'     => '资源类型',
            'value_type'=> 'text',
            'operators' => array('equals', 'notEquals', 'contains', 'notContains', 'startsWith', 'notStartsWith', 'endsWith', 'notEndsWith'),
        ),
        'deviceType'             => array(
            'label'     => '设备类型',
            'value_type'=> 'enum',
            'operators' => array('equals', 'notEquals'),
            'options'   => array(
                'TV' => '电视', 'Tablet' => '平板电脑', 'Mobile' => '手机', 'Desktop' => '电脑', 'Other' => '其他',
            ),
        ),
        'browserType'            => array(
            'label'     => '浏览器类型',
            'value_type'=> 'enum',
            'operators' => array('equals', 'notEquals'),
            'options'   => array(
                'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari', 'MicrosoftEdge' => 'Edge',
                'IE' => 'IE', 'Opera' => 'Opera', 'QQBrowser' => 'QQ浏览器', 'Bot' => '爬虫', 'Other' => '其他',
            ),
        ),
        'operatingSystemType'    => array(
            'label'     => '客户端操作系统',
            'value_type'=> 'enum',
            'operators' => array('equals', 'notEquals'),
            'options'   => array(
                'Windows' => 'Windows', 'Linux' => 'Linux', 'MacOS' => 'MacOS', 'Android' => 'Android',
                'IOS' => 'iOS', 'Bot' => '爬虫', 'Other' => '其他',
            ),
        ),
        'ipVersion'              => array(
            'label'     => 'IP 版本',
            'value_type'=> 'enum',
            'operators' => array('equals', 'notEquals'),
            'options'   => array('4' => 'IPv4', '6' => 'IPv6'),
        ),
        'socket'                 => array(
            'label'     => 'HTTP/HTTPS',
            'value_type'=> 'enum',
            'operators' => array('equals', 'notEquals'),
            'options'   => array('HTTP' => 'HTTP', 'HTTPS' => 'HTTPS'),
        ),
        'cacheType'              => array(
            'label'     => '缓存状态',
            'value_type'=> 'enum',
            'operators' => array('equals', 'notEquals'),
            'options'   => array(
                'hit' => '命中', 'miss' => '未命中', 'dynamic' => '动态', 'other' => '其他',
            ),
        ),
        'mitigatedByWebSecurity' => array(
            'label'     => 'Web 防护缓释',
            'value_type'=> 'enum',
            'operators' => array('equals', 'notEquals'),
            'options'   => array('yes' => '是', 'no' => '否'),
        ),
        'clientIp'               => array('label' => '客户端 IP', 'value_type' => 'multitext', 'operators' => array('equals', 'notEquals')),
        'userAgent'              => array(
            'label'     => 'User-Agent',
            'value_type'=> 'text',
            'operators' => array('equals', 'notEquals', 'contains', 'notContains', 'startsWith', 'notStartsWith', 'endsWith', 'notEndsWith'),
        ),
    );
}

/**
 * @return array<string, string>
 */
function vs_edgeone_filter_operator_labels()
{
    return array(
        'equals'        => '等于',
        'notEquals'     => '不等于',
        'contains'      => '包含',
        'notContains'   => '不包含',
        'startsWith'    => '开始于',
        'notStartsWith' => '不开始于',
        'endsWith'      => '结尾是',
        'notEndsWith'   => '结尾不是',
    );
}

/**
 * @param mixed $raw
 * @return array<int, array{key: string, operator: string, values: array<int, string>}>
 */
function vs_edgeone_custom_filters_normalize($raw)
{
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : array();
    }
    if (!is_array($raw)) {
        return array();
    }

    $defs = vs_edgeone_l7_filter_definitions();
    $ops = vs_edgeone_filter_operator_labels();
    $out = array();

    foreach ($raw as $row) {
        if (!is_array($row)) {
            continue;
        }
        $key = isset($row['key']) ? trim((string) $row['key']) : '';
        if ($key === '' || !isset($defs[$key])) {
            continue;
        }
        $def = $defs[$key];
        $operator = isset($row['operator']) ? trim((string) $row['operator']) : 'equals';
        $allowedOps = isset($def['operators']) ? $def['operators'] : array_keys($ops);
        if (!in_array($operator, $allowedOps, true)) {
            $operator = $allowedOps[0];
        }

        $values = array();
        if (isset($row['values']) && is_array($row['values'])) {
            foreach ($row['values'] as $v) {
                $v = trim((string) $v);
                if ($v !== '') {
                    $values[] = $v;
                }
            }
        } elseif (isset($row['value'])) {
            $parts = preg_split('/[\r\n,;|]+/', (string) $row['value']);
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $values[] = $part;
                }
            }
        }

        if (count($values) === 0) {
            continue;
        }

        $out[] = array(
            'key'      => $key,
            'operator' => $operator,
            'values'   => array_values(array_unique($values)),
        );
    }

    return $out;
}

/**
 * @param array<int, array{key: string, operator: string, values: array<int, string>}> $customFilters
 * @param array<int, string> $excludeKeys
 * @return array<int, array{Key: string, Operator: string, Value: array<int, string>}>
 */
function vs_edgeone_custom_filters_to_api(array $customFilters, array $excludeKeys = array())
{
    $exclude = array_flip($excludeKeys);
    $out = array();

    foreach ($customFilters as $row) {
        $key = isset($row['key']) ? (string) $row['key'] : '';
        if ($key === '' || isset($exclude[$key])) {
            continue;
        }
        $out[] = vs_edgeone_analytics_filter(
            $key,
            isset($row['values']) ? $row['values'] : array(),
            isset($row['operator']) ? (string) $row['operator'] : 'equals'
        );
    }

    return $out;
}

/**
 * @param array{range: string, filter_zone: string, filter_domain: string, custom_filters: array} $filters
 * @return void
 */
function vs_edgeone_render_overview_custom_filters(array $filters)
{
    $defs = vs_edgeone_l7_filter_definitions();
    $ops = vs_edgeone_filter_operator_labels();
    $active = isset($filters['custom_filters']) && is_array($filters['custom_filters'])
        ? $filters['custom_filters']
        : array();

    echo '<div class="vs-form-col vs-form-col--custom vs-edgeone-custom-filters" id="edgeoneCustomFilters"';
    echo ' data-defs="' . vs_e(json_encode($defs, JSON_UNESCAPED_UNICODE)) . '"';
    echo ' data-ops="' . vs_e(json_encode($ops, JSON_UNESCAPED_UNICODE)) . '">';

    echo '<label class="vs-label">';
    echo '自定义筛选 ';
    vs_edgeone_render_help_tip('对应 API 的 Filters.N。多个条件为且关系，同一条件内多个取值为或关系。');
    echo '</label>';

    echo '<div class="vs-edgeone-custom-filters__bar">';
    echo '<button type="button" class="vs-edgeone-filter-add-btn" id="edgeoneAddFilterBtn"><span class="vs-edgeone-filter-add-btn__icon">+</span>添加筛选</button>';
    echo '<div class="vs-edgeone-custom-filters__chips" id="edgeoneCustomFilterChips"></div>';
    echo '</div>';

    echo '<input type="hidden" name="custom_filters_json" id="edgeoneCustomFiltersJson" value="' . vs_e(json_encode($active, JSON_UNESCAPED_UNICODE)) . '">';
    echo '</div>';

    echo '<div class="vs-edgeone-filter-drawer" id="edgeoneFilterDrawer" hidden aria-hidden="true">';
    echo '<div class="vs-edgeone-filter-drawer__overlay" data-filter-drawer-close></div>';
    echo '<div class="vs-edgeone-filter-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="edgeoneFilterDrawerTitle">';
    echo '<div class="vs-edgeone-filter-drawer__handle" aria-hidden="true"></div>';
    echo '<div class="vs-edgeone-filter-drawer__head">';
    echo '<h4 class="vs-edgeone-filter-drawer__title" id="edgeoneFilterDrawerTitle">添加筛选条件</h4>';
    echo '<button type="button" class="vs-edgeone-filter-drawer__close" data-filter-drawer-close aria-label="关闭">';
    echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
    echo '</button></div>';
    echo '<div class="vs-edgeone-filter-drawer__body">';
    echo '<div class="vs-edgeone-filter-drawer__layout">';
    echo '<div class="vs-edgeone-filter-drawer__side">';
    echo '<label class="vs-label">筛选项</label>';
    echo '<button type="button" class="vs-edgeone-filter-picker" id="edgeoneFilterKeyPicker" data-empty="选择筛选项">';
    echo '<span id="edgeoneFilterKeyLabel">选择筛选项</span><svg width="12" height="12" viewBox="0 0 12 12"><path d="M3 4.5 6 7.5 9 4.5" stroke="currentColor" fill="none"/></svg>';
    echo '</button>';
    echo '<input type="hidden" id="edgeoneCustomFilterKey" value="">';
    echo '<div class="vs-edgeone-filter-picker-menu" id="edgeoneFilterKeyMenu" hidden>';
    foreach ($defs as $k => $item) {
        echo '<button type="button" class="vs-edgeone-filter-picker-menu__item" data-value="' . vs_e($k) . '">' . vs_e($item['label']) . '</button>';
    }
    echo '</div>';
    echo '<label class="vs-label">匹配方式</label>';
    echo '<button type="button" class="vs-edgeone-filter-picker" id="edgeoneFilterOpPicker" data-empty="等于">';
    echo '<span id="edgeoneFilterOpLabel">等于</span><svg width="12" height="12" viewBox="0 0 12 12"><path d="M3 4.5 6 7.5 9 4.5" stroke="currentColor" fill="none"/></svg>';
    echo '</button>';
    echo '<input type="hidden" id="edgeoneCustomFilterOp" value="equals">';
    echo '<div class="vs-edgeone-filter-picker-menu" id="edgeoneFilterOpMenu" hidden></div>';
    echo '</div>';
    echo '<div class="vs-edgeone-filter-drawer__values" id="edgeoneCustomFilterValueWrap">';
    echo '<p class="vs-form-tip">请先选择筛选项</p>';
    echo '</div>';
    echo '</div></div>';
    echo '<div class="vs-edgeone-filter-drawer__foot">';
    echo '<button type="button" class="vs-btn vs-btn--ghost" data-filter-drawer-close>取消</button>';
    echo '<button type="button" class="vs-btn vs-btn--primary" id="edgeoneCustomFilterConfirm">确定</button>';
    echo '</div></div></div>';
}

/**
 * @param int|string $idx
 * @param array{key?: string, operator?: string, values?: array<int, string>} $row
 * @param array $defs
 * @param array $ops
 * @return void
 */
function vs_edgeone_render_custom_filter_row($idx, array $row, array $defs, array $ops)
{
    // 保留供兼容；UI 已改为弹层 + 标签，不再输出多行表单
}
