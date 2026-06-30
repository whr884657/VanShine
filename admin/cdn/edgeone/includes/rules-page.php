<?php
/**
 * 文件：admin/cdn/edgeone/includes/rules-page.php
 * 作用：规则引擎页（rules.php）的数据拉取与 HTML 渲染
 *
 * 说明：
 * - 封装 DescribeL7AccRules 及规则 CRUD 所需的 Branches/Actions 构建
 * - 完整规则编辑由 includes/rules-editor.php + edgeone-rules-editor.js 实现
 * - 列表 UI 由 vs_edgeone_render_rules_* 系列函数输出
 */

/**
 * @param EdgeOne $eo
 * @param string  $zoneId
 * @return array{rules: array<int, array<string, mixed>>, error: string, total: int}
 */
function vs_edgeone_fetch_l7_rules_data(EdgeOne $eo, $zoneId)
{
    $out = array('rules' => array(), 'error' => '', 'total' => 0);
    if ($zoneId === '') {
        return $out;
    }

    $result = vs_edgeone_try_call(function () use ($eo, $zoneId) {
        return $eo->l7Acc->describeL7AccRules(array(
            'ZoneId' => $zoneId,
            'Offset' => 0,
            'Limit'  => 1000,
        ));
    }, array());

    if (!$result['ok']) {
        $out['error'] = $result['error'];
        return $out;
    }

    $data = is_array($result['data']) ? $result['data'] : array();
    $out['rules'] = isset($data['Rules']) && is_array($data['Rules']) ? $data['Rules'] : array();
    $out['total'] = isset($data['TotalCount']) ? (int) $data['TotalCount'] : count($out['rules']);

    return $out;
}

/**
 * @param array<int, array<string, mixed>> $rules
 * @param string                          $ruleId
 * @return array<string, mixed>|null
 */
function vs_edgeone_find_l7_rule_by_id(array $rules, $ruleId)
{
    foreach ($rules as $rule) {
        if (!is_array($rule)) {
            continue;
        }
        if (isset($rule['RuleId']) && (string) $rule['RuleId'] === (string) $ruleId) {
            return $rule;
        }
    }

    return null;
}

/**
 * @param string $host
 * @return string
 */
function vs_edgeone_build_host_rule_condition($host)
{
    $host = trim((string) $host);
    if ($host === '' || $host === '*') {
        return '${http.request.host} ne \'\'';
    }

    return '${http.request.host} in [\'' . str_replace(array('\\', '\''), array('\\\\', '\\\''), $host) . '\']';
}

/**
 * @param string $condition
 * @return string
 */
function vs_edgeone_parse_host_from_condition($condition)
{
    $condition = trim((string) $condition);
    if ($condition === '' || $condition === '${http.request.host} ne \'\'') {
        return '';
    }
    if (preg_match("/\\['([^']*)'\\]/", $condition, $m)) {
        return str_replace("\\'", "'", $m[1]);
    }

    return '';
}

/**
 * @param string $condition
 * @return string
 */
function vs_edgeone_humanize_condition($condition)
{
    $condition = trim((string) $condition);
    if ($condition === '' || $condition === "${http.request.host} ne ''") {
        return '全部（站点任意请求）';
    }
    if (preg_match("/\\\\\\\\{http\\.request\\.host\\\\} in \\\\['([^']*)'\\\\]/", $condition, $m)) {
        return 'HOST 等于 ' . str_replace("\\'", "'", $m[1]);
    }
    if (preg_match("/host\\}\\s+in\\s+\\['([^']*)'\\]/", $condition, $m)) {
        return 'HOST 等于 ' . str_replace("\\'", "'", $m[1]);
    }
    if (preg_match("/file_extension\\}\\s+in\\s+\\[(.*?)\\]/", $condition, $m)) {
        return '文件后缀 等于 ' . preg_replace("/'\\s*,\\s*'/", '、', trim($m[1], "'"));
    }
    if (preg_match("/uri\\.path\\}\\s+in\\s+\\['([^']*)'\\]/", $condition, $m)) {
        return 'URL Path 等于 ' . str_replace("\\'", "'", $m[1]);
    }
    return '已配置匹配条件';
}

/**
 * @param array<string, mixed> $rule
 * @return array{branches: int, subrules: int, actions: int, cond_hint: string}
 */
