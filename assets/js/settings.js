/**
 * 文件：assets/js/settings.js
 * 作用：系统设置页 AJAX 保存与折叠板块
 * @version 1.0.42
 */

(function () {
    'use strict';

    var flashEl = document.getElementById('settingsFlash');

    function showFlash(text, type) {
        if (window.VsToast) {
            VsToast.show(text, type === 'error' ? 'error' : (type === 'info' ? 'info' : 'success'));
            return;
        }
        if (!flashEl) return;
        flashEl.textContent = text;
        flashEl.className = 'vs-settings-flash vs-alert vs-alert--' + type;
        flashEl.hidden = false;
        flashEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    function parseResponse(res) {
        return res.text().then(function (text) {
            var data = window.VS && VS.parseJsonResponse ? VS.parseJsonResponse(text) : null;
            if (!data) {
                throw new Error('invalid_json');
            }
            return data;
        });
    }

    function postForm(form) {
        var body = new FormData(form);
        var submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        return fetch(window.location.href, {
            method: 'POST',
            body: body,
            credentials: 'same-origin'
        })
            .then(parseResponse)
            .finally(function () {
                if (submitBtn) submitBtn.disabled = false;
            });
    }

    function bindAjaxForm(form) {
        if (!form || form.getAttribute('data-ajax') !== '1') return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            postForm(form)
                .then(function (data) {
                    if (data.code === 1) {
                        showFlash(data.msg || '操作成功', 'success');
                        if (data.domains) {
                            rebuildDomainList(data.domains);
                        }
                        if (data.clear_edit) {
                            var df = document.getElementById('domainForm');
                            if (df) {
                                df.reset();
                                var actionInput = df.querySelector('input[name="action"]');
                                if (actionInput) actionInput.value = 'add_domain';
                                var domainId = df.querySelector('input[name="domain_id"]');
                                if (domainId) domainId.remove();
                                var cancelBtn = df.querySelector('.vs-domain-cancel');
                                if (cancelBtn) cancelBtn.remove();
                                var subtitle = df.querySelector('.vs-form-subtitle');
                                if (subtitle) subtitle.textContent = '添加绑定域名';
                            }
                        }
                    } else {
                        showFlash(data.msg || '操作失败', 'error');
                    }
                })
                .catch(function () {
                    showFlash('网络异常，请稍后重试', 'error');
                });
        });
    }

    function rebuildDomainList(domains) {
        var wrap = document.getElementById('domainsListWrap');
        if (!wrap) return;

        if (!domains || domains.length === 0) {
            wrap.innerHTML = '<p class="vs-form-tip" id="domainsEmptyTip">暂无绑定子域名，可在下方添加</p>';
            return;
        }

        var html = '<div class="vs-domain-list" id="domainsList">';

        domains.forEach(function (row) {
            var icp = row.icp_number ? String(row.icp_number).trim() : '';
            var gongan = row.gongan_number ? String(row.gongan_number).trim() : '';

            html += '<article class="vs-domain-card" data-domain-id="' + row.id + '">';
            html += '<div class="vs-domain-card__grid">';
            html += domainCell('域名', row.domain);
            html += domainCell('站点名称', row.site_name);
            html += domainCell('ICP 备案号', icp || '未设置');
            html += domainCell('公安备案号', gongan || '未设置');
            html += '</div>';
            html += '<div class="vs-domain-card__actions">';
            html += '<a href="?edit_domain=' + row.id + '" class="vs-btn vs-btn--pill vs-btn--pill-primary">编辑</a>';
            html += '<form method="post" class="vs-domain-delete-form" data-ajax="1">';
            html += '<input type="hidden" name="action" value="delete_domain">';
            html += '<input type="hidden" name="domain_id" value="' + row.id + '">';
            html += '<button type="submit" class="vs-btn vs-btn--pill vs-btn--pill-danger">删除</button>';
            html += '</form></div></article>';
        });

        html += '</div>';
        wrap.innerHTML = html;

        document.querySelectorAll('.vs-domain-delete-form').forEach(function (f) {
            bindDeleteForm(f);
        });
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function domainCell(label, value) {
        return '<div class="vs-domain-card__cell"><span class="vs-domain-card__label">'
            + escapeHtml(label) + '</span><span class="vs-domain-card__value">'
            + escapeHtml(value) + '</span></div>';
    }

    function bindDeleteForm(form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var doDelete = function () {
                postForm(form)
                    .then(function (data) {
                        if (data.code === 1) {
                            showFlash(data.msg || '已删除', 'success');
                            if (data.domains) rebuildDomainList(data.domains);
                        } else {
                            showFlash(data.msg || '删除失败', 'error');
                        }
                    })
                    .catch(function () {
                        showFlash('网络异常，请稍后重试', 'error');
                    });
            };

            if (window.VsModal) {
                VsModal.confirm('确定删除该绑定域名吗？', '删除确认', { danger: true }).then(function (ok) {
                    if (ok) doDelete();
                });
            } else if (confirm('确定删除该绑定域名吗？')) {
                doDelete();
            }
        });
    }

    function bindAccordions() {
        document.querySelectorAll('[data-accordion]').forEach(function (section) {
            var trigger = section.querySelector('.vs-accordion__trigger');
            if (!trigger) return;

            trigger.addEventListener('click', function () {
                var isOpen = section.classList.contains('is-open');
                section.classList.toggle('is-open', !isOpen);
                trigger.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
            });
        });
    }

    function bindStorageTestButtons() {
        document.querySelectorAll('.vs-storage-test-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = document.getElementById('storageForm');
                if (!form) return;

                var body = new FormData(form);
                body.set('action', 'test_storage');
                body.set('storage_key', btn.getAttribute('data-storage-key') || '');

                btn.disabled = true;
                fetch(window.location.href, {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin'
                })
                    .then(parseResponse)
                    .then(function (data) {
                        showFlash(data.msg || (data.code === 1 ? '测试成功' : '测试失败'), data.code === 1 ? 'success' : 'error');
                    })
                    .catch(function () {
                        showFlash('网络异常，请稍后重试', 'error');
                    })
                    .finally(function () {
                        btn.disabled = false;
                    });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindAccordions();

        ['siteForm', 'domainForm', 'mailForm', 'testMailForm', 'storageForm', 'cdnForm'].forEach(function (id) {
            bindAjaxForm(document.getElementById(id));
        });

        bindStorageTestButtons();

        var cdnTestBtn = document.getElementById('cdnEdgeOneTestBtn');
        if (cdnTestBtn) {
            cdnTestBtn.addEventListener('click', function () {
                var form = document.getElementById('cdnForm');
                if (!form) return;
                var body = new FormData(form);
                body.set('action', 'test_edgeone');
                cdnTestBtn.disabled = true;
                fetch(window.location.href, {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin'
                })
                    .then(parseResponse)
                    .then(function (data) {
                        showFlash(data.msg || (data.code === 1 ? '测试成功' : '测试失败'), data.code === 1 ? 'success' : 'error');
                    })
                    .catch(function () {
                        showFlash('网络异常，请稍后重试', 'error');
                    })
                    .finally(function () {
                        cdnTestBtn.disabled = false;
                    });
            });
        }
        document.querySelectorAll('.vs-domain-delete-form').forEach(bindDeleteForm);
    });
})();
