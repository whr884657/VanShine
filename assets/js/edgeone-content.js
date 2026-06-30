/**
 * EdgeOne 缓存管理页：任务类型切换、刷新类型联动
 */
(function () {
    'use strict';

    var PURGE_TIPS = {
        purge_url: '每行填写完整 URL，例如 https://example.com/path/file.js',
        purge_prefix: '每行填写目录前缀，例如 https://example.com/static/',
        purge_host: '每行填写 Hostname，例如 www.example.com',
        purge_all: '将全部清除当前站点缓存，无需填写目标'
    };

    function syncPurgeType(panel) {
        var select = panel.querySelector('#edgeonePurgeType');
        var targetsRow = panel.querySelector('#edgeonePurgeTargetsRow');
        var targets = panel.querySelector('#edgeonePurgeTargets');
        var tip = panel.querySelector('#edgeonePurgeTypeTip');
        if (!select) return;

        var type = select.value || 'purge_url';
        var isAll = type === 'purge_all';

        if (targetsRow) {
            targetsRow.hidden = isAll;
        }
        if (targets) {
            targets.required = !isAll;
            if (isAll) {
                targets.value = '';
            }
        }
        if (tip) {
            tip.textContent = PURGE_TIPS[type] || PURGE_TIPS.purge_url;
            tip.setAttribute('data-for', type);
        }
    }

    function setCacheMode(panel, mode) {
        panel.querySelectorAll('.vs-edgeone-cache-mode__btn').forEach(function (btn) {
            var active = btn.getAttribute('data-cache-mode') === mode;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panel.querySelectorAll('.vs-edgeone-cache-form').forEach(function (form) {
            var formMode = form.getAttribute('data-cache-form');
            var show = formMode === mode;
            form.hidden = !show;
            form.classList.toggle('is-active', show);
        });
    }

    function bindContentPage(root) {
        var scope = root || document;
        var panel = scope.querySelector('#edgeoneCachePanel');
        if (!panel || panel.dataset.bound === '1') return;
        panel.dataset.bound = '1';

        panel.querySelectorAll('.vs-edgeone-cache-mode__btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var mode = btn.getAttribute('data-cache-mode');
                if (!mode) return;
                setCacheMode(panel, mode);
            });
        });

        var purgeType = panel.querySelector('#edgeonePurgeType');
        if (purgeType) {
            purgeType.addEventListener('change', function () {
                syncPurgeType(panel);
            });
            syncPurgeType(panel);
        }
    }

    window.VS_EDGEONE_CONTENT_BIND = bindContentPage;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            bindContentPage(document);
        });
    } else {
        bindContentPage(document);
    }
})();