function vs_edgeone_rule_list_stats(array $rule)
{
    $branches = 0;
    $subrules = 0;
    $actions = 0;
    $condHint = '';

    if (isset($rule['Branches']) && is_array($rule['Branches'])) {
        $branches = count($rule['Branches']);
        foreach ($rule['Branches'] as $branch) {
            if (!is_array($branch)) {
                continue;
            }
            if ($condHint === '' && isset($branch['Condition'])) {
                $cond = trim((string) $branch['Condition']);
                if ($cond !== '') {
                    $condHint = vs_edgeone_humanize_condition($cond);
                }
            }
            if (isset($branch['Actions']) && is_array($branch['Actions'])) {
                $actions += count($branch['Actions']);
            }
            if (!isset($branch['SubRules']) || !is_array($branch['SubRules'])) {
                continue;
            }
            foreach ($branch['SubRules'] as $sub) {
                $subrules++;
                if (!is_array($sub) || !isset($sub['Branches'][0]['Actions']) || !is_array($sub['Branches'][0]['Actions'])) {
                    continue;
                }
                $actions += count($sub['Branches'][0]['Actions']);
            }
        }
    }

    return array(
        'branches' => $branches,
        'subrules' => $subrules,
        'actions'  => $actions,
        'cond_hint' => $condHint,
    );
}

/**
 * @param array<string, mixed> $rule
 * @return string
 */
function vs_edgeone_rule_list_summary(array $rule)
{
    $stats = vs_edgeone_rule_list_stats($rule);
    $parts = array();
    if ($stats['branches'] > 0) {
        $parts[] = $stats['branches'] . ' 个 IF';
    }
    if ($stats['subrules'] > 0) {
        $parts[] = $stats['subrules'] . ' 个子规则';
    }
    if ($stats['actions'] > 0) {
        $parts[] = $stats['actions'] . ' 个操作';
    }

    return count($parts) > 0 ? implode(' · ', $parts) : '暂无分支配置';
}

/**
 * @param array<string, mixed> $rule
 * @return string
 */
function vs_edgeone_rule_search_text(array $rule)
{
    $parts = array();
    if (isset($rule['RuleName'])) {
        $parts[] = (string) $rule['RuleName'];
    }
    if (isset($rule['RuleId'])) {
        $parts[] = (string) $rule['RuleId'];
    }
    if (isset($rule['Description']) && is_array($rule['Description'])) {
        foreach ($rule['Description'] as $desc) {
            $parts[] = (string) $desc;
        }
    }
    if (isset($rule['Branches']) && is_array($rule['Branches'])) {
        foreach ($rule['Branches'] as $branch) {
            if (is_array($branch) && isset($branch['Condition'])) {
                $parts[] = (string) $branch['Condition'];
            }
        }
    }

    return strtolower(implode(' ', $parts));
}

/**
 * @param array<int, array<string, mixed>> $zones
 * @param string                          $zoneId
 * @param array<string, mixed>|null       $zone
 * @param bool                            $canManage
 * @return string
 */
function vs_edgeone_render_rules_site_panel(array $zones, $zoneId, $zone, $canManage = true)
{
    $selected = vs_edgeone_selected_zone();

    ob_start();
    echo '<div class="vs-panel vs-edgeone-rules-site-panel">';
    echo '<form method="post" class="vs-edgeone-domain-toolbar" id="edgeoneRulesZoneForm">';
    echo '<input type="hidden" name="action" value="set_zone">';
    echo '<div class="vs-edgeone-domain-toolbar__row vs-edgeone-domain-toolbar__row--select">';
    echo '<div class="vs-edgeone-domain-toolbar__field">';
    echo '<label class="vs-edgeone-domain-toolbar__label" for="edgeoneRulesZoneSelect">站点</label>';
    echo '<select id="edgeoneRulesZoneSelect" name="zone_id" class="vs-input vs-edgeone-zone-select" aria-label="选择站点">';
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
        echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--primary" id="edgeoneRuleCreateBtn">创建规则</button>';
    } else {
        echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--primary" disabled title="Pages 云端部署站点不支持在此管理">创建规则</button>';
    }
    echo '</div>';
    if ($zoneId !== '' && $zone !== null) {
        $root = vs_edgeone_zone_root_domain($zone);
        echo '<div class="vs-edgeone-rules-site-meta">';
        echo '<p class="vs-edgeone-rules-site-meta__hint">针对站点 <strong>' . vs_e($root !== '' ? $root : (isset($zone['ZoneName']) ? (string) $zone['ZoneName'] : '')) . '</strong> 下特定域名生效的差异化加速配置</p>';
        echo '</div>';
    }
    echo '</form></div>';

    return ob_get_clean();
}

