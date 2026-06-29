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
                bindZonesPage(main);
                bindDomainsPage(main);
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
            bindZonesPage(main);
            bindDomainsPage(main);
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
        bindFluxPanel(scope);
    }

    function loadZonesOverview(rangeKey, chartTab) {
        var host = document.getElementById('edgeoneZonesOverviewHost');
        if (!host) return Promise.resolve();

        host.classList.add('is-loading');
        var body = new FormData();
        body.set('action', 'zones_overview_data');
        body.set('range', rangeKey || 'today');
        body.set('chart_tab', chartTab || 'flux');

        return postFormData(body)
            .then(function (data) {
                if (data.code !== 1 || !data.data || !data.data.overview_html) {
                    host.innerHTML = '<p class="vs-form-tip">加载失败</p>';
                    return;
                }
                host.innerHTML = data.data.overview_html;
                host.classList.remove('is-loading');
                bindZonesOverviewControls(host, rangeKey || 'today', chartTab || 'flux');
                bindCharts(host);
            })
            .catch(function () {
                host.classList.remove('is-loading');
                host.innerHTML = '<p class="vs-form-tip">加载失败，请稍后重试</p>';
            });
    }

    function bindZonesOverviewControls(scope, currentRange, currentTab) {
        var panel = scope.querySelector('#edgeoneZonesOverview') || scope;
        var rangeSelect = panel.querySelector('#edgeoneZonesRange');
        if (rangeSelect && rangeSelect.dataset.bound !== '1') {
            rangeSelect.dataset.bound = '1';
            if (currentRange) {
                rangeSelect.value = currentRange;
            }
            rangeSelect.addEventListener('change', function () {
                var activeBtn = panel.querySelector('.vs-edgeone-zones-chart-tabs__btn.is-active');
                var tab = activeBtn ? activeBtn.getAttribute('data-zones-chart') : 'flux';
                loadZonesOverview(rangeSelect.value, tab);
            });
        }

        panel.querySelectorAll('.vs-edgeone-zones-chart-tabs__btn').forEach(function (btn) {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                var tab = btn.getAttribute('data-zones-chart') || 'flux';
                var range = rangeSelect ? rangeSelect.value : currentRange;
                loadZonesOverview(range, tab);
            });
        });

        var collapseBtn = panel.querySelector('[data-zones-overview-collapse]');
        if (collapseBtn && collapseBtn.dataset.bound !== '1') {
            collapseBtn.dataset.bound = '1';
            collapseBtn.addEventListener('click', function () {
                panel.classList.toggle('is-collapsed');
                collapseBtn.textContent = panel.classList.contains('is-collapsed') ? '展开数据概览' : '收起数据概览';
            });
        }
    }

    function openZoneCreateDrawer() {
        var drawer = document.getElementById('edgeoneZoneCreateDrawer');
        if (!drawer) return;
        drawer.hidden = false;
        drawer.setAttribute('aria-hidden', 'false');
        drawer.classList.add('is-open');
    }

    function closeZoneCreateDrawer() {
        var drawer = document.getElementById('edgeoneZoneCreateDrawer');
        if (!drawer) return;
        drawer.hidden = true;
        drawer.setAttribute('aria-hidden', 'true');
        drawer.classList.remove('is-open');
    }

    function navigateWithZone(zoneId, gotoPath) {
        if (!zoneId || !gotoPath) return;
        var body = new FormData();
        body.set('action', 'set_zone');
        body.set('zone_id', zoneId);
        postFormData(body)
            .then(function (data) {
                if (data.code !== 1) {
                    toast(data.msg || '切换站点失败', 'error');
                    return;
                }
                var base = pagePath().replace(/\/[^/]+$/, '');
                window.location.href = base + '/' + gotoPath + '?zone_id=' + encodeURIComponent(zoneId);
            })
            .catch(function () {
                toast('网络异常', 'error');
            });
    }

    function bindZonesPage(root) {
        var scope = root || document;
        var page = scope.querySelector('#edgeoneZonesPage') || (scope.id === 'edgeoneZonesPage' ? scope : null);
        if (!page) return;

        if (scope === document || scope.id === 'edgeoneMainContent') {
            var defaultRange = page.getAttribute('data-default-range') || 'today';
            loadZonesOverview(defaultRange, 'flux');
        }

        if (page.dataset.zonesBound === '1') return;
        page.dataset.zonesBound = '1';

        var createBtn = page.querySelector('#edgeoneZoneCreateBtn');
        if (createBtn) {
            createBtn.addEventListener('click', function () {
                openZoneCreateDrawer();
            });
        }

        page.querySelectorAll('[data-zone-create-close]').forEach(function (node) {
            node.addEventListener('click', function () {
                closeZoneCreateDrawer();
            });
        });

        var searchInput = page.querySelector('#edgeoneZonesSearch');
        var countNode = page.querySelector('#edgeoneZonesCount');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = searchInput.value.trim().toLowerCase();
                var visible = 0;
                page.querySelectorAll('[data-zone-search]').forEach(function (node) {
                    var text = node.getAttribute('data-zone-search') || '';
                    var show = !q || text.indexOf(q) !== -1;
                    node.hidden = !show;
                    if (show) visible++;
                });
                if (countNode) {
                    countNode.textContent = '共 ' + visible + ' 条';
                }
            });
        }

        page.querySelectorAll('[data-set-zone]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var zid = link.getAttribute('data-set-zone');
                var goto = link.getAttribute('data-goto');
                if (goto) {
                    navigateWithZone(zid, goto);
                }
            });
        });
    }

    function openDomainDrawer(id) {
        var drawer = document.getElementById(id);
        if (!drawer) return;
        drawer.hidden = false;
        drawer.setAttribute('aria-hidden', 'false');
        drawer.classList.add('is-open');
    }

    function closeDomainDrawers() {
        document.querySelectorAll('#edgeoneDomainCreateDrawer, #edgeoneDomainEditDrawer, #edgeoneDomainCertDrawer').forEach(function (drawer) {
            drawer.hidden = true;
            drawer.setAttribute('aria-hidden', 'true');
            drawer.classList.remove('is-open');
        });
    }

    function copyTextToClipboard(text) {
        if (!text) return Promise.reject(new Error('empty'));
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                resolve();
            } catch (err) {
                reject(err);
            }
            document.body.removeChild(ta);
        });
    }

    function certModeLabel(mode) {
        var map = {
            disable: '未部署',
            eofreecert: 'EdgeOne 免费证书',
            eofreecert_manual: 'EdgeOne 免费证书（手动验证）',
            sslcert: 'SSL 托管证书',
            off: '未部署'
        };
        return map[String(mode || '').toLowerCase()] || (mode || '未配置');
    }

    function renderCertVerifyBox(data) {
        var box = document.getElementById('edgeoneDomainCertVerify');
        var checkBtn = document.getElementById('edgeoneDomainCertCheckBtn');
        if (!box) return;
        box.hidden = false;
        var html = '';
        if (data && data.FileVerification) {
            var file = data.FileVerification;
            html += '<p><strong>HTTP 文件验证</strong></p>';
            html += '<p>路径：<code>' + String(file.Path || '') + '</code></p>';
            html += '<p>文件内容：<code>' + String(file.Content || '') + '</code></p>';
            html += '<p class="vs-form-tip">请将上述文件部署到源站对应路径，完成后点击「验证并部署」。</p>';
        } else if (data && data.DnsVerification) {
            var dns = data.DnsVerification;
            html += '<p><strong>DNS 委派验证</strong></p>';
            html += '<p>记录类型：' + String(dns.RecordType || 'CNAME') + '</p>';
            html += '<p>主机记录：<code>' + String(dns.RecordValue || '') + '</code></p>';
            html += '<p>记录值：<code>' + String(dns.Subdomain || '') + '</code></p>';
            html += '<p class="vs-form-tip">请在 DNS 服务商添加上述记录，完成后点击「验证并部署」。</p>';
        } else {
            html += '<p class="vs-form-tip">请按 EdgeOne 控制台要求完成验证后，点击「验证并部署」。</p>';
        }
        box.innerHTML = html;
        if (checkBtn) checkBtn.hidden = false;
    }

    function openCertDrawer(domainName, certMode) {
        var drawer = document.getElementById('edgeoneDomainCertDrawer');
        if (!drawer) return;
        drawer.dataset.domain = domainName || '';
        var title = document.getElementById('edgeoneDomainCertDomain');
        if (title) title.textContent = domainName;
        var status = document.getElementById('edgeoneDomainCertStatus');
        if (status) {
            var row = findDomainRow(domainName);
            var label = row ? certModeLabel((row.Certificate && row.Certificate.Mode) || certMode) : certModeLabel(certMode);
            status.innerHTML = '<p>当前配置：<strong>' + label + '</strong></p>';
        }
        var verify = document.getElementById('edgeoneDomainCertVerify');
        if (verify) {
            verify.hidden = true;
            verify.innerHTML = '';
        }
        var checkBtn = document.getElementById('edgeoneDomainCertCheckBtn');
        if (checkBtn) checkBtn.hidden = true;
        openDomainDrawer('edgeoneDomainCertDrawer');
    }

    function getDomainRowsMeta() {
        var node = document.getElementById('edgeoneDomainRowsMeta');
        if (!node) return [];
        try {
            var rows = JSON.parse(node.textContent || '[]');
            return Array.isArray(rows) ? rows : [];
        } catch (e) {
            return [];
        }
    }

    function findDomainRow(name) {
        var rows = getDomainRowsMeta();
        for (var i = 0; i < rows.length; i++) {
            if (rows[i] && rows[i].DomainName === name) return rows[i];
        }
        return null;
    }

    function bindDomainsPage(root) {
        var scope = root || document;
        var page = scope.querySelector('#edgeoneDomainsPage') || (scope.id === 'edgeoneDomainsPage' ? scope : null);
        if (!page) return;

        if (page.dataset.domainsBound === '1') return;
        page.dataset.domainsBound = '1';

        var addBtn = page.querySelector('#edgeoneDomainAddBtn');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                openDomainDrawer('edgeoneDomainCreateDrawer');
            });
        }

        page.querySelectorAll('[data-domain-drawer-close]').forEach(function (node) {
            node.addEventListener('click', function () {
                closeDomainDrawers();
            });
        });

        var searchInput = page.querySelector('#edgeoneDomainsSearch');
        var countNode = page.querySelector('#edgeoneDomainsCount');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = searchInput.value.trim().toLowerCase();
                var visible = 0;
                page.querySelectorAll('[data-domain-search]').forEach(function (node) {
                    var text = node.getAttribute('data-domain-search') || '';
                    var show = !q || text.indexOf(q) !== -1;
                    node.hidden = !show;
                    if (show) visible++;
                });
                if (countNode) {
                    countNode.textContent = '共 ' + visible + ' 条';
                }
            });
        }

        page.addEventListener('click', function (e) {
            var editLink = e.target.closest('.vs-edgeone-domain-edit');
            if (editLink) {
                e.preventDefault();
                var name = editLink.getAttribute('data-domain') || '';
                var row = findDomainRow(name);
                if (!row) return;
                var form = document.getElementById('edgeoneDomainEditForm');
                if (!form) return;
                document.getElementById('edgeoneDomainEditName').value = name;
                document.getElementById('edgeoneDomainEditTitle').textContent = name;
                var ipv6Wrap = document.getElementById('edgeoneDomainEditIpv6Wrap');
                if (ipv6Wrap) {
                    var ipv6Val = (row.IPv6Status || 'follow').toLowerCase();
                    ipv6Wrap.querySelectorAll('input[name="ipv6_status"]').forEach(function (radio) {
                        radio.checked = radio.value === ipv6Val;
                    });
                }
                var origin = row.OriginDetail && row.OriginDetail.Origin ? row.OriginDetail.Origin : '';
                var originInput = document.getElementById('edgeoneDomainEditOrigin');
                if (originInput) originInput.value = origin;
                var protocol = document.getElementById('edgeoneDomainEditProtocol');
                if (protocol) protocol.value = row.OriginProtocol || 'FOLLOW';
                var httpPort = document.getElementById('edgeoneDomainEditHttpPort');
                if (httpPort) httpPort.value = row.HttpOriginPort || 80;
                var httpsPort = document.getElementById('edgeoneDomainEditHttpsPort');
                if (httpsPort) httpsPort.value = row.HttpsOriginPort || 443;
                openDomainDrawer('edgeoneDomainEditDrawer');
                return;
            }

            var toggleLink = e.target.closest('.vs-edgeone-domain-status-toggle');
            if (toggleLink) {
                e.preventDefault();
                var domainName = toggleLink.getAttribute('data-domain') || '';
                var status = toggleLink.getAttribute('data-status') || '';
                if (!domainName || !status) return;
                var body = new FormData();
                body.set('action', 'domain_status');
                body.set('domain_name', domainName);
                body.set('status', status);
                postFormData(body).then(function (data) {
                    if (data.code === 1) {
                        toast(data.msg || '操作成功', 'success');
                        window.location.reload();
                    } else {
                        toast(data.msg || '操作失败', 'error');
                    }
                }).catch(function () {
                    toast('网络异常', 'error');
                });
                return;
            }

            var copyBtn = e.target.closest('.vs-edgeone-domain-cname-copy');
            if (copyBtn) {
                e.preventDefault();
                var cname = copyBtn.getAttribute('data-copy') || '';
                copyTextToClipboard(cname).then(function () {
                    toast('CNAME 已复制', 'success');
                }).catch(function () {
                    toast('复制失败，请手动选择复制', 'error');
                });
                return;
            }

            var certBtn = e.target.closest('.vs-edgeone-domain-cert-config');
            if (certBtn) {
                e.preventDefault();
                var certDomain = certBtn.getAttribute('data-domain') || '';
                var certMode = certBtn.getAttribute('data-cert-mode') || '';
                openCertDrawer(certDomain, certMode);
                return;
            }

            var deleteLink = e.target.closest('.vs-edgeone-domain-delete');
            if (deleteLink) {
                e.preventDefault();
                var delName = deleteLink.getAttribute('data-domain') || '';
                if (!delName || !window.confirm('确定删除域名 ' + delName + ' ？')) return;
                var delBody = new FormData();
                delBody.set('action', 'domain_delete');
                delBody.set('domain_name', delName);
                postFormData(delBody).then(function (data) {
                    if (data.code === 1) {
                        toast(data.msg || '已删除', 'success');
                        window.location.reload();
                    } else {
                        toast(data.msg || '删除失败', 'error');
                    }
                }).catch(function () {
                    toast('网络异常', 'error');
                });
            }
        });

        var certDrawer = page.querySelector('#edgeoneDomainCertDrawer');
        if (certDrawer) {
            certDrawer.querySelectorAll('.vs-edgeone-cert-apply').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var domainName = certDrawer.dataset.domain || '';
                    var method = btn.getAttribute('data-method') || 'http_challenge';
                    if (!domainName) return;
                    var body = new FormData();
                    body.set('action', 'domain_cert_apply');
                    body.set('domain_name', domainName);
                    body.set('verification_method', method);
                    postFormData(body).then(function (data) {
                        if (data.code === 1) {
                            toast(data.msg || '申请已发起', 'success');
                            renderCertVerifyBox(data.data || {});
                        } else {
                            toast(data.msg || '申请失败', 'error');
                        }
                    }).catch(function () {
                        toast('网络异常', 'error');
                    });
                });
            });

            certDrawer.querySelectorAll('.vs-edgeone-cert-deploy').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var domainName = certDrawer.dataset.domain || '';
                    var mode = btn.getAttribute('data-mode') || '';
                    if (!domainName || !mode) return;
                    if (mode === 'disable' && !window.confirm('确定关闭该域名的 HTTPS ？')) return;
                    var body = new FormData();
                    body.set('action', 'domain_cert_deploy');
                    body.set('domain_name', domainName);
                    body.set('mode', mode);
                    postFormData(body).then(function (data) {
                        if (data.code === 1) {
                            toast(data.msg || '配置成功', 'success');
                            window.location.reload();
                        } else {
                            toast(data.msg || '配置失败', 'error');
                        }
                    }).catch(function () {
                        toast('网络异常', 'error');
                    });
                });
            });

            var checkBtn = certDrawer.querySelector('#edgeoneDomainCertCheckBtn');
            if (checkBtn) {
                checkBtn.addEventListener('click', function () {
                    var domainName = certDrawer.dataset.domain || '';
                    if (!domainName) return;
                    var body = new FormData();
                    body.set('action', 'domain_cert_check');
                    body.set('domain_name', domainName);
                    postFormData(body).then(function (data) {
                        if (data.code === 1) {
                            toast(data.msg || '验证并部署成功', 'success');
                            window.location.reload();
                        } else {
                            toast(data.msg || '验证失败', 'error');
                        }
                    }).catch(function () {
                        toast('网络异常', 'error');
                    });
                });
            }
        }
    }

    function openFilterDrawer(drawer) {
        if (!drawer) return;
        drawer.hidden = false;
        drawer.setAttribute('aria-hidden', 'false');
        drawer.classList.add('is-open');
    }

    function closeFilterDrawer(drawer) {
        if (!drawer) return;
        drawer.hidden = true;
        drawer.setAttribute('aria-hidden', 'true');
        drawer.classList.remove('is-open');
        drawer.querySelectorAll('.vs-edgeone-filter-picker-menu').forEach(function (m) {
            m.hidden = true;
        });
    }

    function bindCustomFilters(form) {
        if (!form || form.dataset.customBound === '1') return;
        form.dataset.customBound = '1';

        var wrap = form.querySelector('#edgeoneCustomFilters');
        if (!wrap) return;

        var drawer = document.getElementById('edgeoneFilterDrawer');
        var jsonInput = wrap.querySelector('#edgeoneCustomFiltersJson');
        var addBtn = wrap.querySelector('#edgeoneAddFilterBtn');
        var chipsHost = wrap.querySelector('#edgeoneCustomFilterChips');
        var keyInput = document.getElementById('edgeoneCustomFilterKey');
        var opInput = document.getElementById('edgeoneCustomFilterOp');
        var keyLabel = document.getElementById('edgeoneFilterKeyLabel');
        var opLabel = document.getElementById('edgeoneFilterOpLabel');
        var keyPicker = document.getElementById('edgeoneFilterKeyPicker');
        var opPicker = document.getElementById('edgeoneFilterOpPicker');
        var keyMenu = document.getElementById('edgeoneFilterKeyMenu');
        var opMenu = document.getElementById('edgeoneFilterOpMenu');
        var valueWrap = document.getElementById('edgeoneCustomFilterValueWrap');
        var confirmBtn = document.getElementById('edgeoneCustomFilterConfirm');
        var defs = {};
        var ops = {};
        var filters = [];

        try {
            defs = JSON.parse(wrap.getAttribute('data-defs') || '{}');
            ops = JSON.parse(wrap.getAttribute('data-ops') || '{}');
        } catch (e) {
            defs = {};
            ops = {};
        }

        function loadFilters() {
            try {
                filters = JSON.parse(jsonInput.value || '[]');
                if (!Array.isArray(filters)) filters = [];
            } catch (e) {
                filters = [];
            }
        }

        function syncHidden() {
            if (!jsonInput) return;
            jsonInput.value = JSON.stringify(filters);
        }

        function escapeHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function renderChips() {
            if (!chipsHost) return;
            chipsHost.innerHTML = '';
            filters.forEach(function (row, idx) {
                var label = (defs[row.key] && defs[row.key].label) ? defs[row.key].label : row.key;
                var opText = ops[row.operator] || row.operator;
                var valText = (row.values || []).join(', ');
                var chip = document.createElement('span');
                chip.className = 'vs-edgeone-filter-chip';
                chip.innerHTML = '<span class="vs-edgeone-filter-chip__text">' + escapeHtml(label) + ' · ' + escapeHtml(opText) + ' · ' + escapeHtml(valText) + '</span><button type="button" class="vs-edgeone-filter-chip__remove" data-idx="' + idx + '" aria-label="删除">×</button>';
                chipsHost.appendChild(chip);
            });
        }

        function closeMenus() {
            if (keyMenu) keyMenu.hidden = true;
            if (opMenu) opMenu.hidden = true;
        }

        function updateOpMenu() {
            if (!opMenu || !keyInput) return;
            var key = keyInput.value;
            var def = defs[key];
            var allowed = def && def.operators ? def.operators : Object.keys(ops);
            opMenu.innerHTML = '';
            allowed.forEach(function (opKey) {
                if (!ops[opKey]) return;
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'vs-edgeone-filter-picker-menu__item';
                btn.setAttribute('data-value', opKey);
                btn.textContent = ops[opKey];
                opMenu.appendChild(btn);
            });
            if (allowed.indexOf(opInput.value) === -1 && allowed.length) {
                opInput.value = allowed[0];
                if (opLabel) opLabel.textContent = ops[allowed[0]] || allowed[0];
            }
        }

        function rebuildValueField() {
            if (!valueWrap || !keyInput) return;
            var key = keyInput.value;
            var def = defs[key];
            valueWrap.innerHTML = '';
            if (!def) {
                valueWrap.innerHTML = '<p class="vs-form-tip">请先选择筛选项</p>';
                return;
            }
            if (def.value_type === 'enum' && def.options) {
                var grid = document.createElement('div');
                grid.className = 'vs-edgeone-filter-value-grid';
                Object.keys(def.options).forEach(function (val) {
                    var lab = document.createElement('label');
                    lab.className = 'vs-edgeone-filter-check';
                    lab.innerHTML = '<input type="checkbox" value="' + escapeHtml(val) + '"><span>' + escapeHtml(def.options[val]) + '</span>';
                    grid.appendChild(lab);
                });
                valueWrap.appendChild(grid);
            } else {
                var ta = document.createElement('textarea');
                ta.className = 'vs-input vs-edgeone-filter-value-text';
                ta.id = 'edgeoneCustomFilterValue';
                ta.placeholder = def.value_type === 'multitext' ? '多个值用换行或逗号分隔' : '输入筛选值，多个用换行或逗号分隔';
                valueWrap.appendChild(ta);
            }
        }

        function getValuesFromDrawer() {
            if (!valueWrap) return [];
            var grid = valueWrap.querySelector('.vs-edgeone-filter-value-grid');
            if (grid) {
                return Array.prototype.slice.call(grid.querySelectorAll('input:checked')).map(function (el) {
                    return el.value.trim();
                }).filter(Boolean);
            }
            var ta = valueWrap.querySelector('#edgeoneCustomFilterValue');
            if (!ta) return [];
            return ta.value.split(/[\r\n,;|]+/).map(function (v) {
                return v.trim();
            }).filter(Boolean);
        }

        function resetDrawer() {
            if (keyInput) keyInput.value = '';
            if (keyLabel) keyLabel.textContent = '选择筛选项';
            if (opInput) opInput.value = 'equals';
            if (opLabel) opLabel.textContent = ops.equals || '等于';
            updateOpMenu();
            rebuildValueField();
        }

        if (addBtn && drawer) {
            addBtn.addEventListener('click', function () {
                resetDrawer();
                openFilterDrawer(drawer);
            });
        }

        if (drawer) {
            drawer.querySelectorAll('[data-filter-drawer-close]').forEach(function (el) {
                el.addEventListener('click', function () {
                    closeFilterDrawer(drawer);
                });
            });
        }

        if (keyPicker && keyMenu) {
            keyPicker.addEventListener('click', function (e) {
                e.stopPropagation();
                keyMenu.hidden = !keyMenu.hidden;
                if (opMenu) opMenu.hidden = true;
            });
            keyMenu.addEventListener('click', function (e) {
                var item = e.target.closest('.vs-edgeone-filter-picker-menu__item');
                if (!item) return;
                var val = item.getAttribute('data-value') || '';
                keyInput.value = val;
                keyLabel.textContent = item.textContent.trim();
                keyMenu.hidden = true;
                updateOpMenu();
                rebuildValueField();
            });
        }

        if (opPicker && opMenu) {
            opPicker.addEventListener('click', function (e) {
                e.stopPropagation();
                opMenu.hidden = !opMenu.hidden;
                if (keyMenu) keyMenu.hidden = true;
            });
            opMenu.addEventListener('click', function (e) {
                var item = e.target.closest('.vs-edgeone-filter-picker-menu__item');
                if (!item) return;
                var val = item.getAttribute('data-value') || 'equals';
                opInput.value = val;
                opLabel.textContent = item.textContent.trim();
                opMenu.hidden = true;
            });
        }

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.vs-edgeone-filter-picker') && !e.target.closest('.vs-edgeone-filter-picker-menu')) {
                closeMenus();
            }
        });

        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                if (!keyInput) return;
                var key = keyInput.value.trim();
                if (!key) return;
                var values = getValuesFromDrawer();
                if (!values.length) return;
                filters.push({
                    key: key,
                    operator: opInput ? opInput.value : 'equals',
                    values: values
                });
                syncHidden();
                renderChips();
                closeFilterDrawer(drawer);
            });
        }

        if (chipsHost) {
            chipsHost.addEventListener('click', function (e) {
                var btn = e.target.closest('.vs-edgeone-filter-chip__remove');
                if (!btn) return;
                var idx = parseInt(btn.getAttribute('data-idx'), 10);
                if (isNaN(idx)) return;
                filters.splice(idx, 1);
                syncHidden();
                renderChips();
            });
        }

        loadFilters();
        renderChips();
        form.addEventListener('submit', syncHidden);
    }

    function bindFluxPanel(root) {
        var panel = document.getElementById('edgeoneFluxPanel');
        var form = document.getElementById('edgeoneOverviewForm');
        if (!panel || !form) return;

        if (panel.dataset.fluxBound !== '1') {
            panel.dataset.fluxBound = '1';
            panel.addEventListener('click', function (e) {
                var dimBtn = e.target.closest('[data-flux-dim]');
                if (dimBtn) {
                    e.preventDefault();
                    var dim = dimBtn.getAttribute('data-flux-dim') || 'all';
                    loadFluxDimension(form, dim);
                    return;
                }
                var moreBtn = e.target.closest('.vs-edgeone-flux-tabs__btn--more');
                if (moreBtn) {
                    var drop = panel.querySelector('.vs-edgeone-flux-tabs__dropdown');
                    if (drop) drop.classList.toggle('is-open');
                }
                if (e.target.matches('[data-legend-show-all]')) {
                    toggleFluxLegend(panel, true);
                }
                if (e.target.matches('[data-legend-hide-all]')) {
                    toggleFluxLegend(panel, false);
                }
                var legItem = e.target.closest('.vs-edgeone-flux-legend__item');
                if (legItem && !e.target.matches('[data-legend-show-all],[data-legend-hide-all]')) {
                    legItem.classList.toggle('is-visible');
                    var idx = parseInt(legItem.getAttribute('data-legend-idx'), 10);
                    toggleFluxSeries(panel, idx, legItem.classList.contains('is-visible'));
                }
            });
        }
        bindFluxLegend(panel);
    }

    function loadFluxDimension(form, dimension) {
        var inner = document.getElementById('edgeoneFluxPanelInner');
        var hidden = document.getElementById('edgeoneFluxDimension');
        if (!inner || !form) return;
        if (hidden) hidden.value = dimension;
        inner.classList.add('is-loading');
        var body = new FormData(form);
        body.set('action', 'overview_flux');
        body.set('flux_dimension', dimension);
        postFormData(body).then(function (data) {
            inner.classList.remove('is-loading');
            if (data.code !== 1 || !data.data || !data.data.flux_html) {
                toast(data.msg || '加载失败', 'error');
                return;
            }
            inner.innerHTML = data.data.flux_html;
            bindCharts(document);
            bindFluxLegend(document.getElementById('edgeoneFluxPanel'));
        }).catch(function () {
            inner.classList.remove('is-loading');
            toast('加载失败', 'error');
        });
    }

    function bindFluxLegend(panel) {
        if (!panel) return;
        var canvas = panel.querySelector('.vs-edgeone-chart--multi');
        if (!canvas || !canvas._eoChartState) return;
        var items = panel.querySelectorAll('.vs-edgeone-flux-legend__item');
        items.forEach(function (item) {
            var idx = parseInt(item.getAttribute('data-legend-idx'), 10);
            var visible = item.classList.contains('is-visible');
            if (canvas._eoChartState.series[idx]) {
                canvas._eoChartState.series[idx].hidden = !visible;
            }
        });
        drawLineChart(canvas, canvas._eoChartState.series, canvas._eoChartState.unit, -1);
    }

    function toggleFluxLegend(panel, show) {
        if (!panel) return;
        panel.querySelectorAll('.vs-edgeone-flux-legend__item').forEach(function (item) {
            item.classList.toggle('is-visible', show);
            var idx = parseInt(item.getAttribute('data-legend-idx'), 10);
            toggleFluxSeries(panel, idx, show);
        });
    }

    function toggleFluxSeries(panel, idx, visible) {
        if (!panel) return;
        var canvas = panel.querySelector('.vs-edgeone-chart--multi');
        if (!canvas || !canvas._eoChartState) return;
        if (canvas._eoChartState.series[idx]) {
            canvas._eoChartState.series[idx].hidden = !visible;
            drawLineChart(canvas, canvas._eoChartState.series, canvas._eoChartState.unit, -1);
        }
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
            bindFluxPanel(document);
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
        bindZonesPage(document);
        bindDomainsPage(document);
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
                if (s.hidden) return;
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

        var allSeries = series;
        series = series.filter(function (s) {
            return !s.hidden;
        });
        if (series.length === 0) return;

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
            series: allSeries,
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
