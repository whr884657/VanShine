<?php
/**
 * 文件：admin/cdn/edgeone/includes/rules-page.php
 * 作用：站点加速规则引擎页渲染与数据
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
    echo '<span class="vs-edgeone-rules-item__name">' . vs_e($name) . '</span>';
    if (isset($rule['Description']) && is_array($rule['Description']) && count($rule['Description']) > 0) {
        echo '<span class="vs-edgeone-rules-item__comment">' . vs_e((string) $rule['Description'][0]) . '</span>';
    }
    echo '</div>';
    echo '<div class="vs-edgeone-rules-item__actions">';
    if ($canManage) {
        echo '<label class="vs-edgeone-rules-switch" title="' . ($enabled ? '已启用' : '已关闭') . '">';
        echo '<input type="checkbox" class="vs-edgeone-rule-status-toggle" data-rule-id="' . vs_e($ruleId) . '"' . ($enabled ? ' checked' : '') . '>';
        echo '<span class="vs-edgeone-rules-switch__track"></span>';
        echo '</label>';
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
function vs_edgeone_render_rule_drawers()
{
    ob_start();
    echo '<div class="vs-edgeone-zone-create-drawer vs-edgeone-rule-form-drawer" id="edgeoneRuleFormDrawer" hidden aria-hidden="true">';
    echo '<div class="vs-edgeone-zone-create-drawer__overlay" data-rule-drawer-close></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__panel" role="dialog" aria-modal="true">';
    echo '<div class="vs-edgeone-zone-create-drawer__handle" aria-hidden="true"></div>';
    echo '<div class="vs-edgeone-zone-create-drawer__head">';
    echo '<h4 id="edgeoneRuleFormTitle">创建规则</h4>';
    echo '<button type="button" class="vs-edgeone-zone-create-drawer__close" data-rule-drawer-close aria-label="关闭">';
    echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
    echo '</button></div>';
    echo '<form class="vs-form" id="edgeoneRuleForm" data-reload="none">';
    echo '<input type="hidden" name="rule_id" id="edgeoneRuleFormId" value="">';
    echo '<div class="vs-edgeone-zone-create-drawer__body">';
    echo '<div class="vs-form-row"><label class="vs-label" for="edgeoneRuleFormName">规则名称</label>';
    echo '<input type="text" class="vs-input" name="rule_name" id="edgeoneRuleFormName" placeholder="未命名规则" required maxlength="255"></div>';
    echo '<div class="vs-form-row"><label class="vs-label" for="edgeoneRuleFormComment">注释</label>';
    echo '<input type="text" class="vs-input" name="rule_comment" id="edgeoneRuleFormComment" placeholder="可选"></div>';
    echo '<div class="vs-form-row"><label class="vs-label">启用状态</label>';
    echo '<label class="vs-edgeone-rules-switch vs-edgeone-rules-switch--inline">';
    echo '<input type="checkbox" name="rule_enabled" id="edgeoneRuleFormEnabled" value="1" checked>';
    echo '<span class="vs-edgeone-rules-switch__track"></span><span>保存后启用</span></label></div>';
    echo '<h5 class="vs-edgeone-subtitle">IF · 匹配条件</h5>';
    echo '<div class="vs-edgeone-rule-condition-row">';
    echo '<div class="vs-form-row"><label class="vs-label">匹配类型</label>';
    echo '<select class="vs-input" name="match_type" id="edgeoneRuleMatchType">';
    echo '<option value="host" selected>HOST</option>';
    echo '<option value="all">全部（站点任意请求）</option>';
    echo '</select></div>';
    echo '<div class="vs-form-row vs-edgeone-rule-host-field" id="edgeoneRuleHostField">';
    echo '<label class="vs-label">HOST 值</label>';
    echo '<input type="text" class="vs-input" name="match_host" id="edgeoneRuleMatchHost" placeholder="www.example.com 或留空匹配全部">';
    echo '</div></div>';
    echo '<h5 class="vs-edgeone-subtitle">THEN · 操作</h5>';
    echo '<p class="vs-form-tip">首版支持常用操作；复杂规则请在保存后于腾讯云控制台继续编辑。</p>';
    echo '<div class="vs-form-row"><label class="vs-label">操作类型</label>';
    echo '<select class="vs-input" name="action_name" id="edgeoneRuleActionName">';
    echo '<option value="Compression">智能压缩</option>';
    echo '<option value="ForceRedirectHTTPS">强制 HTTPS</option>';
    echo '<option value="HTTP2">HTTP/2</option>';
    echo '<option value="Cache">节点缓存 TTL（不缓存）</option>';
    echo '</select></div>';
    echo '</div>';
    echo '<div class="vs-edgeone-zone-create-drawer__foot">';
    echo '<button type="button" class="vs-btn vs-btn--rect vs-btn--default" data-rule-drawer-close>取消</button>';
    echo '<button type="submit" class="vs-btn vs-btn--rect vs-btn--primary" id="edgeoneRuleFormSubmit">保存并发布</button>';
    echo '</div></form></div></div>';

    return ob_get_clean();
}

/**
 * @param string               $host
 * @param string               $actionName
 * @return array<string, mixed>
 */
function vs_edgeone_build_simple_rule_branch($host, $actionName)
{
    $branch = array(
        'Condition' => vs_edgeone_build_host_rule_condition($host),
        'Actions'   => array(vs_edgeone_build_simple_rule_action($actionName)),
    );

    return $branch;
}

/**
 * @param string $actionName
 * @return array<string, mixed>
 */
function vs_edgeone_build_simple_rule_action($actionName)
{
    $actionName = (string) $actionName;
    if ($actionName === 'ForceRedirectHTTPS') {
        return array(
            'Name'                        => 'ForceRedirectHTTPS',
            'ForceRedirectHTTPSParameters' => array('Switch' => 'on', 'RedirectStatusCode' => 302),
        );
    }
    if ($actionName === 'HTTP2') {
        return array('Name' => 'HTTP2', 'HTTP2Parameters' => array('Switch' => 'on'));
    }
    if ($actionName === 'Cache') {
        return array(
            'Name'            => 'Cache',
            'CacheParameters' => array('NoCache' => array('Switch' => 'on')),
        );
    }

    return array('Name' => 'Compression', 'CompressionParameters' => array('Switch' => 'on'));
}

/**
 * @param array<string, mixed> $post
 * @return array<string, mixed>
 */
function vs_edgeone_build_rule_item_from_post(array $post)
{
    $name = trim(isset($post['rule_name']) ? (string) $post['rule_name'] : '');
    if ($name === '') {
        throw new Exception('请填写规则名称');
    }
    $comment = trim(isset($post['rule_comment']) ? (string) $post['rule_comment'] : '');
    $enabled = !empty($post['rule_enabled']);
    $matchType = trim(isset($post['match_type']) ? (string) $post['match_type'] : 'host');
    $host = $matchType === 'all' ? '' : trim(isset($post['match_host']) ? (string) $post['match_host'] : '');
    $actionName = trim(isset($post['action_name']) ? (string) $post['action_name'] : 'Compression');

    $item = array(
        'RuleName' => $name,
        'Status'   => $enabled ? 'enable' : 'disable',
        'Branches' => array(vs_edgeone_build_simple_rule_branch($host, $actionName)),
    );
    if ($comment !== '') {
        $item['Description'] = array($comment);
    }
    $ruleId = trim(isset($post['rule_id']) ? (string) $post['rule_id'] : '');
    if ($ruleId !== '') {
        $item['RuleId'] = $ruleId;
    }

    return $item;
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
