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
        for (var i = 0; i < catalog.matchTypes.length; i++) {
            var mt = catalog.matchTypes[i];
            if (mt.all) continue;
            if (mt.needsName) {
                var prefix = mt.var.split('%s')[0];
                if (varExpr.indexOf(prefix) === 0) return mt.id;
            } else if (mt.var === varExpr) {
                return mt.id;
            }
        }
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
                useRaw: false,
                rawCondition: '',
                logic: 'and',
                conditions: [{ matchType: 'host', operator: 'equal', paramName: '', value: '' }]
            };
        }
        var split = splitConditionLogic(condition);
        var rows = [];
        split.parts.forEach(function (part) {
            var row = parseSingleCondition(part);
            if (row) rows.push(row);
        });
        if (rows.length === 0) {
            return { useRaw: true, rawCondition: condition, logic: 'and', conditions: [] };
        }
        return { useRaw: false, rawCondition: condition, logic: split.logic, conditions: rows };
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
        if (ui.useRaw) return String(ui.rawCondition || '').trim();
        var parts = (ui.conditions || []).map(buildSingleConditionExpr).filter(Boolean);
        if (parts.length === 0) return '';
        var joiner = ui.logic === 'or' ? ' or ' : ' and ';
        return parts.join(joiner);
    }

    function defaultBranchUi() {
        return {
            logic: 'and',
            useRaw: false,
            rawCondition: '',
            showAdvanced: false,
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

    function renderNav() {
        var nav = $('edgeoneRuleEditorNav');
        if (!nav) return;
        var html = '<div class="vs-edgeone-rule-editor__nav-title">规则导航</div>';
        html += '<button type="button" class="vs-edgeone-rule-editor__nav-item is-active" data-nav="meta">基本信息</button>';
        (state.rule.Branches || []).forEach(function (branch, i) {
            var label = 'IF · 条件 ' + (i + 1);
            if (branch.Condition) {
                label = branch.Condition.length > 28 ? branch.Condition.slice(0, 28) + '…' : branch.Condition;
            }
            html += '<button type="button" class="vs-edgeone-rule-editor__nav-item" data-nav="branch-' + i + '">' + escHtml(label) + '</button>';
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
        html += '</div>';
        return html;
    }

    function renderActionCard(scope, actionIndex, action) {
        normalizeCacheAction(action);
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
        } else {
            var jsonVal = val == null ? '{}' : JSON.stringify(val, null, 2);
            html += '<details class="vs-edgeone-rule-json-advanced"><summary>高级 JSON 配置</summary>';
            html += '<textarea class="vs-input vs-edgeone-rule-action-field-input" id="' + id + '" rows="5" data-field-type="json">' + escHtml(jsonVal) + '</textarea>';
            html += '</details>';
        }
        html += '</div>';
        return html;
    }

    function renderConditionSection(scope, ui, sectionId) {
        var pf = scopePrefix(scope);
        var html = '<div class="vs-edgeone-rule-cond-section" id="' + escHtml(sectionId) + '">';
        html += '<div class="vs-edgeone-rule-branch__mode">';
        html += '<select class="vs-input vs-edgeone-rule-cond-logic"' + (ui.useRaw ? ' hidden' : '') + ' aria-label="条件逻辑" data-scope="' + scope.type + '" data-branch="' + scope.branchIndex + '"';
        if (scope.type === 'subrule') html += ' data-subrule="' + scope.subIndex + '"';
        html += '>';
        html += '<option value="and"' + (ui.logic === 'and' ? ' selected' : '') + '>满足全部（AND）</option>';
        html += '<option value="or"' + (ui.logic === 'or' ? ' selected' : '') + '>满足任一（OR）</option>';
        html += '</select></div>';
        html += '<div class="vs-edgeone-rule-cond-builder"' + (ui.useRaw ? ' hidden' : '') + ' data-builder-for="' + pf + '">';
        (ui.conditions || []).forEach(function (row, ri) {
            html += renderConditionRow(scope, ri, row);
        });
        html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-cond-add" data-scope="' + scope.type + '" data-branch-index="' + scope.branchIndex + '"';
        if (scope.type === 'subrule') html += ' data-subrule-index="' + scope.subIndex + '"';
        html += '>+ 添加匹配条件</button>';
        html += '</div>';
        html += '<details class="vs-edgeone-rule-advanced"' + (ui.showAdvanced ? ' open' : '') + ' data-advanced-for="' + pf + '">';
        html += '<summary>开发者选项（高级表达式，一般无需修改）</summary>';
        html += '<label class="vs-label"><input type="checkbox" class="vs-edgeone-rule-cond-raw-toggle" data-scope="' + scope.type + '" data-branch="' + scope.branchIndex + '"';
        if (scope.type === 'subrule') html += ' data-subrule="' + scope.subIndex + '"';
        html += (ui.useRaw ? ' checked' : '') + '> 使用高级表达式</label>';
        html += '<textarea class="vs-input vs-edgeone-rule-cond-raw-input" rows="3" placeholder="仅技术人员使用"' + (ui.useRaw ? '' : ' hidden') + '>' + escHtml(ui.rawCondition || '') + '</textarea>';
        html += '</details></div>';
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
        var html = '<section class="vs-edgeone-rule-branch" id="edgeoneRuleBranch-' + branchIndex + '" data-branch-index="' + branchIndex + '">';
        html += '<header class="vs-edgeone-rule-branch__head">';
        html += '<h5>IF · 匹配条件 ' + (branchIndex + 1) + '</h5>';
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
        html += '<div class="vs-edgeone-rule-subrules__head"><h5>ELSE IF · 子规则</h5>';
        html += '<p class="vs-form-tip">当上方 IF 未完全匹配时，可继续添加子规则（与腾讯云 SubRules 一致）。</p></div>';
        subRules.forEach(function (sub, si) {
            var scope = { type: 'subrule', branchIndex: branchIndex, subIndex: si };
            var ui = ensureSubRuleUi(branchIndex, si);
            var subBranch = getSubBranch(branchIndex, si);
            if (subBranch.Condition && !ui._inited) {
                var parsed = parseConditionExpression(subBranch.Condition);
                ui.logic = parsed.logic;
                ui.useRaw = parsed.useRaw;
                ui.rawCondition = parsed.rawCondition || subBranch.Condition;
                ui.conditions = parsed.conditions.length ? parsed.conditions : ui.conditions;
                ui._inited = true;
            }
            html += '<div class="vs-edgeone-rule-subrule" data-branch="' + branchIndex + '" data-subrule="' + si + '">';
            html += '<header class="vs-edgeone-rule-subrule__head">';
            html += '<span class="vs-edgeone-rule-subrule__badge">子规则 ' + (si + 1) + '</span>';
            html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-subrule-remove" data-branch-index="' + branchIndex + '" data-subrule-index="' + si + '">删除</button>';
            html += '</header>';
            html += '<div class="vs-form-row"><label class="vs-label">注释</label>';
            html += '<input type="text" class="vs-input vs-edgeone-rule-subrule-desc" placeholder="可选，便于识别" value="' + escHtml((sub.Description && sub.Description[0]) || '') + '"></div>';
            html += renderConditionSection(scope, ui, 'edgeoneRuleSubCond-' + branchIndex + '-' + si);
            html += renderThenSection(scope, subBranch.Actions || []);
            html += '</div>';
        });
        html += '<button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-rule-subrule-add" data-branch-index="' + branchIndex + '">+ 添加子规则</button>';
        html += '</div>';
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
        var rawToggle = section.querySelector('.vs-edgeone-rule-cond-raw-toggle');
        ui.useRaw = rawToggle && rawToggle.checked;
        ui.showAdvanced = !!section.querySelector('.vs-edgeone-rule-advanced[open]');
        if (ui.useRaw) {
            var rawInput = section.querySelector('.vs-edgeone-rule-cond-raw-input');
            ui.rawCondition = rawInput ? rawInput.value : '';
            return;
        }
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
            var ui = ensureSubRuleUi(branchIndex, si);
            collectUiFromSection(node, ui);
            var oldBranch = oldSubs[si] && oldSubs[si].Branches && oldSubs[si].Branches[0]
                ? oldSubs[si].Branches[0]
                : { Condition: '', Actions: [] };
            oldBranch.Condition = buildConditionExpression(ui);
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
                var input = fieldNode.querySelector('.vs-edgeone-rule-action-field-input');
                if (!key || !input) return;
                var type = input.getAttribute('data-field-type');
                var val;
                if (type === 'switch') {
                    val = input.checked ? 'on' : 'off';
                } else if (type === 'number') {
                    val = input.value === '' ? 0 : Number(input.value);
                } else if (type === 'json') {
                    try {
                        val = JSON.parse(input.value || '{}');
                    } catch (err) {
                        val = getNested(action, key);
                    }
                } else {
                    val = input.value;
                }
                setNested(action, key, val);
            });
            applyCacheUiMode(action);
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
                    var sc = buildConditionExpression(ui);
                    if (!sc) hasEmptyBranch = true;
                    sub.Branches[0].Condition = sc;
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
                    state.rule.Branches[sb].SubRules.push({
                        Description: [],
                        Branches: [{ Condition: '', Actions: [] }]
                    });
                    ensureSubRuleUi(sb, state.rule.Branches[sb].SubRules.length - 1);
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
                }
            });

            branchesHost.addEventListener('change', function (e) {
                var rawToggle = e.target.closest('.vs-edgeone-rule-cond-raw-toggle');
                if (rawToggle) {
                    var wrap = rawToggle.closest('.vs-edgeone-rule-cond-section') || rawToggle.closest('.vs-edgeone-rule-subrule') || rawToggle.closest('.vs-edgeone-rule-branch');
                    if (!wrap) return;
                    var builder = wrap.querySelector('.vs-edgeone-rule-cond-builder');
                    var rawInput = wrap.querySelector('.vs-edgeone-rule-cond-raw-input');
                    var logic = wrap.querySelector('.vs-edgeone-rule-cond-logic');
                    var on = rawToggle.checked;
                    if (builder) builder.hidden = on;
                    if (rawInput) rawInput.hidden = !on;
                    if (logic) logic.hidden = on;
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