/**
 * @param array<int, array<string, mixed>> $rules
 * @param string                           $error
 * @param bool                             $canManage
 * @return string
 */
function vs_edgeone_render_rules_list_body(array $rules, $error, $canManage = true)
{
    ob_start();
    if ($error !== '') {
        echo '<p class="vs-form-tip">加载失败：' . vs_e($error) . '</p>';
    } elseif (count($rules) === 0) {
        echo '<p class="vs-form-tip">暂无规则，点击「创建规则」添加</p>';
    } else {
        echo '<ul class="vs-edgeone-rules-list" id="edgeoneRulesList">';
        $index = 0;
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $index++;
            echo vs_edgeone_render_rule_list_item($rule, $index, $canManage);
        }
        echo '</ul>';
    }

    return ob_get_clean();
}

/**
 * @param array<string, mixed> $rule
 * @param int                  $index
 * @param bool                 $canManage
 * @return string
 */
function vs_edgeone_render_rule_list_item(array $rule, $index, $canManage = true)
{
    $ruleId = isset($rule['RuleId']) ? (string) $rule['RuleId'] : '';
    $name = isset($rule['RuleName']) ? (string) $rule['RuleName'] : '未命名规则';
    $status = isset($rule['Status']) ? strtolower((string) $rule['Status']) : 'disable';
    $enabled = $status === 'enable';
    $search = vs_edgeone_rule_search_text($rule);
    $summary = vs_edgeone_rule_list_summary($rule);
    $stats = vs_edgeone_rule_list_stats($rule);
    $num = str_pad((string) $index, 2, '0', STR_PAD_LEFT);

    ob_start();
    echo '<li class="vs-edgeone-rules-item" data-rule-id="' . vs_e($ruleId) . '" data-rule-search="' . vs_e($search) . '" draggable="' . ($canManage ? 'true' : 'false') . '">';
    echo '<span class="vs-edgeone-rules-item__index">' . vs_e($num) . '</span>';
    if ($canManage) {
        echo '<span class="vs-edgeone-rules-item__drag" title="拖拽调整优先级" aria-hidden="true">';
        echo '<svg width="12" height="16" viewBox="0 0 12 16" fill="currentColor"><circle cx="3" cy="3" r="1.2"/><circle cx="9" cy="3" r="1.2"/><circle cx="3" cy="8" r="1.2"/><circle cx="9" cy="8" r="1.2"/><circle cx="3" cy="13" r="1.2"/><circle cx="9" cy="13" r="1.2"/></svg>';
        echo '</span>';
    }
    echo '<div class="vs-edgeone-rules-item__main">';
    echo '<div class="vs-edgeone-rules-item__title-row">';
    echo '<span class="vs-edgeone-rules-item__name">' . vs_e($name) . '</span>';
    if ($canManage) {
        echo '<label class="vs-edgeone-rules-switch vs-edgeone-rules-switch--list" title="' . ($enabled ? '已启用' : '已关闭') . '">';
        echo '<input type="checkbox" class="vs-edgeone-rule-status-toggle" data-rule-id="' . vs_e($ruleId) . '"' . ($enabled ? ' checked' : '') . '>';
        echo '<span class="vs-edgeone-rules-switch__track"></span>';
        echo '</label>';
    }
    echo '</div>';
    echo '<span class="vs-edgeone-rules-item__summary">' . vs_e($summary) . '</span>';
    if ($stats['cond_hint'] !== '') {
        echo '<span class="vs-edgeone-rules-item__cond" title="' . vs_e($stats['cond_hint']) . '">' . vs_e($stats['cond_hint']) . '</span>';
    }
    if (isset($rule['Description']) && is_array($rule['Description']) && count($rule['Description']) > 0) {
        echo '<span class="vs-edgeone-rules-item__comment">' . vs_e((string) $rule['Description'][0]) . '</span>';
    }
    echo '</div>';
    echo '<div class="vs-edgeone-rules-item__actions">';
    if ($canManage) {
        echo '<button type="button" class="vs-edgeone-rules-icon-btn vs-edgeone-rule-edit" data-rule-id="' . vs_e($ruleId) . '" title="编辑" aria-label="编辑">';
        echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M11.5 2.5l2 2L5 13H3v-2l8.5-8.5z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>';
        echo '</button>';
        echo '<button type="button" class="vs-edgeone-rules-icon-btn vs-edgeone-rule-copy" data-rule-id="' . vs_e($ruleId) . '" title="复制" aria-label="复制">';
        echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="5" y="5" width="8" height="8" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M3 11V3.5A1.5 1.5 0 0 1 4.5 2H11" stroke="currentColor" stroke-width="1.4"/></svg>';
        echo '</button>';
        echo '<button type="button" class="vs-edgeone-rules-icon-btn vs-edgeone-rule-delete" data-rule-id="' . vs_e($ruleId) . '" title="删除" aria-label="删除">';
        echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 4h10M6 4V3h4v1M5 4v9h6V4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        echo '</button>';
    }
    echo '</div></li>';

    return ob_get_clean();
}

