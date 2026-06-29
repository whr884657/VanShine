/**
 * EdgeOne 后台交互
 */
(function () {
    'use strict';

    var COLORS = ['#3b82f6', '#f97316', '#06b6d4', '#6366f1', '#e11d48', '#0ea5e9', '#f59e0b'];
    var TOTAL_COLOR = '#1d4ed8';

    function pagePath() {
        return window.location.pathname;
    }

    function keepCleanUrl() {
        if (window.history && window.history.replaceState) {
            window.history.replaceState({ edgeone: true }, '', pagePath());
        }
    }

    function apiUrl() {
        return window.VS_EDGEONE_API || '';
    }

    function toast(msg, type) {
        if (window.VsToast) {
            VsToast.show(msg, type || 'success');
            return;
        }
        alert(msg);
    }

    function postFormData(body) {
        return fetch(apiUrl(), {
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

    function loadMainContent(url, pushState) {
        var main = document.getElementById('edgeoneMainContent');
        if (!main) {
            window.location.href = url.split('?')[0];
            return Promise.resolve();
        }

        var path = url.split('?')[0];
        main.classList.add('is-loading');
        return fetch(path + '?fragment=1', { credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) throw new Error('load_failed');
                return res.text();
            })
            .then(function (html) {
                main.innerHTML = html;
                main.classList.remove('is-loading');
                bindApiForms(main);
                bindFragmentForms(main);
                bindOverviewPage(main);
                bindCharts(main);
                updateNavActive(path);
                keepCleanUrl();
                if (pushState !== false && window.history.pushState) {
                    window.history.pushState({ edgeone: true }, '', path);
                }
            })
            .catch(function () {
                main.classList.remove('is-loading');
                window.location.href = path;
            });
    }

    function loadMainContentPost(form) {
        var main = document.getElementById('edgeoneMainContent');
        if (!main) return Promise.resolve();

        var body = new FormData(form);
        body.set('fragment', '1');
        main.classList.add('is-loading');

        return fetch(pagePath(), {
            method: 'POST',
            body: body,
            credentials: 'same-origin'
        }).then(function (res) {
            if (!res.ok) throw new Error('load_failed');
            return res.text();
        }).then(function (html) {
            main.innerHTML = html;
            main.classList.remove('is-loading');
            bindApiForms(main);
            bindFragmentForms(main);
            bindOverviewPage(main);
            bindCharts(main);
            keepCleanUrl();
        }).catch(function () {
            main.classList.remove('is-loading');
            toast('加载失败', 'error');
        });
    }

    function reloadMainContent() {
        return loadMainContent(pagePath(), false);
    }

    function collapseMobileDrawer() {
        var drawer = document.getElementById('edgeoneMobileNav');
        if (!drawer) return;
        drawer.classList.remove('is-open');
        var toggle = drawer.querySelector('.vs-edgeone-nav__drawer-toggle');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
        drawer.querySelectorAll('.vs-edgeone-nav__section.is-open').forEach(function (section) {
            section.classList.remove('is-open');
            var btn = section.querySelector('.vs-edgeone-nav__section-btn');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    }

    function updateMobileDrawerLabel() {
        var drawerCurrent = document.querySelector('.vs-edgeone-nav__drawer-current');
        if (!drawerCurrent) return;
        var activeLink = document.querySelector('.vs-edgeone-nav__mobile a.is-active');
        if (!activeLink) {
            drawerCurrent.textContent = 'EdgeOne 功能导航';
            return;
        }
        var section = activeLink.closest('.vs-edgeone-nav__section');
        if (section) {
            var sectionBtn = section.querySelector('.vs-edgeone-nav__section-btn');
            var sectionLabel = sectionBtn ? sectionBtn.childNodes[0].textContent.trim() : '';
            var itemLabel = activeLink.textContent.trim();
            drawerCurrent.textContent = sectionLabel ? sectionLabel + ' · ' + itemLabel : itemLabel;
            return;
        }
        drawerCurrent.textContent = activeLink.textContent.trim() || 'EdgeOne 功能导航';
    }

    function updateNavActive(url) {
        var path = url.split('?')[0];
        document.querySelectorAll('.vs-edgeone-nav a[href]').forEach(function (link) {
            var href = link.getAttribute('href') || '';
            var linkPath = href.split('?')[0];
            link.classList.toggle('is-active', linkPath === path);
        });

        document.querySelectorAll('.vs-edgeone-nav__tab, .vs-edgeone-nav__section').forEach(function (node) {
            node.classList.remove('is-active', 'is-open');
        });

        document.querySelectorAll('.vs-edgeone-nav a.is-active').forEach(function (link) {
            var tab = link.closest('.vs-edgeone-nav__tab');
            if (tab) tab.classList.add('is-active');
        });

        updateMobileDrawerLabel();
        collapseMobileDrawer();
    }

    function bindSpaNavigation() {
        var app = document.getElementById('edgeoneApp');
        if (!app) return;

        app.addEventListener('click', function (e) {
            var link = e.target.closest('.vs-edgeone-nav a[href]');
            if (!link || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var href = link.getAttribute('href');
            if (!href || href.indexOf('http') === 0) return;
            e.preventDefault();
            loadMainContent(href, true);
        });

        window.addEventListener('popstate', function () {
            if (window.history.state && window.history.state.edgeone) {
                loadMainContent(pagePath(), false);
            }
        });
    }

    function bindApiForms(root) {
        (root || document).querySelectorAll('.vs-edgeone-api-form').forEach(function (form) {
            if (form.dataset.bound === '1') return;
            form.dataset.bound = '1';
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var action = form.getAttribute('data-action');
                var body = new FormData(form);
                body.set('action', action);
                var btn = form.querySelector('[type="submit"]');
                if (btn) btn.disabled = true;
                postFormData(body)
                    .then(function (data) {
                        if (data.code === 1) {
                            toast(data.msg || '操作成功', 'success');
                            setTimeout(function () { reloadMainContent(); }, 400);
                        } else {
                            toast(data.msg || '操作失败', 'error');
                        }
                    })
                    .catch(function () {
                        toast('网络异常', 'error');
                    })
                    .finally(function () {
                        if (btn) btn.disabled = false;
                    });
            });
        });
    }

    function bindFragmentForms(root) {
        (root || document).querySelectorAll('.vs-edgeone-fragment-form').forEach(function (form) {
            if (form.dataset.bound === '1') return;
            form.dataset.bound = '1';
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (form.classList.contains('vs-edgeone-overview-form')) {
                    loadOverviewData(form);
                    return;
                }
                loadMainContentPost(form);
            });
        });
    }

    function bindOverviewPage(root) {
        var scope = root || document;
        var form = scope.querySelector('#edgeoneOverviewForm');
        if (!form) return;

        if (scope === document || scope.id === 'edgeoneMainContent') {
            loadOverviewData(form);
        }

        var zoneSelect = form.querySelector('#edgeoneFilterZone');
        var domainSelect = form.querySelector('#edgeoneFilterDomain');
        if (zoneSelect && domainSelect && zoneSelect.dataset.bound !== '1') {
            zoneSelect.dataset.bound = '1';
            zoneSelect.addEventListener('change', function () {
                var zoneId = zoneSelect.value;
                if (zoneId === '*' || zoneId === '') {
                    domainSelect.innerHTML = '<option value="">全部域名</option>';
                    domainSelect.disabled = true;
                    return;
                }
                domainSelect.disabled = true;
                var body = new FormData();
                body.set('action', 'overview_domains');
                body.set('filter_zone', zoneId);
                postFormData(body).then(function (data) {
                    domainSelect.innerHTML = '<option value="">全部域名</option>';
                    if (data.code === 1 && data.data && data.data.domains) {
                        data.data.domains.forEach(function (name) {
                            var opt = document.createElement('option');
                            opt.value = name;
                            opt.textContent = name;
                            domainSelect.appendChild(opt);
                        });
                    }
                    domainSelect.disabled = false;
                }).catch(function () {
                    domainSelect.disabled = false;
                });
            });
        }

        bindCustomFilters(form);
    }

    function bindCustomFilters(form) {
        if (!form || form.dataset.customBound === '1') return;
        form.dataset.customBound = '1';

        var wrap = form.querySelector('#edgeoneCustomFilters');
        if (!wrap) return;

        var list = wrap.querySelector('#edgeoneCustomFiltersList');
        var jsonInput = wrap.querySelector('#edgeoneCustomFiltersJson');
        var addBtn = wrap.querySelector('#edgeoneAddFilterBtn');
        var tpl = document.getElementById('edgeoneCustomFilterRowTpl');
        var defs = {};
        try {
            defs = JSON.parse(wrap.getAttribute('data-defs') || '{}');
        } catch (e) {
            defs = {};
        }

        function syncJson() {
            if (!jsonInput || !list) return;
            var rows = [];
            list.querySelectorAll('.vs-edgeone-custom-filter-row').forEach(function (row) {
                var keyEl = row.querySelector('.vs-edgeone-custom-filter-row__key');
                var opEl = row.querySelector('.vs-edgeone-custom-filter-row__op');
                var valEl = row.querySelector('.vs-edgeone-custom-filter-row__value');
                if (!keyEl || !opEl || !valEl) return;
                var key = keyEl.value.trim();
                if (!key) return;
                var values = valEl.value.split(/[\r\n,;|]+/).map(function (v) { return v.trim(); }).filter(Boolean);
                if (!values.length) return;
                rows.push({ key: key, operator: opEl.value, values: values });
            });
            jsonInput.value = JSON.stringify(rows);
            var emptyTip = list.querySelector('#edgeoneCustomFiltersEmpty');
            if (emptyTip) {
                emptyTip.style.display = rows.length ? 'none' : '';
            }
        }

        function bindRow(row) {
            row.querySelectorAll('select, input').forEach(function (el) {
                el.addEventListener('change', syncJson);
                el.addEventListener('input', syncJson);
            });
            var enumSel = row.querySelector('.vs-edgeone-custom-filter-row__value-enum');
            if (enumSel) {
                enumSel.addEventListener('change', function () {
                    var hidden = row.querySelector('.vs-edgeone-custom-filter-row__value');
                    if (!hidden) return;
                    var vals = [];
                    enumSel.querySelectorAll('option:checked').forEach(function (opt) {
                        vals.push(opt.value);
                    });
                    hidden.value = vals.join('\n');
                    syncJson();
                });
            }
            var removeBtn = row.querySelector('.vs-edgeone-custom-filter-row__remove');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    row.remove();
                    syncJson();
                });
            }
            var keyEl = row.querySelector('.vs-edgeone-custom-filter-row__key');
            if (keyEl) {
                keyEl.addEventListener('change', function () {
                    syncJson();
                });
            }
        }

        list.querySelectorAll('.vs-edgeone-custom-filter-row').forEach(bindRow);

        if (addBtn && tpl && list) {
            addBtn.addEventListener('click', function () {
                var html = tpl.innerHTML.replace(/__IDX__/g, String(Date.now()));
                var holder = document.createElement('div');
                holder.innerHTML = html.trim();
                var row = holder.firstElementChild;
                if (!row) return;
                list.appendChild(row);
                bindRow(row);
                syncJson();
            });
        }

        form.querySelectorAll('.vs-edgeone-range-tabs__item input').forEach(function (radio) {
            radio.addEventListener('change', function () {
                form.querySelectorAll('.vs-edgeone-range-tabs__item').forEach(function (item) {
                    item.classList.toggle('is-active', item.contains(radio) && radio.checked);
                });
            });
        });

        form.addEventListener('submit', syncJson);
        syncJson();
    }

    function loadOverviewData(form) {
        var dashboardHost = document.getElementById('edgeoneDashboardHost');
        if (!dashboardHost) return;

        dashboardHost.classList.add('vs-edgeone-overview--loading');
        dashboardHost.innerHTML = '<aside class="vs-edgeone-overview__sidebar"><article class="vs-edgeone-kpi vs-edgeone-kpi--sidebar vs-edgeone-kpi--loading"><span class="vs-edgeone-kpi__label">加载中</span><strong class="vs-edgeone-kpi__value">—</strong></article></aside><div class="vs-edgeone-overview__main"><div class="vs-panel vs-edgeone-chart-panel vs-edgeone-chart-panel--loading"><p class="vs-form-tip">数据加载中…</p></div></div>';

        var body = new FormData(form);
        body.set('action', 'overview_data');

        postFormData(body).then(function (data) {
            if (data.code !== 1 || !data.data) {
                dashboardHost.classList.remove('vs-edgeone-overview--loading');
                dashboardHost.innerHTML = '<div class="vs-panel"><p class="vs-form-tip">加载失败：' + (data.msg || '未知错误') + '</p></div>';
                return;
            }
            if (data.data.dashboard_html) {
                dashboardHost.outerHTML = data.data.dashboard_html;
            }
            bindCharts(document);
            keepCleanUrl();
        }).catch(function () {
            dashboardHost.classList.remove('vs-edgeone-overview--loading');
            dashboardHost.innerHTML = '<div class="vs-panel"><p class="vs-form-tip">数据加载失败，请稍后重试</p></div>';
        });
    }

    function bindZoneForm() {
        var form = document.getElementById('edgeoneZoneForm');
        if (!form || form.dataset.bound === '1') return;
        form.dataset.bound = '1';
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var body = new FormData(form);
            postFormData(body)
                .then(function (data) {
                    if (data.code === 1) {
                        toast(data.msg || '已切换', 'success');
                        reloadMainContent();
                    } else {
                        toast(data.msg || '切换失败', 'error');
                    }
                })
                .catch(function () {
                    toast('网络异常', 'error');
                });
        });
    }

    function bindMobileDrawer() {
        var drawer = document.getElementById('edgeoneMobileNav');
        if (!drawer || drawer.dataset.bound === '1') return;
        drawer.dataset.bound = '1';

        var toggle = drawer.querySelector('.vs-edgeone-nav__drawer-toggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                var open = drawer.classList.contains('is-open');
                if (open) {
                    collapseMobileDrawer();
                    return;
                }
                drawer.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            });
        }

        drawer.querySelectorAll('.vs-edgeone-nav__section-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var section = btn.closest('.vs-edgeone-nav__section');
                if (!section) return;
                var open = section.classList.contains('is-open');
                drawer.querySelectorAll('.vs-edgeone-nav__section.is-open').forEach(function (other) {
                    if (other !== section) {
                        other.classList.remove('is-open');
                        var ob = other.querySelector('.vs-edgeone-nav__section-btn');
                        if (ob) ob.setAttribute('aria-expanded', 'false');
                    }
                });
                section.classList.toggle('is-open', !open);
                btn.setAttribute('aria-expanded', open ? 'false' : 'true');
            });
        });

        drawer.querySelectorAll('a[href]').forEach(function (link) {
            link.addEventListener('click', function () {
                collapseMobileDrawer();
            });
        });
    }

    function bindDesktopDropdowns() {
        document.querySelectorAll('.vs-edgeone-nav__tab--dropdown').forEach(function (tab) {
            var btn = tab.querySelector('.vs-edgeone-nav__tab-btn');
            if (!btn || tab.dataset.bound === '1') return;
            tab.dataset.bound = '1';
            tab.addEventListener('mouseenter', function () {
                btn.setAttribute('aria-expanded', 'true');
            });
            tab.addEventListener('mouseleave', function () {
                btn.setAttribute('aria-expanded', 'false');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        keepCleanUrl();
        bindSpaNavigation();
        bindApiForms(document);
        bindFragmentForms(document);
        bindOverviewPage(document);
        bindZoneForm();
        bindMobileDrawer();
        bindDesktopDropdowns();
        bindCharts(document);
        if (window.history && window.history.replaceState) {
            window.history.replaceState({ edgeone: true }, '', pagePath());
        }
    });

    function formatChartValue(value, unit) {
        var n = Number(value) || 0;
        if (unit === 'bytes') {
            var units = ['B', 'KB', 'MB', 'GB', 'TB'];
            var i = 0;
            while (n >= 1024 && i < units.length - 1) {
                n /= 1024;
                i++;
            }
            return (i === 0 ? Math.round(n) : n.toFixed(2)) + ' ' + units[i];
        }
        if (unit === 'bps') {
            if (n >= 1e9) return (n / 1e9).toFixed(2) + ' Gbps';
            if (n >= 1e6) return (n / 1e6).toFixed(2) + ' Mbps';
            if (n >= 1e3) return (n / 1e3).toFixed(2) + ' Kbps';
            return Math.round(n) + ' bps';
        }
        if (unit === 'ms') return n.toFixed(2) + ' ms';
        return n.toLocaleString();
    }

    function bindCharts(root) {
        var scope = root || document;
        scope.querySelectorAll('.vs-edgeone-chart-data').forEach(function (node) {
            var targetId = node.getAttribute('data-target');
            var unit = node.getAttribute('data-unit') || 'number';
            var canvas = document.getElementById(targetId);
            if (!canvas) return;
            var points;
            try {
                points = JSON.parse(node.textContent || '[]');
            } catch (e) {
                return;
            }
            drawLineChart(canvas, [{ label: '', points: points, is_total: true }], unit);
        });

        scope.querySelectorAll('.vs-edgeone-multi-chart-data').forEach(function (node) {
            var targetId = node.getAttribute('data-target');
            var unit = node.getAttribute('data-unit') || 'number';
            var canvas = document.getElementById(targetId);
            if (!canvas) return;
            var series;
            try {
                series = JSON.parse(node.textContent || '[]');
            } catch (e) {
                return;
            }
            drawLineChart(canvas, series, unit);
        });
    }

    function renderChartLegend(wrap, series) {
        var legend = wrap.querySelector('.vs-edgeone-chart-legend--overlay');
        if (!legend) {
            legend = document.createElement('div');
            legend.className = 'vs-edgeone-chart-legend vs-edgeone-chart-legend--overlay';
            wrap.insertBefore(legend, wrap.firstChild);
        }
        legend.innerHTML = '';
        if (!series || series.length <= 1) {
            legend.style.display = 'none';
            return;
        }
        legend.style.display = 'flex';
        var colorIdx = 0;
        series.forEach(function (s) {
            var color = s.is_total ? TOTAL_COLOR : COLORS[colorIdx++ % COLORS.length];
            var item = document.createElement('span');
            item.className = 'vs-edgeone-chart-legend__item' + (s.is_total ? ' is-total' : '');
            var dot = document.createElement('i');
            dot.className = 'vs-edgeone-chart-legend__dot';
            dot.style.backgroundColor = color;
            item.appendChild(dot);
            item.appendChild(document.createTextNode(s.label || ''));
            legend.appendChild(item);
        });
    }

    function seriesColor(s, colorIdx) {
        if (s.is_total) return TOTAL_COLOR;
        return COLORS[colorIdx % COLORS.length];
    }

    function bindChartInteraction(canvas, wrap) {
        if (canvas.dataset.eoChartBound === '1') return;
        canvas.dataset.eoChartBound = '1';

        var tip = wrap.querySelector('.vs-edgeone-chart-tooltip');
        if (!tip) {
            tip = document.createElement('div');
            tip.className = 'vs-edgeone-chart-tooltip';
            tip.style.display = 'none';
            wrap.appendChild(tip);
        }

        function hideTip() {
            tip.style.display = 'none';
            var st = canvas._eoChartState;
            if (st) drawLineChart(canvas, st.series, st.unit, -1);
        }

        function showAt(clientX) {
            var st = canvas._eoChartState;
            if (!st || !st.tsList || st.tsList.length === 0) return;

            var rect = canvas.getBoundingClientRect();
            var x = clientX - rect.left;
            if (x < st.padL || x > st.width - st.padR) {
                hideTip();
                return;
            }

            var ratio = (x - st.padL) / st.plotW;
            var idx = Math.round(ratio * (st.tsList.length - 1));
            idx = Math.max(0, Math.min(st.tsList.length - 1, idx));

            drawLineChart(canvas, st.series, st.unit, idx);

            var ts = st.tsList[idx];
            var timeStr = formatTs(new Date(ts * 1000));
            var html = '<div class="vs-edgeone-chart-tooltip__time">' + timeStr + '</div>';
            var colorIdx = 0;
            st.series.forEach(function (s) {
                var points = s.points || [];
                var val = null;
                for (var i = 0; i < points.length; i++) {
                    if (Number(points[i].ts) === ts) {
                        val = Number(points[i].value) || 0;
                        break;
                    }
                }
                if (val === null) return;
                var color = seriesColor(s, colorIdx++);
                var label = s.label || '数值';
                html += '<div class="vs-edgeone-chart-tooltip__row">';
                html += '<i class="vs-edgeone-chart-tooltip__dot" style="background:' + color + '"></i>';
                html += '<span>' + label + '：' + formatChartValue(val, st.unit) + '</span></div>';
            });
            tip.innerHTML = html;
            tip.style.display = 'block';

            var tipX = x + 12;
            if (tipX + 180 > st.width) {
                tipX = x - 192;
            }
            tip.style.left = Math.max(4, tipX) + 'px';
            tip.style.top = '24px';
        }

        canvas.addEventListener('mousemove', function (e) {
            showAt(e.clientX);
        });
        canvas.addEventListener('mouseleave', hideTip);
        canvas.addEventListener('touchstart', function (e) {
            if (e.touches[0]) showAt(e.touches[0].clientX);
        }, { passive: true });
        canvas.addEventListener('touchmove', function (e) {
            if (e.touches[0]) showAt(e.touches[0].clientX);
        }, { passive: true });
        canvas.addEventListener('touchend', hideTip);
    }

    function drawLineChart(canvas, series, unit, highlightIdx) {
        if (!series || series.length === 0) return;
        if (typeof highlightIdx !== 'number') highlightIdx = -1;

        var wrap = canvas.parentElement;
        if (!wrap) return;
        renderChartLegend(wrap, series);

        var width = wrap.clientWidth || 900;
        if (width < 280) width = 280;
        var height = 260;
        var padL = 56;
        var padR = 16;
        var padT = series.length > 1 ? 36 : 20;
        var padB = 36;
        var dpr = window.devicePixelRatio || 1;

        canvas.width = Math.floor(width * dpr);
        canvas.height = Math.floor(height * dpr);
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';

        var ctx = canvas.getContext('2d');
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.clearRect(0, 0, width, height);

        var max = 1;
        var allTs = {};
        series.forEach(function (s) {
            (s.points || []).forEach(function (p) {
                allTs[p.ts] = true;
                var v = Number(p.value) || 0;
                if (v > max) max = v;
            });
        });
        if (max <= 0) max = 1;

        var plotW = width - padL - padR;
        var plotH = height - padT - padB;

        ctx.strokeStyle = '#eef0f3';
        ctx.lineWidth = 1;
        for (var g = 0; g <= 4; g++) {
            var gy = padT + (plotH / 4) * g;
            ctx.beginPath();
            ctx.moveTo(padL, gy);
            ctx.lineTo(width - padR, gy);
            ctx.stroke();
        }

        ctx.fillStyle = '#888';
        ctx.font = '11px sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText(formatChartValue(max, unit), padL - 8, padT + 4);
        ctx.fillText('0', padL - 8, padT + plotH);

        var tsList = Object.keys(allTs).map(Number).sort(function (a, b) { return a - b; });
        if (tsList.length === 0) return;

        canvas._eoChartState = {
            series: series,
            unit: unit,
            tsList: tsList,
            max: max,
            padL: padL,
            padR: padR,
            padT: padT,
            padB: padB,
            width: width,
            height: height,
            plotW: plotW,
            plotH: plotH
        };

        bindChartInteraction(canvas, wrap);

        var colorIdx = 0;
        series.forEach(function (s) {
            var points = s.points || [];
            if (points.length === 0) return;
            var color = seriesColor(s, colorIdx++);
            ctx.strokeStyle = color;
            ctx.lineWidth = s.is_total ? 2.5 : 2;
            ctx.beginPath();
            points.forEach(function (p, i) {
                var tsIndex = tsList.indexOf(Number(p.ts));
                var x = padL + (plotW * (tsIndex >= 0 ? tsIndex : i)) / Math.max(tsList.length - 1, 1);
                var y = padT + plotH - (plotH * (Number(p.value) || 0)) / max;
                if (i === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            });
            ctx.stroke();
        });

        if (highlightIdx >= 0 && highlightIdx < tsList.length) {
            var hiTs = tsList[highlightIdx];
            var hx = padL + (plotW * highlightIdx) / Math.max(tsList.length - 1, 1);

            ctx.save();
            ctx.strokeStyle = 'rgba(22, 119, 255, 0.35)';
            ctx.lineWidth = 1;
            ctx.setLineDash([4, 3]);
            ctx.beginPath();
            ctx.moveTo(hx, padT);
            ctx.lineTo(hx, padT + plotH);
            ctx.stroke();
            ctx.setLineDash([]);
            ctx.restore();

            var dotIdx = 0;
            series.forEach(function (s) {
                var points = s.points || [];
                var val = null;
                for (var j = 0; j < points.length; j++) {
                    if (Number(points[j].ts) === hiTs) {
                        val = Number(points[j].value) || 0;
                        break;
                    }
                }
                if (val === null) return;
                var hy = padT + plotH - (plotH * val) / max;
                var dotColor = seriesColor(s, dotIdx++);
                ctx.fillStyle = dotColor;
                ctx.beginPath();
                ctx.arc(hx, hy, 4, 0, Math.PI * 2);
                ctx.fill();
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 1.5;
                ctx.stroke();
            });
        }

        var firstTs = tsList[0] ? new Date(tsList[0] * 1000) : null;
        var lastTs = tsList[tsList.length - 1] ? new Date(tsList[tsList.length - 1] * 1000) : null;
        ctx.fillStyle = '#888';
        ctx.textAlign = 'left';
        ctx.fillText(firstTs ? formatTs(firstTs) : '', padL, height - 10);
        ctx.textAlign = 'right';
        ctx.fillText(lastTs ? formatTs(lastTs) : '', width - padR, height - 10);
    }

    function formatTs(d) {
        var m = (d.getMonth() + 1).toString().padStart(2, '0');
        var day = d.getDate().toString().padStart(2, '0');
        var h = d.getHours().toString().padStart(2, '0');
        var min = d.getMinutes().toString().padStart(2, '0');
        return m + '-' + day + ' ' + h + ':' + min;
    }
})();
