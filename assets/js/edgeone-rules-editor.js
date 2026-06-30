/**
 * EdgeOne 规则引擎全屏编辑器（对齐腾讯云控制台 / L7Acc API）
 */
(function () {
    'use strict';

    var catalog = null;
    var state = {
        rule: null,
        branchUi: [],
        subRuleUi: {},
        pickerTarget: null
    };

    var els = {};

    function $(id) {
        return document.getElementById(id);
    }

    function deepClone(obj) {
        return JSON.parse(JSON.stringify(obj));
    }

    function toast(msg, type) {
        if (window.VsToast) {
            VsToast.show(msg, type || 'success');
            return;
        }
        alert(msg);
    }

    function postFormData(body) {
        var api = window.VS_EDGEONE_API || '';
        return fetch(api, {
            method: 'POST',
            body: body,
            credentials: 'same-origin'
        }).then(function (res) {
            return res.text();
        }).then(function (text) {
            var data = window.VS && VS.parseJsonResponse ? VS.parseJsonResponse(text) : null;
            if (!data) throw new Error('invalid_json');
            return data;
        });
    }

    function loadCatalog() {
        var node = $('edgeoneRulesCatalog');
        if (!node) return null;
        try {
            return JSON.parse(node.textContent || '{}');
        } catch (err) {
            return null;
        }
    }

    function findMatchType(id) {
        if (!catalog || !catalog.matchTypes) return null;
        for (var i = 0; i < catalog.matchTypes.length; i++) {
            if (catalog.matchTypes[i].id === id) return catalog.matchTypes[i];
        }
        return null;
    }

    function findOperator(id) {
        if (!catalog || !catalog.operators) return null;
        for (var i = 0; i < catalog.operators.length; i++) {
            if (catalog.operators[i].id === id) return catalog.operators[i];
        }
        return null;
    }

    function splitDuration(seconds) {
        seconds = parseInt(seconds, 10);
        if (!seconds || seconds < 0) return { amount: 0, unit: 's' };
        if (seconds % 86400 === 0) return { amount: seconds / 86400, unit: 'd' };
        if (seconds % 3600 === 0) return { amount: seconds / 3600, unit: 'h' };
        if (seconds % 60 === 0) return { amount: seconds / 60, unit: 'm' };
        return { amount: seconds, unit: 's' };
    }

    function toSeconds(amount, unit) {
        amount = parseFloat(amount);
        if (isNaN(amount) || amount < 0) return 0;
        var mult = 1;
        (catalog && catalog.timeUnits || []).forEach(function (u) {
            if (u.id === unit) mult = u.mult;
        });
        if (unit === 'm') mult = 60;
        if (unit === 'h') mult = 3600;
        if (unit === 'd') mult = 86400;
        return Math.round(amount * mult);
    }

    function matchTypeShort(id) {
        var map = {
            all: '全部', host: 'HOST', url_path: '路径', url_full: '全URL',
            query_string: '参数', file_extension: '后缀', file_name: '文件名',
            request_header: '请求头', client_geo: '地域', client_isp: '运营商',
            request_protocol: '协议', client_ip: 'IP', request_method: '方法',
            cookie: 'Cookie', response_header: '响应头', response_status: '状态码'
        };
        if (map[id]) return map[id];
        var mt = findMatchType(id);
        if (!mt) return '条件';
        return String(mt.label || id).replace(/（.*?）/g, '').replace(/\(.*?\)/g, '').trim();
    }

    function briefValues(text, max) {
        max = max || 16;
        var vals = String(text || '').split(/[\n,;]+/).map(function (v) {
            return v.trim();
        }).filter(Boolean);
        if (vals.length === 0) return '';
        var head = vals.slice(0, 2).join('、');
        if (head.length > max) head = head.slice(0, max) + '…';
        else if (vals.length > 2) head += '等';
        return head;
    }

    function summarizeConditionRowCompact(row) {
        var mt = findMatchType(row.matchType);
        var op = findOperator(row.operator);
        if (mt && mt.all) return '全部请求';
        var label = matchTypeShort(row.matchType);
        if (row.paramName) label += ':' + row.paramName;
        var opLabel = op ? op.label : '等于';
        if (row.operator === 'exists' || row.operator === 'not_exists') {
            return label + opLabel;
        }
        var valText = briefValues(row.value, 20);
        return label + ' ' + opLabel + (valText ? ' ' + valText : '');
    }

    function summarizeConditionCompact(ui) {
        if (!ui) return '未配置';
        if (ui.isElse) return '未匹配时执行';
        if (ui.preserveCondition && !ui.conditionsDirty) {
            return summarizeConditionExprCompact(ui.preserveCondition);
        }
        var conds = (ui.conditions || []).filter(function (r) {
            return r && (r.matchType !== 'host' || String(r.value || '').trim() !== '' || r.matchType === 'all');
        });
        if (conds.length === 0) conds = ui.conditions || [];
        if (conds.length === 0) return '未配置';
        if (conds.length === 1) return summarizeConditionRowCompact(conds[0]);
        var types = [];
        conds.forEach(function (c) {
            var t = matchTypeShort(c.matchType);
            if (types.indexOf(t) < 0) types.push(t);
        });
        var valHint = briefValues(conds[0].value, 14);
        return types.join('+') + (valHint ? ' · ' + valHint : '') + '（' + conds.length + '条）';
    }

    function summarizeConditionExprCompact(expr) {
        expr = String(expr || '').trim();
        if (!expr || expr === "${http.request.host} ne ''") return '全部请求';
        var parsed = parseConditionExpression(expr);
        if (!parsed.parseWarning) return summarizeConditionCompact(parsed);
        var parts = expr.split(/\s+(?:and|or)\s+/i);
        var summaries = [];
        parts.forEach(function (part) {
            var s = humanizeExprPart(part.trim());
            if (s) summaries.push(s);
        });
        if (summaries.length) return summaries.join(' 且 ');
        return '已配置条件';
    }

    function humanizeExprPart(part) {
        if (!part) return '';
        var patterns = [
            { re: /\$\{http\.request\.host\}\s+in\s+\['([^']*)'\]/, fmt: function (m) { return 'HOST=' + m[1]; } },
            { re: /\$\{http\.request\.uri\.path\}\s+in\s+\['([^']*)'\]/, fmt: function (m) { return '路径=' + m[1]; } },
            { re: /\$\{http\.request\.file_extension\}\s+in\s+\[(.*?)\]/, fmt: function (m) {
                return '后缀=' + m[1].replace(/'\s*,\s*'/g, '、').replace(/'/g, '');
            }},
            { re: /\$\{http\.request\.scheme\}\s+in\s+\['([^']*)'\]/, fmt: function (m) { return '协议=' + m[1]; } },
            { re: /\$\{http\.request\.method\}\s+in\s+\[(.*?)\]/, fmt: function (m) {
                return '方法=' + m[1].replace(/'\s*,\s*'/g, '、').replace(/'/g, '');
            }}
        ];
        for (var i = 0; i < patterns.length; i++) {
            var m = part.match(patterns[i].re);
            if (m) return patterns[i].fmt(m);
        }
        return '';
    }

    function summarizeConditionRow(row) {
        var mt = findMatchType(row.matchType);
        var op = findOperator(row.operator);
        if (mt && mt.all) return '全部（站点任意请求）';
        var label = mt ? mt.label : '条件';
        if (row.paramName) label += ' · ' + row.paramName;
        var opLabel = op ? op.label : '等于';
        if (row.operator === 'exists' || row.operator === 'not_exists') {
            return label + ' ' + opLabel;
        }
        var vals = String(row.value || '').split(/[\n,;]+/).map(function (v) {
            return v.trim();
        }).filter(Boolean);
        var valText = vals.slice(0, 2).join('、');
        if (vals.length > 2) valText += ' 等';
        return label + ' ' + opLabel + ' ' + (valText || '…');
    }

    function summarizeConditionUi(ui) {
        if (!ui) return '未配置条件';
        if (ui.preserveCondition && !ui.conditionsDirty) {
            return summarizeConditionExpr(ui.preserveCondition);
        }
        var parts = (ui.conditions || []).map(summarizeConditionRow).filter(Boolean);
        if (parts.length === 0) return '未配置条件';
        return parts.join(ui.logic === 'or' ? ' 或 ' : ' 且 ');
    }

    function summarizeConditionExpr(expr) {
        expr = String(expr || '').trim();
        if (!expr || expr === "${http.request.host} ne ''") return '全部（站点任意请求）';
        var parsed = parseConditionExpression(expr);
        if (!parsed.parseWarning) return summarizeConditionUi(parsed);
        return summarizeConditionExprCompact(expr);
    }

    function branchLabel(index, total) {
        return total > 1 ? 'IF-' + (index + 1) : 'IF';
    }

    function subRuleBadge(index, total, desc, isElse) {
        if (desc) return desc;
        if (isElse) return 'ELSE';
        if (index === 0) return '嵌套IF';
        return 'EI-' + index;
    }

    function summarizeSubRuleUi(ui) {
        if (ui && ui.isElse) return '未匹配以上条件时执行';
        return summarizeConditionCompact(ui);
    }

    function taglistToText(val) {
        if (Array.isArray(val)) return val.join(', ');
        return String(val || '');
    }

    function textToTaglist(text) {
        return String(text || '').split(/[\n,;]+/).map(function (v) {
            return v.trim();
        }).filter(Boolean);
    }

    function escapeCondValue(val) {
        return String(val || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    }

    function buildVarExpr(matchType, paramName) {
        var mt = findMatchType(matchType);
        if (!mt) return '';
        if (mt.all) return '${http.request.host}';
        var tpl = mt.var || '';
        if (mt.needsName) {
            var name = String(paramName || '').replace(/\\/g, '\\\\').replace(/"/g, '\\"');
            return tpl.replace('%s', name);
        }
        return tpl;
    }

    function splitConditionLogic(condition) {
        var logic = 'and';
        var parts = [];
        if (!condition) return { logic: logic, parts: parts };
        var re = /\s+(and|or)\s+/gi;
        var last = 0;
        var m;
        while ((m = re.exec(condition)) !== null) {
            parts.push(condition.slice(last, m.index).trim());
            logic = m[1].toLowerCase();
            last = m.index + m[0].length;
        }
        parts.push(condition.slice(last).trim());
        return { logic: logic, parts: parts.filter(Boolean) };
    }

    function detectMatchTypeFromVar(varExpr) {
        if (!catalog || !catalog.matchTypes) return 'host';
        var types = catalog.matchTypes.slice().sort(function (a, b) {
            return String(b.var || '').length - String(a.var || '').length;
        });
        for (var i = 0; i < types.length; i++) {
            var mt = types[i];
            if (mt.all) continue;
            if (mt.needsName) {
                var prefix = mt.var.split('%s')[0];
                if (varExpr.indexOf(prefix) === 0) return mt.id;
            } else if (mt.var === varExpr) {
                return mt.id;
            }
        }
        if (varExpr.indexOf('uri.path') >= 0) return 'url_path';
        if (varExpr.indexOf('file_extension') >= 0) return 'file_extension';
        if (varExpr.indexOf('file_extension') < 0 && varExpr.indexOf('filename') >= 0) return 'file_name';
        if (varExpr.indexOf('full_uri') >= 0) return 'url_full';
        if (varExpr.indexOf('scheme') >= 0) return 'request_protocol';
        if (varExpr.indexOf('method') >= 0) return 'request_method';
        return 'host';
    }

    function parseSingleCondition(part) {
        part = String(part || '').trim();
        if (!part) return null;
        if (part === "${http.request.host} ne ''") {
            return { matchType: 'all', operator: 'equal', paramName: '', value: '' };
        }
        var existsMatch = part.match(/^(.+?)\s+(not\s+)?exists$/i);
        if (existsMatch) {
            var varExpr = existsMatch[1].trim();
            var mtId = detectMatchTypeFromVar(varExpr);
            var mt = findMatchType(mtId);
            var paramName = '';
            if (mt && mt.needsName) {
                var hm = varExpr.match(/\["([^"]+)"\]/);
                paramName = hm ? hm[1] : '';
            }
            return {
                matchType: mtId,
                operator: existsMatch[2] ? 'not_exists' : 'exists',
                paramName: paramName,
                value: ''
            };
        }
        var regexMatch = part.match(/^(.+?)\s+(not\s+)?matches\s+'((?:\\'|[^'])*)'$/i);
        if (regexMatch) {
            var varExpr2 = regexMatch[1].trim();
            return {
                matchType: detectMatchTypeFromVar(varExpr2),
                operator: regexMatch[2] ? 'not_regex' : 'regex',
                paramName: extractParamName(varExpr2),
                value: regexMatch[3].replace(/\\'/g, "'")
            };
        }
        var neMatch = part.match(/^(.+?)\s+ne\s+'((?:\\'|[^'])*)'$/i);
        if (neMatch) {
            var varExprNe = neMatch[1].trim();
            if (neMatch[2] === '' && varExprNe.indexOf('http.request.host') >= 0) {
                return { matchType: 'all', operator: 'equal', paramName: '', value: '' };
            }
        }
        var inMatch = part.match(/^(.+?)\s+(not\s+in|in)\s+\[(.*)\]$/i);
        if (inMatch) {
            var varExpr3 = inMatch[1].trim();
            var vals = [];
            var raw = inMatch[3];
            var vm = raw.match(/'((?:\\'|[^'])*)'/g);
            if (vm) {
                vm.forEach(function (s) {
                    vals.push(s.slice(1, -1).replace(/\\'/g, "'"));
                });
            }
            return {
                matchType: detectMatchTypeFromVar(varExpr3),
                operator: /not\s+in/i.test(inMatch[2]) ? 'not_equal' : 'equal',
                paramName: extractParamName(varExpr3),
                value: vals.join('\n')
            };
        }
        return null;
    }

    function extractParamName(varExpr) {
        var m = String(varExpr).match(/\["([^"]+)"\]/);
        return m ? m[1] : '';
    }

    function parseConditionExpression(condition) {
        condition = String(condition || '').trim();
        if (!condition) {
            return {
                rawCondition: '',
                logic: 'and',
                conditions: [{ matchType: 'host', operator: 'equal', paramName: '', value: '' }],
                preserveCondition: '',
                parseWarning: false,
                conditionsDirty: false
            };
        }
        var split = splitConditionLogic(condition);
        var rows = [];
        split.parts.forEach(function (part) {
            var row = parseSingleCondition(part);
            if (row) rows.push(row);
        });
        if (rows.length === 0 || rows.length < split.parts.length) {
            return {
                rawCondition: condition,
                logic: split.logic,
                conditions: rows.length ? rows : [{ matchType: 'all', operator: 'equal', paramName: '', value: '' }],
                preserveCondition: condition,
                parseWarning: true,
                conditionsDirty: false
            };
        }
        return {
            rawCondition: condition,
            logic: split.logic,
            conditions: rows,
            preserveCondition: '',
            parseWarning: false,
            conditionsDirty: false
        };
    }

    function buildSingleConditionExpr(row) {
        if (!row) return '';
        var mt = findMatchType(row.matchType);
        if (!mt) return '';
        if (mt.all) {
            return "${http.request.host} ne ''";
        }
        var varExpr = buildVarExpr(row.matchType, row.paramName);
        var op = row.operator || 'equal';
        if (op === 'exists') return varExpr + ' exists';
        if (op === 'not_exists') return varExpr + ' not exists';
        if (op === 'regex') return varExpr + " matches '" + escapeCondValue(row.value) + "'";
        if (op === 'not_regex') return varExpr + " not matches '" + escapeCondValue(row.value) + "'";
        var values = String(row.value || '').split(/[\n,;]+/).map(function (v) {
            return v.trim();
        }).filter(Boolean);
        if (values.length === 0) values = [''];
        var list = values.map(function (v) {
            return "'" + escapeCondValue(v) + "'";
        }).join(',');
        if (op === 'not_equal') return varExpr + ' not in [' + list + ']';
        return varExpr + ' in [' + list + ']';
    }

    function buildConditionExpression(ui) {
        if (!ui) return '';
        if (ui.preserveCondition && !ui.conditionsDirty) {
            return String(ui.preserveCondition).trim();
        }
        var parts = (ui.conditions || []).map(buildSingleConditionExpr).filter(Boolean);
        if (parts.length === 0) return '';
        var joiner = ui.logic === 'or' ? ' or ' : ' and ';
        return parts.join(joiner);
    }

    function defaultBranchUi() {
        return {
            logic: 'and',
            rawCondition: '',
            preserveCondition: '',
            parseWarning: false,
            conditionsDirty: false,
            conditions: [{ matchType: 'host', operator: 'equal', paramName: '', value: '' }]
        };
    }

    function ensureSubRuleUi(branchIndex, subIndex) {
        if (!state.subRuleUi[branchIndex]) state.subRuleUi[branchIndex] = [];
        if (!state.subRuleUi[branchIndex][subIndex]) {
            state.subRuleUi[branchIndex][subIndex] = defaultBranchUi();
        }
        return state.subRuleUi[branchIndex][subIndex];
    }

    function getSubBranch(branchIndex, subIndex) {
        var sub = state.rule.Branches[branchIndex].SubRules[subIndex];
        if (!sub.Branches || !sub.Branches[0]) {
            sub.Branches = [{ Condition: '', Actions: [] }];
        }
        return sub.Branches[0];
    }

    function scopePrefix(scope) {
        if (scope.type === 'subrule') {
            return 'sub' + scope.branchIndex + '_' + scope.subIndex;
        }
        return 'branch' + scope.branchIndex;
    }

    function normalizeMaxAgeAction(action) {
        if (!action || action.Name !== 'MaxAge' || !action.MaxAgeParameters) return;
        var p = action.MaxAgeParameters;
        if (!p._uiMode) {
            p._uiMode = String(p.FollowOrigin).toLowerCase() === 'on' ? 'follow_origin' : 'custom';
        }
    }

    function applyMaxAgeUiMode(action) {
        if (!action || action.Name !== 'MaxAge' || !action.MaxAgeParameters) return;
        var p = action.MaxAgeParameters;
        if (p._uiMode === 'follow_origin') {
            p.FollowOrigin = 'on';
        } else {
            p.FollowOrigin = 'off';
        }
        delete p._uiMode;
    }

    function applySiteFailoverBeforeSave(action) {
        if (!action || action.Name !== 'SiteFailover' || !action.SiteFailoverParameters) return;
        var codes = action.SiteFailoverParameters.SiteFailoverStatusCodes;
        if (Array.isArray(codes)) {
            action.SiteFailoverParameters.SiteFailoverStatusCodes = codes.map(function (c) {
                return parseInt(c, 10) || 0;
            }).filter(function (c) { return c > 0; });
        }
        var params = action.SiteFailoverParameters.SiteFailoverParams;
        if (Array.isArray(params) && params.length > 2) {
            action.SiteFailoverParameters.SiteFailoverParams = params.slice(0, 2);
        }
    }

    function normalizeCacheAction(action) {
        if (!action || action.Name !== 'Cache' || !action.CacheParameters) return;
        var p = action.CacheParameters;
        if (!p._uiMode) {
            if (p.NoCache && String(p.NoCache.Switch).toLowerCase() === 'on') {
                p._uiMode = 'no_cache';
            } else if (p.CustomTime && String(p.CustomTime.Switch).toLowerCase() === 'on') {
                p._uiMode = 'custom';
            } else {
                p._uiMode = 'follow_origin';
            }
        }
    }

    function applyCacheUiMode(action) {
        if (!action || action.Name !== 'Cache' || !action.CacheParameters) return;
        var mode = action.CacheParameters._uiMode || 'follow_origin';
        var p = action.CacheParameters;
        p.FollowOrigin = p.FollowOrigin || {};
        p.NoCache = p.NoCache || { Switch: 'off' };
        p.CustomTime = p.CustomTime || { Switch: 'off', CacheTime: 0, IgnoreCacheControl: 'off' };
        if (mode === 'no_cache') {
            p.NoCache.Switch = 'on';
            p.FollowOrigin.Switch = 'off';
            p.CustomTime.Switch = 'off';
        } else if (mode === 'custom') {
            p.CustomTime.Switch = 'on';
            p.NoCache.Switch = 'off';
            p.FollowOrigin.Switch = 'off';
        } else {
            p.FollowOrigin.Switch = 'on';
            p.FollowOrigin.DefaultCache = p.FollowOrigin.DefaultCache || 'on';
            p.FollowOrigin.DefaultCacheStrategy = p.FollowOrigin.DefaultCacheStrategy || 'on';
            p.FollowOrigin.DefaultCacheTime = p.FollowOrigin.DefaultCacheTime != null ? p.FollowOrigin.DefaultCacheTime : 0;
            p.NoCache.Switch = 'off';
            p.CustomTime.Switch = 'off';
        }
        delete p._uiMode;
    }

    function defaultRule() {
        return {
            RuleName: '',
            Status: 'enable',
            Description: [],
            Branches: [{
                Condition: "${http.request.host} ne ''",
                Actions: [],
                SubRules: []
            }]
        };
    }

    function cloneActionDefaults(name) {
        if (!catalog || !catalog.actions || !catalog.actions[name]) {
            return { Name: name };
        }
        return deepClone(catalog.actions[name].defaults);
    }

    function getNested(obj, path) {
        var parts = String(path).split('.');
        var cur = obj;
        for (var i = 0; i < parts.length; i++) {
            if (cur == null) return undefined;
            cur = cur[parts[i]];
        }
        return cur;
    }

    function setNested(obj, path, value) {
        var parts = String(path).split('.');
        var cur = obj;
        for (var i = 0; i < parts.length - 1; i++) {
            if (cur[parts[i]] == null || typeof cur[parts[i]] !== 'object') {
                cur[parts[i]] = {};
            }
            cur = cur[parts[i]];
        }
        cur[parts[parts.length - 1]] = value;
    }

    function actionLabel(name) {
        if (catalog && catalog.actions && catalog.actions[name]) {
            return catalog.actions[name].label || name;
        }
        return name;
    }

    function syncRuleFromForm() {
        if (!state.rule) return;
        state.rule.RuleName = ($('edgeoneRuleEditorName') || {}).value || '';
        var comment = ($('edgeoneRuleEditorComment') || {}).value || '';
        state.rule.Description = comment.trim() ? [comment.trim()] : [];
        state.rule.Status = ($('edgeoneRuleEditorEnabled') || {}).checked ? 'enable' : 'disable';
        state.branchUi.forEach(function (ui, idx) {
            if (!state.rule.Branches[idx]) return;
            state.rule.Branches[idx].Condition = buildConditionExpression(ui);
        });
    }

    function normalizeActionBeforeRender(action) {
        normalizeCacheAction(action);
        normalizeMaxAgeAction(action);
    }

    function applyActionBeforeSave(action) {
        applyCacheUiMode(action);
        applyMaxAgeUiMode(action);
        applySiteFailoverBeforeSave(action);
    }

    function renderNav() {
        var nav = $('edgeoneRuleEditorNav');
        if (!nav) return;
        var branchTotal = (state.rule.Branches || []).length;
        var html = '<div class="vs-edgeone-rule-editor__nav-title">规则导航</div>';
        html += '<button type="button" class="vs-edgeone-rule-editor__nav-item is-active" data-nav="meta">基本信息</button>';
        (state.rule.Branches || []).forEach(function (branch, i) {
            var ui = state.branchUi[i] || defaultBranchUi();
            var bl = branchLabel(i, branchTotal);
            var compact = summarizeConditionCompact(ui);
            var label = bl + ' · ' + compact;
            html += '<button type="button" class="vs-edgeone-rule-editor__nav-item" data-nav="branch-' + i + '" title="' + escHtml(summarizeConditionUi(ui)) + '">';
            html += '<span class="vs-edgeone-rule-editor__nav-badge">' + escHtml(bl) + '</span>';
            html += '<span class="vs-edgeone-rule-editor__nav-text">' + escHtml(compact) + '</span></button>';
            (branch.SubRules || []).forEach(function (sub, si) {
                var sui = state.subRuleUi[i] && state.subRuleUi[i][si] ? state.subRuleUi[i][si] : defaultBranchUi();
                var badge = subRuleBadge(si, branch.SubRules.length, (sub.Description && sub.Description[0]) || '', sui.isElse);
                var subCompact = summarizeSubRuleUi(sui);
                html += '<button type="button" class="vs-edgeone-rule-editor__nav-item vs-edgeone-rule-editor__nav-item--sub" data-nav="sub-' + i + '-' + si + '" title="' + escHtml(summarizeConditionUi(sui)) + '">';
                html += '<span class="vs-edgeone-rule-editor__nav-badge">' + escHtml(badge) + '</span>';
                html += '<span class="vs-edgeone-rule-editor__nav-text">' + escHtml(subCompact) + '</span></button>';
            });
        });
        nav.innerHTML = html;
    }

    function escHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderMatchTypeOptions(selected) {
        var html = '';
        (catalog.matchTypes || []).forEach(function (mt) {
            html += '<option value="' + escHtml(mt.id) + '"' + (mt.id === selected ? ' selected' : '') + '>' + escHtml(mt.label) + '</option>';
        });
        return html;
    }

    function renderOperatorOptions(selected, matchTypeId) {
        var mt = findMatchType(matchTypeId);
        var html = '';
        (catalog.operators || []).forEach(function (op) {
            html += '<option value="' + escHtml(op.id) + '"' + (op.id === selected ? ' selected' : '') + '>' + escHtml(op.label) + '</option>';
        });
        return html;
    }

    function operatorNeedsValue(opId) {
        return opId !== 'exists' && opId !== 'not_exists';
    }

    function renderConditionRow(scope, rowIndex, row) {
        var mt = findMatchType(row.matchType);
        var needsName = mt && mt.needsName;
        var needsValue = operatorNeedsValue(row.operator);
        var pf = scopePrefix(scope);
        var html = '<div class="vs-edgeone-rule-cond-row" data-scope="' + escHtml(scope.type) + '" data-branch="' + scope.branchIndex + '"';
        if (scope.type === 'subrule') html += ' data-subrule="' + scope.subIndex + '"';
        html += ' data-cond="' + rowIndex + '">';
        html += '<select class="vs-input vs-edgeone-rule-cond-type" aria-label="匹配类型">' + renderMatchTypeOptions(row.matchType) + '</select>';
        if (needsName) {
            html += '<input type="text" class="vs-input vs-edgeone-rule-cond-name" placeholder="参数名，如 User-Agent" value="' + escHtml(row.paramName) + '">';
        }
        html += '<select class="vs-input vs-edgeone-rule-cond-op" aria-label="运算符">' + renderOperatorOptions(row.operator, row.matchType) + '</select>';
        if (needsValue) {
            html += '<input type="text" class="vs-input vs-edgeone-rule-cond-value" placeholder="匹配值，多个用英文逗号分隔" value="' + escHtml(String(row.value || '').replace(/\n/g, ', ')) + '">';
        }
        html += '<button type="button" class="vs-edgeone-rule-cond-remove" title="删除条件">&times;</button>';
        if (mt && mt.hint) {
            html += '<p class="vs-form-tip vs-edgeone-rule-cond-hint">' + escHtml(mt.hint) + '</p>';
        }
        html += '</div>';
        return html;
    }

    function genericFieldLabel(key) {
        if (catalog && catalog.paramLabels && catalog.paramLabels[key]) {
            return catalog.paramLabels[key];
        }
        return key;
    }

    function renderGenericFieldNode(path, label, val) {
        var html = '<div class="vs-edgeone-rule-action-field" data-field-key="' + escHtml(path) + '">';
        html += '<label class="vs-label">' + escHtml(genericFieldLabel(label)) + '</label>';
        if (val !== null && typeof val === 'object' && !Array.isArray(val)) {
            html += '<div class="vs-edgeone-rule-nested-fields">' + renderGenericFields(val, path) + '</div>';
        } else if (Array.isArray(val)) {
            html += '<input type="text" class="vs-input vs-edgeone-rule-action-field-input" value="' + escHtml(taglistToText(val)) + '" placeholder="多个值用英文逗号分隔" data-field-type="taglist">';
        } else if (String(val).toLowerCase() === 'on' || String(val).toLowerCase() === 'off') {
            var on = String(val).toLowerCase() === 'on';
            html += '<label class="vs-edgeone-rules-switch vs-edgeone-rules-switch--inline">';
            html += '<input type="checkbox" class="vs-edgeone-rule-action-field-input"' + (on ? ' checked' : '') + ' data-field-type="switch">';
            html += '<span class="vs-edgeone-rules-switch__track"></span><span>' + (on ? '开启' : '关闭') + '</span></label>';
        } else if (typeof val === 'number') {
            html += '<input type="number" class="vs-input vs-edgeone-rule-action-field-input" value="' + escHtml(val) + '" data-field-type="number">';
        } else {
            html += '<input type="text" class="vs-input vs-edgeone-rule-action-field-input" value="' + escHtml(val == null ? '' : val) + '" data-field-type="text">';
        }
        html += '</div>';
        return html;
    }

    function renderGenericFields(obj, pathPrefix) {
        var html = '';
        if (!obj || typeof obj !== 'object' || Array.isArray(obj)) return html;
        Object.keys(obj).forEach(function (k) {
            var path = pathPrefix ? pathPrefix + '.' + k : k;
            html += renderGenericFieldNode(path, k, obj[k]);
        });
        return html;
    }

    function renderUnknownActionFields(action) {
        var html = '<div class="vs-edgeone-rule-action-card__fields">';
        html += '<p class="vs-form-tip">该操作来自腾讯云控制台，已转为表单编辑，无需手写 JSON。</p>';
        Object.keys(action).forEach(function (key) {
            if (key === 'Name') return;
            html += renderGenericFieldNode(key, key, action[key]);
        });
        html += '</div>';
        return html;
    }

    function failoverModeOptions(selected) {
        var opts = {
            FailoverToHost: '回源到指定 IP/域名',
            FailoverToCOS: '回源到腾讯云 COS',
            FailoverToS3CompatibleObjectStorage: '回源到 S3 兼容存储',
            FailoverRedirectToURL: '重定向至 URL',
            FailoverCustomResponsePage: '自定义响应页'
        };
        var html = '';
        Object.keys(opts).forEach(function (k) {
            html += '<option value="' + escHtml(k) + '"' + (selected === k ? ' selected' : '') + '>' + escHtml(opts[k]) + '</option>';
        });
        return html;
    }

    function renderFailoverRow(row, ri) {
        row = row || {};
        var html = '<div class="vs-edgeone-rule-repeat-row vs-edgeone-rule-failover-row" data-row="' + ri + '">';
        html += '<select class="vs-input vs-edgeone-rule-failover-mode">' + failoverModeOptions(row.Mode || 'FailoverToHost') + '</select>';
        html += '<input type="text" class="vs-input vs-edgeone-rule-failover-origin" placeholder="源站/COS 域名" value="' + escHtml(row.Origin || '') + '">';
        html += '<select class="vs-input vs-edgeone-rule-failover-protocol">';
        html += '<option value="follow"' + (row.OriginProtocol === 'follow' ? ' selected' : '') + '>协议跟随</option>';
        html += '<option value="http"' + (row.OriginProtocol === 'http' ? ' selected' : '') + '>HTTP</option>';
        html += '<option value="https"' + (row.OriginProtocol === 'https' ? ' selected' : '') + '>HTTPS</option>';
        html += '</select>';
        html += '<input type="number" class="vs-input vs-edgeone-rule-failover-http-port" placeholder="HTTP 端口" min="1" max="65535" value="' + escHtml(row.HTTPOriginPort == null ? '' : row.HTTPOriginPort) + '">';
        html += '<input type="number" class="vs-input vs-edgeone-rule-failover-https-port" placeholder="HTTPS 端口" min="1" max="65535" value="' + escHtml(row.HTTPSOriginPort == null ? '' : row.HTTPSOriginPort) + '">';
        html += '<input type="text" class="vs-input vs-edgeone-rule-failover-redirect" placeholder="重定向 URL" value="' + escHtml(row.RedirectURL || '') + '">';
        html += '<input type="text" class="vs-input vs-edgeone-rule-failover-page" placeholder="响应页 ID" value="' + escHtml(row.ResponsePageId || '') + '">';
        html += '<input type="number" class="vs-input vs-edgeone-rule-failover-status" placeholder="状态码" min="100" max="599" value="' + escHtml(row.StatusCode == null ? '' : row.StatusCode) + '">';
        html += '<button type="button" class="vs-edgeone-rule-repeat-remove" title="删除">&times;</button></div>';
        return html;
    }

    function renderActionCard(scope, actionIndex, action) {
        normalizeActionBeforeRender(action);
        var name = action.Name || '';
        var meta = catalog.actions && catalog.actions[name] ? catalog.actions[name] : null;
        var html = '<div class="vs-edgeone-rule-action-card" data-scope="' + escHtml(scope.type) + '" data-branch="' + scope.branchIndex + '"';
        if (scope.type === 'subrule') html += ' data-subrule="' + scope.subIndex + '"';
        html += ' data-action="' + actionIndex + '">';
        html += '<div class="vs-edgeone-rule-action-card__head">';
        html += '<strong>' + escHtml(actionLabel(name)) + '</strong>';
        html += '<button type="button" class="vs-edgeone-rule-action-remove" title="删除操作">&times;</button>';
        html += '</div>';
        if (meta && meta.fields && meta.fields.length) {
            html += '<div class="vs-edgeone-rule-action-card__fields">';
            meta.fields.forEach(function (field, fi) {
                html += renderActionField(scope, actionIndex, action, field, fi);
            });
            html += '</div>';
        } else if (!meta) {
            html += renderUnknownActionFields(action);
        } else {
            html += '<p class="vs-form-tip">该操作已启用，无需额外配置。</p>';
        }
        html += '</div>';
        return html;
    }

    function renderActionField(scope, actionIndex, action, field, fi) {
        var key = field.key || '';
        var val = getNested(action, key);
        var pf = scopePrefix(scope);
        var id = 'ruleActionField_' + pf + '_' + actionIndex + '_' + fi;
        var html = '<div class="vs-edgeone-rule-action-field" data-field-key="' + escHtml(key) + '">';
        html += '<label class="vs-label" for="' + id + '">' + escHtml(field.label || key) + '</label>';
        if (field.type === 'switch') {
            var on = String(val).toLowerCase() === 'on';
            html += '<label class="vs-edgeone-rules-switch vs-edgeone-rules-switch--inline">';
            html += '<input type="checkbox" id="' + id + '" class="vs-edgeone-rule-action-field-input"' + (on ? ' checked' : '') + ' data-field-type="switch">';
            html += '<span class="vs-edgeone-rules-switch__track"></span><span>' + (on ? '开启' : '关闭') + '</span></label>';
        } else if (field.type === 'number') {
            html += '<input type="number" class="vs-input vs-edgeone-rule-action-field-input" id="' + id + '" value="' + escHtml(val == null ? '' : val) + '"';
            if (field.min != null) html += ' min="' + field.min + '"';
            if (field.max != null) html += ' max="' + field.max + '"';
            html += ' data-field-type="number">';
        } else if (field.type === 'select') {
            html += '<select class="vs-input vs-edgeone-rule-action-field-input" id="' + id + '" data-field-type="select">';
            var opts = field.options || [];
            if (Array.isArray(opts)) {
                opts.forEach(function (opt) {
                    html += '<option value="' + escHtml(opt) + '"' + (String(val) === String(opt) ? ' selected' : '') + '>' + escHtml(opt) + '</option>';
                });
            } else {
                Object.keys(opts).forEach(function (k) {
                    html += '<option value="' + escHtml(k) + '"' + (String(val) === String(k) ? ' selected' : '') + '>' + escHtml(opts[k]) + '</option>';
                });
            }
            html += '</select>';
        } else if (field.type === 'text') {
            html += '<input type="text" class="vs-input vs-edgeone-rule-action-field-input" id="' + id + '" value="' + escHtml(val == null ? '' : val) + '" data-field-type="text">';
        } else if (field.type === 'taglist') {
            html += '<input type="text" class="vs-input vs-edgeone-rule-action-field-input" id="' + id + '" value="' + escHtml(taglistToText(val)) + '" placeholder="多个值用英文逗号分隔" data-field-type="taglist">';
        } else if (field.type === 'duration') {
            var dur = splitDuration(val);
            html += '<div class="vs-edgeone-rule-duration">';
            html += '<input type="number" class="vs-input vs-edgeone-rule-duration-amount" min="0" value="' + escHtml(dur.amount) + '" data-field-type="duration-amount">';
            html += '<select class="vs-input vs-edgeone-rule-duration-unit" data-field-type="duration-unit">';
            (catalog.timeUnits || [{ id: 's', label: '秒' }, { id: 'm', label: '分' }, { id: 'h', label: '时' }, { id: 'd', label: '天' }]).forEach(function (u) {
                html += '<option value="' + escHtml(u.id) + '"' + (dur.unit === u.id ? ' selected' : '') + '>' + escHtml(u.label) + '</option>';
            });
            html += '</select></div>';
        } else if (field.type === 'checkboxes') {
            var selected = Array.isArray(val) ? val : [];
            html += '<div class="vs-edgeone-rule-checkboxes" data-field-type="checkboxes">';
            var opts = field.options || {};
            Object.keys(opts).forEach(function (k) {
                var checked = selected.indexOf(k) >= 0;
                html += '<label class="vs-edgeone-rule-checkbox-item"><input type="checkbox" class="vs-edgeone-rule-checkbox-input" value="' + escHtml(k) + '"' + (checked ? ' checked' : '') + '> ' + escHtml(opts[k]) + '</label>';
            });
            html += '</div>';
        } else if (field.type === 'status_rows') {
            var rows = Array.isArray(val) ? val : [];
            html += '<div class="vs-edgeone-rule-repeat-rows" data-field-type="status_rows">';
            rows.forEach(function (row, ri) {
                var rd = splitDuration(row.CacheTime);
                html += '<div class="vs-edgeone-rule-repeat-row" data-row="' + ri + '">';
                html += '<input type="number" class="vs-input vs-edgeone-rule-status-code" placeholder="状态码" value="' + escHtml(row.StatusCode == null ? '' : row.StatusCode) + '">';
                html += '<input type="number" class="vs-input vs-edgeone-rule-duration-amount" min="0" value="' + escHtml(rd.amount) + '">';
                html += '<select class="vs-input vs-edgeone-rule-duration-unit">';
                (catalog.timeUnits || []).forEach(function (u) {
                    html += '<option value="' + escHtml(u.id) + '"' + (rd.unit === u.id ? ' selected' : '') + '>' + escHtml(u.label) + '</option>';
                });
                html += '</select>';
                html += '<button type="button" class="vs-edgeone-rule-repeat-remove" title="删除">&times;</button></div>';
            });
            html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-repeat-add">+ 添加状态码</button></div>';
        } else if (field.type === 'header_rows') {
            var hrows = Array.isArray(val) ? val : [];
            var withValue = field.withValue !== false;
            html += '<div class="vs-edgeone-rule-repeat-rows" data-field-type="header_rows" data-with-value="' + (withValue ? '1' : '0') + '">';
            hrows.forEach(function (row, ri) {
                html += '<div class="vs-edgeone-rule-repeat-row" data-row="' + ri + '">';
                html += '<select class="vs-input vs-edgeone-rule-header-action">';
                html += '<option value="add"' + (row.Action === 'add' ? ' selected' : '') + '>添加</option>';
                html += '<option value="set"' + (row.Action === 'set' ? ' selected' : '') + '>设置</option>';
                html += '<option value="del"' + (row.Action === 'del' ? ' selected' : '') + '>删除</option>';
                html += '</select>';
                html += '<input type="text" class="vs-input vs-edgeone-rule-header-name" placeholder="Header 名称" value="' + escHtml(row.Name || '') + '">';
                if (withValue) {
                    html += '<input type="text" class="vs-input vs-edgeone-rule-header-value" placeholder="Header 值" value="' + escHtml(row.Value || '') + '">';
                }
                html += '<button type="button" class="vs-edgeone-rule-repeat-remove" title="删除">&times;</button></div>';
            });
            html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-repeat-add">+ 添加 Header 规则</button></div>';
        } else if (field.type === 'error_page_rows') {
            var erows = Array.isArray(val) ? val : [];
            html += '<div class="vs-edgeone-rule-repeat-rows" data-field-type="error_page_rows">';
            erows.forEach(function (row, ri) {
                html += '<div class="vs-edgeone-rule-repeat-row" data-row="' + ri + '">';
                html += '<input type="number" class="vs-input vs-edgeone-rule-status-code" placeholder="状态码" value="' + escHtml(row.StatusCode == null ? '' : row.StatusCode) + '">';
                html += '<input type="text" class="vs-input vs-edgeone-rule-error-url" placeholder="跳转 URL" value="' + escHtml(row.RedirectURL || '') + '">';
                html += '<button type="button" class="vs-edgeone-rule-repeat-remove" title="删除">&times;</button></div>';
            });
            html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-repeat-add">+ 添加错误页面</button></div>';
        } else if (field.type === 'failover_rows') {
            var frows = Array.isArray(val) ? val : [];
            html += '<div class="vs-edgeone-rule-repeat-rows" data-field-type="failover_rows">';
            frows.forEach(function (row, ri) {
                html += renderFailoverRow(row, ri);
            });
            html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-repeat-add"' + (frows.length >= 2 ? ' disabled' : '') + '>+ 添加备用策略（最多 2 条）</button></div>';
        } else if (field.type === 'auth_property_rows') {
            var arows = Array.isArray(val) && val.length ? val : [{ Type: 'QueryString', Name: '', Value: '' }];
            html += '<div class="vs-edgeone-rule-repeat-rows" data-field-type="auth_property_rows">';
            arows.forEach(function (row, ri) {
                html += '<div class="vs-edgeone-rule-repeat-row" data-row="' + ri + '">';
                html += '<select class="vs-input vs-edgeone-rule-auth-type">';
                html += '<option value="QueryString"' + (row.Type === 'QueryString' ? ' selected' : '') + '>URL 查询参数</option>';
                html += '<option value="Header"' + (row.Type === 'Header' ? ' selected' : '') + '>HTTP 请求头</option>';
                html += '</select>';
                html += '<input type="text" class="vs-input vs-edgeone-rule-auth-name" placeholder="参数名称" value="' + escHtml(row.Name || '') + '">';
                html += '<input type="text" class="vs-input vs-edgeone-rule-auth-value" placeholder="参数值" value="' + escHtml(row.Value || '') + '">';
                html += '<button type="button" class="vs-edgeone-rule-repeat-remove" title="删除">&times;</button></div>';
            });
            html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-repeat-add">+ 添加鉴权参数</button></div>';
        } else {
            html += '<input type="text" class="vs-input vs-edgeone-rule-action-field-input" id="' + id + '" value="' + escHtml(val == null ? '' : String(val)) + '" data-field-type="text">';
        }
        if (field.hint) {
            html += '<p class="vs-form-tip vs-edgeone-rule-field-hint">' + escHtml(field.hint) + '</p>';
        }
        html += '</div>';
        return html;
    }

    function renderConditionSection(scope, ui, sectionId) {
        var pf = scopePrefix(scope);
        var html = '<div class="vs-edgeone-rule-cond-section" id="' + escHtml(sectionId) + '">';
        if (ui.parseWarning && ui.preserveCondition) {
            html += '<p class="vs-form-tip vs-edgeone-rule-cond-tip">部分条件较复杂，已保留腾讯云原配置。修改下方条件后将按新配置保存。</p>';
        }
        html += '<div class="vs-edgeone-rule-branch__mode">';
        html += '<select class="vs-input vs-edgeone-rule-cond-logic" aria-label="条件逻辑" data-scope="' + scope.type + '" data-branch="' + scope.branchIndex + '"';
        if (scope.type === 'subrule') html += ' data-subrule="' + scope.subIndex + '"';
        html += '>';
        html += '<option value="and"' + (ui.logic === 'and' ? ' selected' : '') + '>满足全部（AND）</option>';
        html += '<option value="or"' + (ui.logic === 'or' ? ' selected' : '') + '>满足任一（OR）</option>';
        html += '</select></div>';
        html += '<div class="vs-edgeone-rule-cond-builder" data-builder-for="' + pf + '">';
        (ui.conditions || []).forEach(function (row, ri) {
            html += renderConditionRow(scope, ri, row);
        });
        html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-cond-add" data-scope="' + scope.type + '" data-branch-index="' + scope.branchIndex + '"';
        if (scope.type === 'subrule') html += ' data-subrule-index="' + scope.subIndex + '"';
        html += '>+ 添加匹配条件</button>';
        html += '</div></div>';
        return html;
    }

    function renderThenSection(scope, actions) {
        var html = '<div class="vs-edgeone-rule-branch__then">';
        html += '<h5>THEN · 执行操作</h5>';
        html += '<div class="vs-edgeone-rule-actions-list">';
        (actions || []).forEach(function (action, ai) {
            html += renderActionCard(scope, ai, action);
        });
        html += '</div>';
        html += '<button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-rule-action-add" data-scope="' + scope.type + '" data-branch-index="' + scope.branchIndex + '"';
        if (scope.type === 'subrule') html += ' data-subrule-index="' + scope.subIndex + '"';
        html += '>+ 添加操作</button></div>';
        return html;
    }

    function renderBranchCard(branchIndex) {
        var branch = state.rule.Branches[branchIndex];
        var ui = state.branchUi[branchIndex] || defaultBranchUi();
        var scope = { type: 'branch', branchIndex: branchIndex };
        var bl = branchLabel(branchIndex, state.rule.Branches.length);
        var html = '<section class="vs-edgeone-rule-branch" id="edgeoneRuleBranch-' + branchIndex + '" data-branch-index="' + branchIndex + '">';
        html += '<header class="vs-edgeone-rule-branch__head">';
        html += '<div class="vs-edgeone-rule-branch__title">';
        html += '<span class="vs-edgeone-rule-branch__badge">' + escHtml(bl) + '</span>';
        html += '<h5>' + escHtml(summarizeConditionCompact(ui)) + '</h5>';
        html += '</div>';
        if (state.rule.Branches.length > 1) {
            html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-branch-remove" data-branch-index="' + branchIndex + '">删除条件块</button>';
        }
        html += '</header>';
        html += renderConditionSection(scope, ui, 'edgeoneRuleCond-' + branchIndex);
        html += renderThenSection(scope, branch.Actions || []);
        html += renderSubRules(branchIndex, branch.SubRules || []);
        html += '</section>';
        return html;
    }

    function renderSubRules(branchIndex, subRules) {
        var html = '<div class="vs-edgeone-rule-subrules">';
        html += '<div class="vs-edgeone-rule-subrules__head"><h5>嵌套规则（IF / ELSE IF / ELSE）</h5>';
        html += '<p class="vs-form-tip">与腾讯云控制台一致：先满足外层 IF，再按顺序匹配内层规则；最后可添加 ELSE 兜底分支。</p></div>';
        var hasElse = false;
        (state.subRuleUi[branchIndex] || []).forEach(function (u) {
            if (u && u.isElse) hasElse = true;
        });
        subRules.forEach(function (sub, si) {
            var scope = { type: 'subrule', branchIndex: branchIndex, subIndex: si };
            var ui = ensureSubRuleUi(branchIndex, si);
            var subBranch = getSubBranch(branchIndex, si);
            if (ui.isElse) hasElse = true;
            var badge = subRuleBadge(si, subRules.length, '', ui.isElse);
            if (subBranch.Condition && !ui._inited) {
                var parsed = parseConditionExpression(subBranch.Condition);
                ui.logic = parsed.logic;
                ui.rawCondition = parsed.rawCondition || subBranch.Condition;
                ui.preserveCondition = parsed.preserveCondition || '';
                ui.parseWarning = parsed.parseWarning;
                ui.conditionsDirty = false;
                ui.conditions = parsed.conditions.length ? parsed.conditions : ui.conditions;
                ui._inited = true;
            }
            html += '<div class="vs-edgeone-rule-subrule' + (ui.isElse ? ' is-else' : '') + '" id="edgeoneRuleSub-' + branchIndex + '-' + si + '" data-branch="' + branchIndex + '" data-subrule="' + si + '">';
            html += '<header class="vs-edgeone-rule-subrule__head">';
            html += '<span class="vs-edgeone-rule-subrule__badge">' + escHtml(badge) + '</span>';
            html += '<span class="vs-edgeone-rule-subrule__summary">' + escHtml(summarizeSubRuleUi(ui)) + '</span>';
            html += '<label class="vs-edgeone-rule-subrule-else-toggle">';
            html += '<input type="checkbox" class="vs-edgeone-rule-subrule-is-else"' + (ui.isElse ? ' checked' : '') + '>';
            html += ' ELSE 兜底</label>';
            html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-subrule-remove" data-branch-index="' + branchIndex + '" data-subrule-index="' + si + '">删除</button>';
            html += '</header>';
            html += '<div class="vs-form-row"><label class="vs-label">注释</label>';
            html += '<input type="text" class="vs-input vs-edgeone-rule-subrule-desc" placeholder="可选，便于识别" value="' + escHtml((sub.Description && sub.Description[0]) || '') + '"></div>';
            if (ui.isElse) {
                html += '<p class="vs-form-tip vs-edgeone-rule-else-tip">当以上嵌套 IF / ELSE IF 均未匹配时，执行下方操作（无需配置条件）。</p>';
            } else {
                html += renderConditionSection(scope, ui, 'edgeoneRuleSubCond-' + branchIndex + '-' + si);
            }
            html += renderThenSection(scope, subBranch.Actions || []);
            html += '</div>';
        });
        html += '<div class="vs-edgeone-rule-subrules__actions">';
        html += '<button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-rule-subrule-add" data-branch-index="' + branchIndex + '">+ 添加 ELSE IF</button>';
        if (!hasElse) {
            html += '<button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-rule-subrule-add-else" data-branch-index="' + branchIndex + '">+ 添加 ELSE（兜底）</button>';
        }
        html += '</div></div>';
        return html;
    }

    function collectAllFromDom() {
        if (!state.rule) return;
        collectConditionsFromDom();
        collectActionFieldsFromDom();
        (state.rule.Branches || []).forEach(function (branch, i) {
            collectSubRulesFromDom(i);
        });
    }

    function renderBranches() {
        var host = $('edgeoneRuleEditorBranches');
        if (host && host.children.length) {
            collectAllFromDom();
        }
        if (!host || !state.rule) return;
        var html = '';
        state.rule.Branches.forEach(function (branch, i) {
            html += renderBranchCard(i);
        });
        host.innerHTML = html;
        renderNav();
    }

    function renderActionPicker() {
        var body = $('edgeoneRuleActionPickerBody');
        if (!body || !catalog) return;
        var q = ($('edgeoneRuleActionSearch') || {}).value || '';
        q = q.trim().toLowerCase();
        var html = '';
        var cats = catalog.categories || {};
        Object.keys(cats).forEach(function (catKey) {
            var items = [];
            Object.keys(catalog.actions || {}).forEach(function (name) {
                var act = catalog.actions[name];
                if (act.category !== catKey) return;
                var label = (act.label || name).toLowerCase();
                if (q && label.indexOf(q) === -1 && name.toLowerCase().indexOf(q) === -1) return;
                items.push({ name: name, act: act });
            });
            if (items.length === 0) return;
            html += '<section class="vs-edgeone-rule-action-group"><h5>' + escHtml(cats[catKey]) + '</h5><div class="vs-edgeone-rule-action-grid">';
            items.forEach(function (item) {
                html += '<button type="button" class="vs-edgeone-rule-action-pick" data-action-name="' + escHtml(item.name) + '">';
                html += '<span class="vs-edgeone-rule-action-pick__label">' + escHtml(item.act.label || item.name) + '</span>';
                if (item.act.paid) html += '<span class="vs-edgeone-rule-action-pick__tag">增值</span>';
                html += '</button>';
            });
            html += '</div></section>';
        });
        body.innerHTML = html || '<p class="vs-form-tip">无匹配操作</p>';
    }

    function openEditor(ruleId) {
        catalog = loadCatalog();
        if (!catalog) {
            toast('规则目录未加载', 'error');
            return;
        }
        bindEditorOnce();

        var rule = defaultRule();
        state.branchUi = [defaultBranchUi()];
        state.subRuleUi = {};

        if (ruleId) {
            var row = findRuleRow(ruleId);
            if (row) {
                rule = deepClone(row);
                state.branchUi = (rule.Branches || []).map(function (branch) {
                    return parseConditionExpression(branch.Condition || '');
                });
                (rule.Branches || []).forEach(function (branch, bi) {
                    state.subRuleUi[bi] = (branch.SubRules || []).map(function (sub) {
                        var cond = sub.Branches && sub.Branches[0] ? sub.Branches[0].Condition : '';
                        var ui = parseConditionExpression(cond || '');
                        if (cond === '' || cond == null) {
                            ui.isElse = true;
                            ui.conditions = [];
                        }
                        ui._inited = true;
                        return ui;
                    });
                });
            }
        }

        state.rule = rule;
        $('edgeoneRuleEditorName').value = rule.RuleName || '';
        $('edgeoneRuleEditorComment').value = (rule.Description && rule.Description[0]) || '';
        $('edgeoneRuleEditorEnabled').checked = String(rule.Status || '').toLowerCase() !== 'disable';
        var hint = $('edgeoneRuleEditorIdHint');
        if (hint) {
            if (rule.RuleId) {
                hint.hidden = false;
                hint.textContent = 'ID: ' + rule.RuleId;
            } else {
                hint.hidden = true;
                hint.textContent = '';
            }
        }

        renderBranches();

        var editor = $('edgeoneRuleEditor');
        if (!editor) return;
        editor.hidden = false;
        editor.setAttribute('aria-hidden', 'false');
        editor.classList.add('is-open');
        document.body.classList.add('vs-drawer-open');
    }

    function closeEditor() {
        var editor = $('edgeoneRuleEditor');
        if (!editor) return;
        editor.hidden = true;
        editor.setAttribute('aria-hidden', 'true');
        editor.classList.remove('is-open');
        document.body.classList.remove('vs-drawer-open');
        closeActionPicker();
    }

    function openActionPicker(target) {
        state.pickerTarget = target;
        renderActionPicker();
        var picker = $('edgeoneRuleActionPicker');
        if (!picker) return;
        picker.hidden = false;
        picker.setAttribute('aria-hidden', 'false');
        picker.classList.add('is-open');
    }

    function closeActionPicker() {
        state.pickerTarget = null;
        var picker = $('edgeoneRuleActionPicker');
        if (!picker) return;
        picker.hidden = true;
        picker.setAttribute('aria-hidden', 'true');
        picker.classList.remove('is-open');
    }

    function findRuleRow(id) {
        if (typeof window.edgeoneFindRuleRow === 'function') {
            return window.edgeoneFindRuleRow(id);
        }
        var node = $('edgeoneRuleRowsMeta');
        if (!node) return null;
        try {
            var rows = JSON.parse(node.textContent || '[]');
            for (var i = 0; i < rows.length; i++) {
                if (rows[i] && rows[i].RuleId === id) return rows[i];
            }
        } catch (err) { /* ignore */ }
        return null;
    }

    function collectUiFromSection(section, ui) {
        if (!section || !ui) return;
        ui.conditionsDirty = true;
        ui.preserveCondition = '';
        ui.parseWarning = false;
        var logicSel = section.querySelector('.vs-edgeone-rule-cond-logic');
        ui.logic = logicSel && logicSel.value === 'or' ? 'or' : 'and';
        ui.conditions = [];
        section.querySelectorAll('.vs-edgeone-rule-cond-row').forEach(function (row) {
            ui.conditions.push({
                matchType: (row.querySelector('.vs-edgeone-rule-cond-type') || {}).value || 'host',
                paramName: (row.querySelector('.vs-edgeone-rule-cond-name') || {}).value || '',
                operator: (row.querySelector('.vs-edgeone-rule-cond-op') || {}).value || 'equal',
                value: (row.querySelector('.vs-edgeone-rule-cond-value') || {}).value || ''
            });
        });
    }

    function collectSubRulesFromDom(branchIndex) {
        var branch = state.rule.Branches[branchIndex];
        if (!branch) return;
        var nodes = document.querySelectorAll('.vs-edgeone-rule-subrule[data-branch="' + branchIndex + '"]');
        if (nodes.length === 0) return;
        var oldSubs = branch.SubRules || [];
        branch.SubRules = [];
        nodes.forEach(function (node, si) {
            var desc = node.querySelector('.vs-edgeone-rule-subrule-desc');
            var elseCb = node.querySelector('.vs-edgeone-rule-subrule-is-else');
            var ui = ensureSubRuleUi(branchIndex, si);
            if (elseCb) ui.isElse = elseCb.checked;
            if (ui.isElse) {
                ui.conditions = [];
                ui.conditionsDirty = true;
                ui.preserveCondition = '';
            } else {
                collectUiFromSection(node, ui);
            }
            var oldBranch = oldSubs[si] && oldSubs[si].Branches && oldSubs[si].Branches[0]
                ? oldSubs[si].Branches[0]
                : { Condition: '', Actions: [] };
            oldBranch.Condition = ui.isElse ? '' : buildConditionExpression(ui);
            branch.SubRules.push({
                Description: desc && desc.value.trim() ? [desc.value.trim()] : [],
                Branches: [oldBranch]
            });
        });
    }

    function getActionFromCard(card) {
        var scope = card.getAttribute('data-scope');
        var bi = parseInt(card.getAttribute('data-branch'), 10);
        var ai = parseInt(card.getAttribute('data-action'), 10);
        if (scope === 'subrule') {
            var si = parseInt(card.getAttribute('data-subrule'), 10);
            return getSubBranch(bi, si).Actions[ai];
        }
        return state.rule.Branches[bi].Actions[ai];
    }

    function collectActionFieldsFromDom() {
        document.querySelectorAll('.vs-edgeone-rule-action-card').forEach(function (card) {
            var action = getActionFromCard(card);
            if (!action) return;
            card.querySelectorAll('.vs-edgeone-rule-action-field').forEach(function (fieldNode) {
                var key = fieldNode.getAttribute('data-field-key');
                if (!key) return;
                var input = fieldNode.querySelector('.vs-edgeone-rule-action-field-input');
                var repeat = fieldNode.querySelector('[data-field-type="status_rows"], [data-field-type="header_rows"], [data-field-type="error_page_rows"], [data-field-type="failover_rows"], [data-field-type="auth_property_rows"]');
                var checkboxes = fieldNode.querySelector('[data-field-type="checkboxes"]');
                var durAmount = fieldNode.querySelector('[data-field-type="duration-amount"]');
                if (repeat) {
                    var ftype = repeat.getAttribute('data-field-type');
                    var rows = [];
                    repeat.querySelectorAll('.vs-edgeone-rule-repeat-row').forEach(function (row) {
                        if (ftype === 'status_rows') {
                            var code = row.querySelector('.vs-edgeone-rule-status-code');
                            var amt = row.querySelector('.vs-edgeone-rule-duration-amount');
                            var unit = row.querySelector('.vs-edgeone-rule-duration-unit');
                            if (!code || !code.value) return;
                            rows.push({
                                StatusCode: parseInt(code.value, 10) || 0,
                                CacheTime: toSeconds(amt ? amt.value : 0, unit ? unit.value : 's')
                            });
                        } else if (ftype === 'header_rows') {
                            var act = row.querySelector('.vs-edgeone-rule-header-action');
                            var name = row.querySelector('.vs-edgeone-rule-header-name');
                            var valInput = row.querySelector('.vs-edgeone-rule-header-value');
                            if (!name || !name.value.trim()) return;
                            var item = { Action: act ? act.value : 'add', Name: name.value.trim() };
                            if (valInput && valInput.value.trim()) item.Value = valInput.value.trim();
                            rows.push(item);
                        } else if (ftype === 'error_page_rows') {
                            var code2 = row.querySelector('.vs-edgeone-rule-status-code');
                            var url = row.querySelector('.vs-edgeone-rule-error-url');
                            if (!code2 || !code2.value) return;
                            rows.push({
                                StatusCode: parseInt(code2.value, 10) || 0,
                                RedirectURL: url ? url.value.trim() : ''
                            });
                        } else if (ftype === 'failover_rows') {
                            var mode = row.querySelector('.vs-edgeone-rule-failover-mode');
                            if (!mode) return;
                            var item = { Mode: mode.value };
                            var origin = row.querySelector('.vs-edgeone-rule-failover-origin');
                            var protocol = row.querySelector('.vs-edgeone-rule-failover-protocol');
                            var httpPort = row.querySelector('.vs-edgeone-rule-failover-http-port');
                            var httpsPort = row.querySelector('.vs-edgeone-rule-failover-https-port');
                            var redirect = row.querySelector('.vs-edgeone-rule-failover-redirect');
                            var page = row.querySelector('.vs-edgeone-rule-failover-page');
                            var status = row.querySelector('.vs-edgeone-rule-failover-status');
                            if (origin && origin.value.trim()) item.Origin = origin.value.trim();
                            if (protocol) item.OriginProtocol = protocol.value;
                            if (httpPort && httpPort.value !== '') item.HTTPOriginPort = parseInt(httpPort.value, 10) || 80;
                            if (httpsPort && httpsPort.value !== '') item.HTTPSOriginPort = parseInt(httpsPort.value, 10) || 443;
                            if (redirect && redirect.value.trim()) item.RedirectURL = redirect.value.trim();
                            if (page && page.value.trim()) item.ResponsePageId = page.value.trim();
                            if (status && status.value !== '') item.StatusCode = parseInt(status.value, 10) || 302;
                            rows.push(item);
                        } else if (ftype === 'auth_property_rows') {
                            var authType = row.querySelector('.vs-edgeone-rule-auth-type');
                            var authName = row.querySelector('.vs-edgeone-rule-auth-name');
                            var authValue = row.querySelector('.vs-edgeone-rule-auth-value');
                            if (!authName || !authName.value.trim()) return;
                            rows.push({
                                Type: authType ? authType.value : 'QueryString',
                                Name: authName.value.trim(),
                                Value: authValue ? authValue.value.trim() : ''
                            });
                        }
                    });
                    setNested(action, key, rows);
                    return;
                }
                if (checkboxes) {
                    var vals = [];
                    checkboxes.querySelectorAll('.vs-edgeone-rule-checkbox-input:checked').forEach(function (cb) {
                        vals.push(cb.value);
                    });
                    setNested(action, key, vals);
                    return;
                }
                if (durAmount) {
                    var durUnit = fieldNode.querySelector('[data-field-type="duration-unit"]');
                    setNested(action, key, toSeconds(durAmount.value, durUnit ? durUnit.value : 's'));
                    return;
                }
                if (!input) return;
                var type = input.getAttribute('data-field-type');
                var val;
                if (type === 'switch') {
                    val = input.checked ? 'on' : 'off';
                } else if (type === 'number') {
                    val = input.value === '' ? 0 : Number(input.value);
                } else if (type === 'taglist') {
                    val = textToTaglist(input.value);
                } else {
                    val = input.value;
                }
                setNested(action, key, val);
            });
            applyActionBeforeSave(action);
        });
    }

    function collectConditionsFromDom() {
        state.branchUi.forEach(function (ui, branchIndex) {
            var section = $('edgeoneRuleBranch-' + branchIndex);
            collectUiFromSection(section, ui);
        });
        Object.keys(state.subRuleUi).forEach(function (bi) {
            var branchIndex = parseInt(bi, 10);
            (state.subRuleUi[branchIndex] || []).forEach(function (ui, si) {
                if (ui && ui.isElse) return;
                var node = document.querySelector('.vs-edgeone-rule-subrule[data-branch="' + branchIndex + '"][data-subrule="' + si + '"]');
                collectUiFromSection(node, ui);
            });
        });
    }

    function saveRule() {
        if (!state.rule) return;
        collectConditionsFromDom();
        collectActionFieldsFromDom();
        (state.rule.Branches || []).forEach(function (branch, i) {
            collectSubRulesFromDom(i);
        });
        syncRuleFromForm();

        if (!state.rule.RuleName.trim()) {
            toast('请填写规则名称', 'error');
            return;
        }
        var hasEmptyBranch = false;
        (state.rule.Branches || []).forEach(function (branch, i) {
            var cond = buildConditionExpression(state.branchUi[i]);
            if (!cond) hasEmptyBranch = true;
            branch.Condition = cond;
        });
        (state.rule.Branches || []).forEach(function (branch, bi) {
            (branch.SubRules || []).forEach(function (sub, si) {
                var ui = state.subRuleUi[bi] && state.subRuleUi[bi][si];
                if (ui && sub.Branches && sub.Branches[0]) {
                    if (ui.isElse) {
                        sub.Branches[0].Condition = '';
                    } else {
                        var sc = buildConditionExpression(ui);
                        if (!sc) hasEmptyBranch = true;
                        sub.Branches[0].Condition = sc;
                    }
                }
            });
        });
        if (hasEmptyBranch) {
            toast('请完善 IF 匹配条件', 'error');
            return;
        }

        var body = new FormData();
        body.set('action', 'rule_save');
        body.set('rule_json', JSON.stringify(state.rule));

        var btnPub = $('edgeoneRuleEditorSavePublish');
        var btnOnly = $('edgeoneRuleEditorSaveOnly');
        if (btnPub) btnPub.disabled = true;
        if (btnOnly) btnOnly.disabled = true;

        postFormData(body).then(function (data) {
            if (data.code === 1) {
                toast(data.msg || '保存成功', 'success');
                closeEditor();
                if (typeof window.edgeoneRefreshRulesList === 'function') {
                    window.edgeoneRefreshRulesList(true);
                }
            } else {
                toast(data.msg || '保存失败', 'error');
            }
        }).catch(function () {
            toast('网络异常', 'error');
        }).finally(function () {
            if (btnPub) btnPub.disabled = false;
            if (btnOnly) btnOnly.disabled = false;
        });
    }

    var editorBound = false;

    function bindEditorOnce() {
        if (editorBound) return;
        editorBound = true;

        document.querySelectorAll('[data-rule-editor-close]').forEach(function (node) {
            node.addEventListener('click', closeEditor);
        });
        document.querySelectorAll('[data-action-picker-close]').forEach(function (node) {
            node.addEventListener('click', closeActionPicker);
        });

        var savePub = $('edgeoneRuleEditorSavePublish');
        var saveOnly = $('edgeoneRuleEditorSaveOnly');
        if (savePub) savePub.addEventListener('click', saveRule);
        if (saveOnly) saveOnly.addEventListener('click', saveRule);

        var addIf = $('edgeoneRuleEditorAddIf');
        if (addIf) {
            addIf.addEventListener('click', function () {
                state.rule.Branches.push({ Condition: '', Actions: [], SubRules: [] });
                state.branchUi.push(defaultBranchUi());
                renderBranches();
            });
        }

        var search = $('edgeoneRuleActionSearch');
        if (search) search.addEventListener('input', renderActionPicker);

        var branchesHost = $('edgeoneRuleEditorBranches');
        if (branchesHost) {
            branchesHost.addEventListener('click', function (e) {
                var addCond = e.target.closest('.vs-edgeone-rule-cond-add');
                if (addCond) {
                    var scope = addCond.getAttribute('data-scope') || 'branch';
                    var bi = parseInt(addCond.getAttribute('data-branch-index'), 10);
                    if (scope === 'subrule') {
                        var si = parseInt(addCond.getAttribute('data-subrule-index'), 10);
                        ensureSubRuleUi(bi, si).conditions.push({ matchType: 'host', operator: 'equal', paramName: '', value: '' });
                    } else {
                        state.branchUi[bi].conditions.push({ matchType: 'host', operator: 'equal', paramName: '', value: '' });
                    }
                    renderBranches();
                    return;
                }
                var rmCond = e.target.closest('.vs-edgeone-rule-cond-remove');
                if (rmCond) {
                    var row = rmCond.closest('.vs-edgeone-rule-cond-row');
                    var scope2 = row.getAttribute('data-scope') || 'branch';
                    var bIdx = parseInt(row.getAttribute('data-branch'), 10);
                    var cIdx = parseInt(row.getAttribute('data-cond'), 10);
                    var ui = scope2 === 'subrule'
                        ? ensureSubRuleUi(bIdx, parseInt(row.getAttribute('data-subrule'), 10))
                        : state.branchUi[bIdx];
                    ui.conditions.splice(cIdx, 1);
                    if (ui.conditions.length === 0) {
                        ui.conditions.push({ matchType: 'host', operator: 'equal', paramName: '', value: '' });
                    }
                    renderBranches();
                    return;
                }
                var addAction = e.target.closest('.vs-edgeone-rule-action-add');
                if (addAction) {
                    var scope3 = addAction.getAttribute('data-scope') || 'branch';
                    var bi2 = parseInt(addAction.getAttribute('data-branch-index'), 10);
                    if (scope3 === 'subrule') {
                        openActionPicker({ type: 'subrule', branchIndex: bi2, subIndex: parseInt(addAction.getAttribute('data-subrule-index'), 10) });
                    } else {
                        openActionPicker({ type: 'branch', branchIndex: bi2 });
                    }
                    return;
                }
                var rmAction = e.target.closest('.vs-edgeone-rule-action-remove');
                if (rmAction) {
                    var card = rmAction.closest('.vs-edgeone-rule-action-card');
                    var scope4 = card.getAttribute('data-scope') || 'branch';
                    var b = parseInt(card.getAttribute('data-branch'), 10);
                    var a = parseInt(card.getAttribute('data-action'), 10);
                    if (scope4 === 'subrule') {
                        getSubBranch(b, parseInt(card.getAttribute('data-subrule'), 10)).Actions.splice(a, 1);
                    } else {
                        state.rule.Branches[b].Actions.splice(a, 1);
                    }
                    renderBranches();
                    return;
                }
                var rmBranch = e.target.closest('.vs-edgeone-rule-branch-remove');
                if (rmBranch) {
                    var idx = parseInt(rmBranch.getAttribute('data-branch-index'), 10);
                    state.rule.Branches.splice(idx, 1);
                    state.branchUi.splice(idx, 1);
                    renderBranches();
                    return;
                }
                var addSub = e.target.closest('.vs-edgeone-rule-subrule-add');
                if (addSub) {
                    var sb = parseInt(addSub.getAttribute('data-branch-index'), 10);
                    if (!state.rule.Branches[sb].SubRules) state.rule.Branches[sb].SubRules = [];
                    if (!state.subRuleUi[sb]) state.subRuleUi[sb] = [];
                    var subs = state.rule.Branches[sb].SubRules;
                    var uis = state.subRuleUi[sb];
                    var newSub = { Description: [], Branches: [{ Condition: '', Actions: [] }] };
                    var newUi = defaultBranchUi();
                    if (subs.length && uis[subs.length - 1] && uis[subs.length - 1].isElse) {
                        subs.splice(subs.length - 1, 0, newSub);
                        uis.splice(uis.length - 1, 0, newUi);
                    } else {
                        subs.push(newSub);
                        uis.push(newUi);
                    }
                    renderBranches();
                    return;
                }
                var addSubElse = e.target.closest('.vs-edgeone-rule-subrule-add-else');
                if (addSubElse) {
                    var sb2 = parseInt(addSubElse.getAttribute('data-branch-index'), 10);
                    if (!state.rule.Branches[sb2].SubRules) state.rule.Branches[sb2].SubRules = [];
                    if (!state.subRuleUi[sb2]) state.subRuleUi[sb2] = [];
                    var uis2 = state.subRuleUi[sb2];
                    if (uis2.some(function (u) { return u && u.isElse; })) {
                        toast('每个 IF 块仅允许一个 ELSE 兜底分支', 'error');
                        return;
                    }
                    state.rule.Branches[sb2].SubRules.push({ Description: [], Branches: [{ Condition: '', Actions: [] }] });
                    var elseUi = defaultBranchUi();
                    elseUi.isElse = true;
                    elseUi.conditions = [];
                    elseUi.conditionsDirty = true;
                    uis2.push(elseUi);
                    renderBranches();
                    return;
                }
                var rmSub = e.target.closest('.vs-edgeone-rule-subrule-remove');
                if (rmSub) {
                    var sbi = parseInt(rmSub.getAttribute('data-branch-index'), 10);
                    var ssi = parseInt(rmSub.getAttribute('data-subrule-index'), 10);
                    state.rule.Branches[sbi].SubRules.splice(ssi, 1);
                    if (state.subRuleUi[sbi]) state.subRuleUi[sbi].splice(ssi, 1);
                    renderBranches();
                    return;
                }
                var repeatAdd = e.target.closest('.vs-edgeone-rule-repeat-add');
                if (repeatAdd) {
                    collectAllFromDom();
                    var wrap = repeatAdd.closest('.vs-edgeone-rule-repeat-rows');
                    if (!wrap) return;
                    var ftype = wrap.getAttribute('data-field-type');
                    if (ftype === 'failover_rows') {
                        if (wrap.querySelectorAll('.vs-edgeone-rule-failover-row').length >= 2) return;
                        repeatAdd.insertAdjacentHTML('beforebegin', renderFailoverRow({
                            Mode: 'FailoverToHost', OriginProtocol: 'follow', HTTPOriginPort: 80, HTTPSOriginPort: 443
                        }, wrap.querySelectorAll('.vs-edgeone-rule-repeat-row').length));
                        return;
                    }
                    var rowHtml = '<div class="vs-edgeone-rule-repeat-row">';
                    if (ftype === 'status_rows') {
                        rowHtml += '<input type="number" class="vs-input vs-edgeone-rule-status-code" placeholder="状态码" value="404">';
                        rowHtml += '<input type="number" class="vs-input vs-edgeone-rule-duration-amount" min="0" value="10">';
                        rowHtml += '<select class="vs-input vs-edgeone-rule-duration-unit"><option value="s" selected>秒</option><option value="m">分</option><option value="h">时</option><option value="d">天</option></select>';
                    } else if (ftype === 'header_rows') {
                        var withVal = wrap.getAttribute('data-with-value') === '1';
                        rowHtml += '<select class="vs-input vs-edgeone-rule-header-action"><option value="add">添加</option><option value="set">设置</option><option value="del">删除</option></select>';
                        rowHtml += '<input type="text" class="vs-input vs-edgeone-rule-header-name" placeholder="Header 名称">';
                        if (withVal) rowHtml += '<input type="text" class="vs-input vs-edgeone-rule-header-value" placeholder="Header 值">';
                    } else if (ftype === 'error_page_rows') {
                        rowHtml += '<input type="number" class="vs-input vs-edgeone-rule-status-code" placeholder="状态码" value="404">';
                        rowHtml += '<input type="text" class="vs-input vs-edgeone-rule-error-url" placeholder="跳转 URL">';
                    } else if (ftype === 'auth_property_rows') {
                        rowHtml += '<select class="vs-input vs-edgeone-rule-auth-type"><option value="QueryString" selected>URL 查询参数</option><option value="Header">HTTP 请求头</option></select>';
                        rowHtml += '<input type="text" class="vs-input vs-edgeone-rule-auth-name" placeholder="参数名称">';
                        rowHtml += '<input type="text" class="vs-input vs-edgeone-rule-auth-value" placeholder="参数值">';
                    }
                    rowHtml += '<button type="button" class="vs-edgeone-rule-repeat-remove" title="删除">&times;</button></div>';
                    repeatAdd.insertAdjacentHTML('beforebegin', rowHtml);
                    return;
                }
                var repeatRm = e.target.closest('.vs-edgeone-rule-repeat-remove');
                if (repeatRm) {
                    var rrow = repeatRm.closest('.vs-edgeone-rule-repeat-row');
                    if (rrow) rrow.remove();
                }
            });

            branchesHost.addEventListener('change', function (e) {
                var elseToggle = e.target.closest('.vs-edgeone-rule-subrule-is-else');
                if (elseToggle) {
                    collectAllFromDom();
                    var subNode = elseToggle.closest('.vs-edgeone-rule-subrule');
                    if (subNode) {
                        var bi = parseInt(subNode.getAttribute('data-branch'), 10);
                        var si = parseInt(subNode.getAttribute('data-subrule'), 10);
                        var ui = ensureSubRuleUi(bi, si);
                        ui.isElse = elseToggle.checked;
                        if (ui.isElse) {
                            ui.conditions = [];
                            ui.conditionsDirty = true;
                            ui.preserveCondition = '';
                        }
                    }
                    renderBranches();
                    return;
                }
                var logic = e.target.closest('.vs-edgeone-rule-cond-logic');
                if (logic) {
                    var scope = logic.getAttribute('data-scope') || 'branch';
                    var bi = parseInt(logic.getAttribute('data-branch'), 10);
                    if (scope === 'subrule') {
                        ensureSubRuleUi(bi, parseInt(logic.getAttribute('data-subrule'), 10)).logic = logic.value === 'or' ? 'or' : 'and';
                    } else {
                        state.branchUi[bi].logic = logic.value === 'or' ? 'or' : 'and';
                    }
                }
            });
        }

        var pickerBody = $('edgeoneRuleActionPickerBody');
        if (pickerBody) {
            pickerBody.addEventListener('click', function (e) {
                var pick = e.target.closest('.vs-edgeone-rule-action-pick');
                if (!pick || !state.pickerTarget) return;
                var name = pick.getAttribute('data-action-name');
                if (!name) return;
                var target = state.pickerTarget;
                if (target.type === 'subrule') {
                    var actions = getSubBranch(target.branchIndex, target.subIndex).Actions;
                    actions.push(cloneActionDefaults(name));
                } else {
                    if (!state.rule.Branches[target.branchIndex].Actions) state.rule.Branches[target.branchIndex].Actions = [];
                    state.rule.Branches[target.branchIndex].Actions.push(cloneActionDefaults(name));
                }
                closeActionPicker();
                renderBranches();
            });
        }

        var nav = $('edgeoneRuleEditorNav');
        if (nav) {
            nav.addEventListener('click', function (e) {
                var item = e.target.closest('.vs-edgeone-rule-editor__nav-item');
                if (!item) return;
                var target = item.getAttribute('data-nav');
                nav.querySelectorAll('.vs-edgeone-rule-editor__nav-item').forEach(function (n) {
                    n.classList.toggle('is-active', n === item);
                });
                if (target === 'meta') {
                    var main = document.querySelector('.vs-edgeone-rule-editor__main');
                    if (main) main.scrollTop = 0;
                    return;
                }
                var subM = target && target.match(/^sub-(\d+)-(\d+)$/);
                if (subM) {
                    var subEl = $('edgeoneRuleSub-' + subM[1] + '-' + subM[2]);
                    if (subEl) subEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    return;
                }
                var m = target && target.match(/^branch-(\d+)$/);
                if (m) {
                    var el = $('edgeoneRuleBranch-' + m[1]);
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }
    }

    window.VS_EDGEONE_RULES_EDITOR = {
        open: openEditor,
        close: closeEditor,
        ready: true
    };
})();