/**
 * @param array<int, array<string, mixed>> $rules
 * @param string                           $error
 * @param string                           $zoneId
 * @param bool                             $canManage
 * @return string
 */
function vs_edgeone_render_rules_list_panel(array $rules, $error, $zoneId, $canManage = true)
{
    $total = count($rules);

    ob_start();
    echo '<div class="vs-panel vs-edgeone-rules-list-panel" id="edgeoneRulesListPanel">';

    if ($zoneId === '') {
        echo '<p class="vs-form-tip">请先选择站点</p>';
        echo '</div>';
        return ob_get_clean();
    }
    if (!$canManage) {
        echo '<p class="vs-form-tip">当前站点为 Pages 云端部署，请在 EdgeOne Pages 控制台管理规则。</p>';
        echo '</div>';
        return ob_get_clean();
    }

    echo '<div class="vs-edgeone-rules-intro">';
    echo '<div class="vs-edgeone-rules-intro__head">';
    echo '<div class="vs-edgeone-rules-intro__icon" aria-hidden="true">';
    echo '<svg width="18" height="18" viewBox="0 0 18 18" fill="none"><rect x="2" y="2" width="6" height="6" rx="1" fill="currentColor" opacity=".85"/><rect x="10" y="2" width="6" height="6" rx="1" fill="currentColor" opacity=".55"/><rect x="2" y="10" width="6" height="6" rx="1" fill="currentColor" opacity=".55"/><rect x="10" y="10" width="6" height="6" rx="1" fill="currentColor" opacity=".35"/></svg>';
    echo '</div>';
    echo '<div><h3 class="vs-edgeone-rules-intro__title">规则引擎</h3>';
    echo '<p class="vs-edgeone-rules-intro__desc">针对特定域名生效的差异化配置</p></div>';
    echo '</div></div>';

    echo '<p class="vs-edgeone-rules-priority-tip">共 <span id="edgeoneRulesCount">' . $total . '</span> 条规则，新增规则默认位于最下方。执行顺序依次往下，<strong>下方优先级更高</strong>；若同时匹配多条规则，下方规则的操作将覆盖上方。详见 <a href="https://cloud.tencent.com/document/product/1552/70901" target="_blank" rel="noopener">规则引擎说明</a>。</p>';

    echo '<div class="vs-edgeone-rules-toolbar-inner">';
    echo '<div class="vs-edgeone-domains-search">';
    echo '<input type="search" class="vs-input" id="edgeoneRulesSearch" placeholder="搜索名称/注释" aria-label="搜索规则">';
    echo '</div>';
    echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-rules-refresh" id="edgeoneRulesRefreshBtn" title="刷新规则列表">';
    echo '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M13.5 8A5.5 5.5 0 1 1 8 2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M8 1v3.5H11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    echo '<span>刷新列表</span></button>';
    echo '</div>';

    echo '<div id="edgeoneRulesListBody" class="vs-edgeone-rules-list-body">';
    echo vs_edgeone_render_rules_list_body($rules, $error, $canManage);
    echo '</div>';

    echo '</div>';
    return ob_get_clean();
}

/**
 * @return string
 */
function vs_edgeone_render_rule_editor_shell()
{
    return vs_edgeone_render_rule_editor_modal();
}

/**
 * @param array<string, mixed> $rule
 * @return array<string, mixed>
 */
function vs_edgeone_rule_item_for_copy(array $rule)
{
    unset($rule['RuleId'], $rule['RulePriority']);
    $name = isset($rule['RuleName']) ? (string) $rule['RuleName'] : '未命名规则';
    $rule['RuleName'] = $name . ' 副本';
    $rule['Status'] = 'disable';

    return $rule;
}
