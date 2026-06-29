<?php
/**
 * 文件：admin/cdn/edgeone/includes/filter-builder.php
 * 作用：EdgeOne L7 指标分析自定义筛选（Filters.N / QueryCondition）
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

    echo '<div class="vs-edgeone-custom-filters" id="edgeoneCustomFilters" data-defs="' . vs_e(json_encode($defs, JSON_UNESCAPED_UNICODE)) . '">';
    echo '<div class="vs-edgeone-custom-filters__head">';
    echo '<span class="vs-edgeone-custom-filters__title">自定义筛选</span>';
    echo '<button type="button" class="vs-btn vs-btn--ghost vs-btn--sm" id="edgeoneAddFilterBtn">+ 添加筛选</button>';
    echo '</div>';
    echo '<p class="vs-form-tip">多个条件之间为「且」关系，同一条件内多个取值为「或」关系。站点与域名请使用上方字段。</p>';
    echo '<div class="vs-edgeone-custom-filters__list" id="edgeoneCustomFiltersList">';

    if (count($active) === 0) {
        echo '<p class="vs-form-tip vs-edgeone-custom-filters__empty" id="edgeoneCustomFiltersEmpty">暂无自定义筛选，点击「添加筛选」按 API 文档配置 Filters.N。</p>';
    }

    foreach ($active as $idx => $row) {
        vs_edgeone_render_custom_filter_row($idx, $row, $defs, $ops);
    }

    echo '</div>';
    echo '<input type="hidden" name="custom_filters_json" id="edgeoneCustomFiltersJson" value="' . vs_e(json_encode($active, JSON_UNESCAPED_UNICODE)) . '">';
    echo '</div>';

    echo '<template id="edgeoneCustomFilterRowTpl">';
    vs_edgeone_render_custom_filter_row('__IDX__', array('key' => '', 'operator' => 'equals', 'values' => array()), $defs, $ops);
    echo '</template>';
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
    $key = isset($row['key']) ? (string) $row['key'] : '';
    $operator = isset($row['operator']) ? (string) $row['operator'] : 'equals';
    $values = isset($row['values']) && is_array($row['values']) ? $row['values'] : array();
    $def = ($key !== '' && isset($defs[$key])) ? $defs[$key] : null;
    $allowedOps = ($def && isset($def['operators'])) ? $def['operators'] : array_keys($ops);

    echo '<div class="vs-edgeone-custom-filter-row" data-idx="' . vs_e((string) $idx) . '">';
    echo '<select class="vs-input vs-edgeone-custom-filter-row__key" aria-label="筛选项">';
    echo '<option value="">选择筛选项</option>';
    foreach ($defs as $k => $item) {
        echo '<option value="' . vs_e($k) . '"' . ($k === $key ? ' selected' : '') . '>' . vs_e($item['label']) . '</option>';
    }
    echo '</select>';

    echo '<select class="vs-input vs-edgeone-custom-filter-row__op" aria-label="运算符">';
    foreach ($allowedOps as $opKey) {
        if (!isset($ops[$opKey])) {
            continue;
        }
        echo '<option value="' . vs_e($opKey) . '"' . ($opKey === $operator ? ' selected' : '') . '>' . vs_e($ops[$opKey]) . '</option>';
    }
    echo '</select>';

    echo '<div class="vs-edgeone-custom-filter-row__value-wrap">';
    if ($def && $def['value_type'] === 'enum' && !empty($def['options'])) {
        echo '<select class="vs-input vs-edgeone-custom-filter-row__value-enum" multiple size="1" aria-label="筛选值">';
        foreach ($def['options'] as $val => $label) {
            $sel = in_array((string) $val, $values, true) ? ' selected' : '';
            echo '<option value="' . vs_e($val) . '"' . $sel . '>' . vs_e($label) . '</option>';
        }
        echo '</select>';
        echo '<input type="hidden" class="vs-edgeone-custom-filter-row__value" value="' . vs_e(implode("\n", $values)) . '">';
    } else {
        $placeholder = ($def && $def['value_type'] === 'multitext') ? '多个值用回车或逗号分隔' : '输入筛选值';
        echo '<input type="text" class="vs-input vs-edgeone-custom-filter-row__value" value="' . vs_e(implode("\n", $values)) . '" placeholder="' . vs_e($placeholder) . '">';
    }
    echo '</div>';

    echo '<button type="button" class="vs-btn vs-btn--ghost vs-btn--sm vs-edgeone-custom-filter-row__remove" aria-label="删除">删除</button>';
    echo '</div>';
}
