/**
 * EdgeOne 规则引擎全屏编辑器（对齐腾讯云控制台 / L7Acc API）
 */
(function () {
    'use strict';

    var catalog = null;
    var state = {
        rule: null,
        branchUi: [],
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
            conditions: [{ matchType: 'host', operator: 'equal', paramName: '', value: '' }]
        };
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

    function renderConditionRow(branchIndex, rowIndex, row) {
        var mt = findMatchType(row.matchType);
        var needsName = mt && mt.needsName;
        var needsValue = operatorNeedsValue(row.operator);
        var html = '<div class="vs-edgeone-rule-cond-row" data-branch="' + branchIndex + '" data-cond="' + rowIndex + '">';
        html += '<select class="vs-input vs-edgeone-rule-cond-type" aria-label="匹配类型">' + renderMatchTypeOptions(row.matchType) + '</select>';
        if (needsName) {
            html += '<input type="text" class="vs-input vs-edgeone-rule-cond-name" placeholder="参数名" value="' + escHtml(row.paramName) + '">';
        }
        html += '<select class="vs-input vs-edgeone-rule-cond-op" aria-label="运算符">' + renderOperatorOptions(row.operator, row.matchType) + '</select>';
        if (needsValue) {
            html += '<textarea class="vs-input vs-edgeone-rule-cond-value" rows="2" placeholder="匹配值，多个值换行或逗号分隔">' + escHtml(row.value) + '</textarea>';
        }
        html += '<button type="button" class="vs-edgeone-rule-cond-remove" title="删除条件">&times;</button>';
        html += '</div>';
        return html;
    }

    function renderActionCard(branchIndex, actionIndex, action) {
        var name = action.Name || '';
        var meta = catalog.actions && catalog.actions[name] ? catalog.actions[name] : null;
        var html = '<div class="vs-edgeone-rule-action-card" data-branch="' + branchIndex + '" data-action="' + actionIndex + '">';
        html += '<div class="vs-edgeone-rule-action-card__head">';
        html += '<strong>' + escHtml(actionLabel(name)) + '</strong>';
        html += '<span class="vs-edgeone-rule-action-card__code">' + escHtml(name) + '</span>';
        html += '<button type="button" class="vs-edgeone-rule-action-remove" title="删除操作">&times;</button>';
        html += '</div>';
        if (meta && meta.fields && meta.fields.length) {
            html += '<div class="vs-edgeone-rule-action-card__fields">';
            meta.fields.forEach(function (field, fi) {
                html += renderActionField(branchIndex, actionIndex, action, field, fi);
            });
            html += '</div>';
        } else {
            html += '<p class="vs-form-tip">该操作无额外字段，或使用 JSON 参数块。</p>';
        }
        html += '</div>';
        return html;
    }

    function renderActionField(branchIndex, actionIndex, action, field, fi) {
        var key = field.key || '';
        var val = getNested(action, key);
        var id = 'ruleActionField_' + branchIndex + '_' + actionIndex + '_' + fi;
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
            (field.options || []).forEach(function (opt) {
                html += '<option value="' + escHtml(opt) + '"' + (String(val) === String(opt) ? ' selected' : '') + '>' + escHtml(opt) + '</option>';
            });
            html += '</select>';
        } else if (field.type === 'text') {
            html += '<input type="text" class="vs-input vs-edgeone-rule-action-field-input" id="' + id + '" value="' + escHtml(val == null ? '' : val) + '" data-field-type="text">';
        } else {
            var jsonVal = val == null ? '{}' : JSON.stringify(val, null, 2);
            html += '<textarea class="vs-input vs-edgeone-rule-action-field-input" id="' + id + '" rows="6" data-field-type="json">' + escHtml(jsonVal) + '</textarea>';
        }
        html += '</div>';
        return html;
    }

    function renderBranchCard(branchIndex) {
        var branch = state.rule.Branches[branchIndex];
        var ui = state.branchUi[branchIndex] || defaultBranchUi();
        var html = '<section class="vs-edgeone-rule-branch" id="edgeoneRuleBranch-' + branchIndex + '" data-branch-index="' + branchIndex + '">';
        html += '<header class="vs-edgeone-rule-branch__head">';
        html += '<h5>IF · 匹配条件 ' + (branchIndex + 1) + '</h5>';
        if (state.rule.Branches.length > 1) {
            html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-branch-remove" data-branch-index="' + branchIndex + '">删除条件块</button>';
        }
        html += '</header>';
        html += '<div class="vs-edgeone-rule-branch__mode">';
        html += '<label><input type="radio" name="condMode' + branchIndex + '" value="builder"' + (!ui.useRaw ? ' checked' : '') + '> 可视化构建</label>';
        html += '<label><input type="radio" name="condMode' + branchIndex + '" value="raw"' + (ui.useRaw ? ' checked' : '') + '> 高级表达式</label>';
        html += '<select class="vs-input vs-edgeone-rule-cond-logic"' + (ui.useRaw ? ' hidden' : '') + ' aria-label="条件逻辑">';
        html += '<option value="and"' + (ui.logic === 'and' ? ' selected' : '') + '>满足全部（AND）</option>';
        html += '<option value="or"' + (ui.logic === 'or' ? ' selected' : '') + '>满足任一（OR）</option>';
        html += '</select></div>';
        html += '<div class="vs-edgeone-rule-cond-builder"' + (ui.useRaw ? ' hidden' : '') + '>';
        (ui.conditions || []).forEach(function (row, ri) {
            html += renderConditionRow(branchIndex, ri, row);
        });
        html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-cond-add" data-branch-index="' + branchIndex + '">+ 添加匹配条件</button>';
        html += '</div>';
        html += '<div class="vs-edgeone-rule-cond-raw"' + (!ui.useRaw ? ' hidden' : '') + '>';
        html += '<label class="vs-label">Condition 表达式</label>';
        html += '<textarea class="vs-input vs-edgeone-rule-cond-raw-input" rows="4" placeholder="${http.request.host} in [\'www.example.com\']">' + escHtml(ui.rawCondition || branch.Condition || '') + '</textarea>';
        html += '<p class="vs-form-tip">与腾讯云 API 字段 Condition 一致；复杂表达式可直接粘贴。</p>';
        html += '</div>';
        html += '<div class="vs-edgeone-rule-branch__then">';
        html += '<h5>THEN · 操作</h5>';
        html += '<div class="vs-edgeone-rule-actions-list">';
        (branch.Actions || []).forEach(function (action, ai) {
            html += renderActionCard(branchIndex, ai, action);
        });
        html += '</div>';
        html += '<button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-rule-action-add" data-branch-index="' + branchIndex + '">+ 添加操作</button>';
        html += '</div>';
        html += renderSubRules(branchIndex, branch.SubRules || []);
        html += '</section>';
        return html;
    }

    function renderSubRules(branchIndex, subRules) {
        var html = '<div class="vs-edgeone-rule-subrules">';
        html += '<div class="vs-edgeone-rule-subrules__head"><h5>子规则（SubRules）</h5></div>';
        subRules.forEach(function (sub, si) {
            html += '<div class="vs-edgeone-rule-subrule" data-branch="' + branchIndex + '" data-subrule="' + si + '">';
            html += '<input type="text" class="vs-input vs-edgeone-rule-subrule-desc" placeholder="子规则注释" value="' + escHtml((sub.Description && sub.Description[0]) || '') + '">';
            var subBranch = sub.Branches && sub.Branches[0] ? sub.Branches[0] : { Condition: '', Actions: [] };
            html += '<label class="vs-label">子规则 Condition</label>';
            html += '<textarea class="vs-input vs-edgeone-rule-subrule-condition" rows="2">' + escHtml(subBranch.Condition || '') + '</textarea>';
            html += '<label class="vs-label">子规则 Actions（JSON 数组）</label>';
            html += '<textarea class="vs-input vs-edgeone-rule-subrule-actions" rows="4">' + escHtml(JSON.stringify(subBranch.Actions || [], null, 2)) + '</textarea>';
            html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-subrule-remove" data-branch-index="' + branchIndex + '" data-subrule-index="' + si + '">删除子规则</button>';
            html += '</div>';
        });
        html += '<button type="button" class="vs-btn vs-btn--text vs-edgeone-rule-subrule-add" data-branch-index="' + branchIndex + '">+ 添加子规则</button>';
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

        if (ruleId) {
            var row = findRuleRow(ruleId);
            if (row) {
                rule = deepClone(row);
                state.branchUi = (rule.Branches || []).map(function (branch) {
                    return parseConditionExpression(branch.Condition || '');
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

    function openActionPicker(branchIndex) {
        state.pickerTarget = branchIndex;
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

    function collectSubRulesFromDom(branchIndex) {
        var branch = state.rule.Branches[branchIndex];
        if (!branch) return;
        var nodes = document.querySelectorAll('.vs-edgeone-rule-subrule[data-branch="' + branchIndex + '"]');
        if (nodes.length === 0) return;
        branch.SubRules = [];
        nodes.forEach(function (node) {
            var desc = node.querySelector('.vs-edgeone-rule-subrule-desc');
            var cond = node.querySelector('.vs-edgeone-rule-subrule-condition');
            var acts = node.querySelector('.vs-edgeone-rule-subrule-actions');
            var actions = [];
            try {
                actions = JSON.parse(acts && acts.value ? acts.value : '[]');
            } catch (err) {
                actions = [];
            }
            var sub = {
                Description: desc && desc.value.trim() ? [desc.value.trim()] : [],
                Branches: [{
                    Condition: cond ? cond.value.trim() : '',
                    Actions: Array.isArray(actions) ? actions : []
                }]
            };
            branch.SubRules.push(sub);
        });
    }

    function collectActionFieldsFromDom() {
        document.querySelectorAll('.vs-edgeone-rule-action-card').forEach(function (card) {
            var bi = parseInt(card.getAttribute('data-branch'), 10);
            var ai = parseInt(card.getAttribute('data-action'), 10);
            if (!state.rule.Branches[bi] || !state.rule.Branches[bi].Actions[ai]) return;
            var action = state.rule.Branches[bi].Actions[ai];
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
        });
    }

    function collectConditionsFromDom() {
        state.branchUi.forEach(function (ui, branchIndex) {
            var section = $('edgeoneRuleBranch-' + branchIndex);
            if (!section) return;
            var rawMode = section.querySelector('input[name="condMode' + branchIndex + '"][value="raw"]');
            ui.useRaw = rawMode && rawMode.checked;
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
                    var bi = parseInt(addCond.getAttribute('data-branch-index'), 10);
                    state.branchUi[bi].conditions.push({ matchType: 'host', operator: 'equal', paramName: '', value: '' });
                    renderBranches();
                    return;
                }
                var rmCond = e.target.closest('.vs-edgeone-rule-cond-remove');
                if (rmCond) {
                    var row = rmCond.closest('.vs-edgeone-rule-cond-row');
                    var bIdx = parseInt(row.getAttribute('data-branch'), 10);
                    var cIdx = parseInt(row.getAttribute('data-cond'), 10);
                    state.branchUi[bIdx].conditions.splice(cIdx, 1);
                    if (state.branchUi[bIdx].conditions.length === 0) {
                        state.branchUi[bIdx].conditions.push({ matchType: 'host', operator: 'equal', paramName: '', value: '' });
                    }
                    renderBranches();
                    return;
                }
                var addAction = e.target.closest('.vs-edgeone-rule-action-add');
                if (addAction) {
                    openActionPicker(parseInt(addAction.getAttribute('data-branch-index'), 10));
                    return;
                }
                var rmAction = e.target.closest('.vs-edgeone-rule-action-remove');
                if (rmAction) {
                    var card = rmAction.closest('.vs-edgeone-rule-action-card');
                    var b = parseInt(card.getAttribute('data-branch'), 10);
                    var a = parseInt(card.getAttribute('data-action'), 10);
                    state.rule.Branches[b].Actions.splice(a, 1);
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
                    renderBranches();
                    return;
                }
                var rmSub = e.target.closest('.vs-edgeone-rule-subrule-remove');
                if (rmSub) {
                    var sbi = parseInt(rmSub.getAttribute('data-branch-index'), 10);
                    var ssi = parseInt(rmSub.getAttribute('data-subrule-index'), 10);
                    state.rule.Branches[sbi].SubRules.splice(ssi, 1);
                    renderBranches();
                }
            });

            branchesHost.addEventListener('change', function (e) {
                var mode = e.target.closest('input[name^="condMode"]');
                if (mode) {
                    var branchIndex = parseInt(String(mode.name).replace('condMode', ''), 10);
                    var section = $('edgeoneRuleBranch-' + branchIndex);
                    if (!section) return;
                    var isRaw = mode.value === 'raw';
                    section.querySelector('.vs-edgeone-rule-cond-builder').hidden = isRaw;
                    section.querySelector('.vs-edgeone-rule-cond-raw').hidden = !isRaw;
                    section.querySelector('.vs-edgeone-rule-cond-logic').hidden = isRaw;
                    state.branchUi[branchIndex].useRaw = isRaw;
                    return;
                }
                var logic = e.target.closest('.vs-edgeone-rule-cond-logic');
                if (logic) {
                    var sec = logic.closest('.vs-edgeone-rule-branch');
                    if (sec) {
                        var bi2 = parseInt(sec.getAttribute('data-branch-index'), 10);
                        state.branchUi[bi2].logic = logic.value === 'or' ? 'or' : 'and';
                    }
                }
            });
        }

        var pickerBody = $('edgeoneRuleActionPickerBody');
        if (pickerBody) {
            pickerBody.addEventListener('click', function (e) {
                var pick = e.target.closest('.vs-edgeone-rule-action-pick');
                if (!pick || state.pickerTarget == null) return;
                var name = pick.getAttribute('data-action-name');
                if (!name) return;
                var bi3 = state.pickerTarget;
                if (!state.rule.Branches[bi3].Actions) state.rule.Branches[bi3].Actions = [];
                state.rule.Branches[bi3].Actions.push(cloneActionDefaults(name));
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
