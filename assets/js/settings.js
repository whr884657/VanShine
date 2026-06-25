/**
 * 文件：assets/js/settings.js
 * 作用：系统设置页 AJAX 保存与折叠板块
 * @version 1.0.13
 */

(function () {
    'use strict';

    var flashEl = document.getElementById('settingsFlash');

    function showFlash(text, type) {
        if (!flashEl) return;
        flashEl.textContent = text;
        flashEl.className = 'vs-settings-flash vs-alert vs-alert--' + type;
        flashEl.hidden = false;
        flashEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
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
            .then(function (res) { return res.json(); })
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
            html += '<div class="vs-domain-card__head">';
            html += '<h4 class="vs-domain-card__domain">' + escapeHtml(row.domain) + '</h4>';
            html += '<span class="vs-domain-card__site">' + escapeHtml(row.site_name) + '</span>';
            html += '</div>';
            html += '<div class="vs-domain-card__meta">';
            html += '<div class="vs-domain-card__item"><span class="vs-domain-card__label">ICP 备案号</span>';
            html += '<span class="vs-domain-card__value">' + escapeHtml(icp || '未设置') + '</span></div>';
            html += '<div class="vs-domain-card__item"><span class="vs-domain-card__label">公安备案号</span>';
            html += '<span class="vs-domain-card__value">' + escapeHtml(gongan || '未设置') + '</span></div>';
            html += '</div>';
            html += '<div class="vs-domain-card__actions">';
            html += '<a href="?edit_domain=' + row.id + '" class="vs-btn vs-btn--default vs-btn--sm">编辑</a>';
            html += '<form method="post" class="vs-domain-delete-form" data-ajax="1">';
            html += '<input type="hidden" name="action" value="delete_domain">';
            html += '<input type="hidden" name="domain_id" value="' + row.id + '">';
            html += '<button type="submit" class="vs-btn vs-btn--text vs-btn--danger-text">删除</button>';
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

    document.addEventListener('DOMContentLoaded', function () {
        bindAccordions();

        ['siteForm', 'domainForm', 'mailForm', 'testMailForm'].forEach(function (id) {
            bindAjaxForm(document.getElementById(id));
        });

        document.querySelectorAll('.vs-domain-delete-form').forEach(bindDeleteForm);
    });
})();
