/**
 * 文件：assets/js/vs-update.js
 * 作用：系统升级共用逻辑（检测、二次确认、执行更新）
 * @version 1.0.27
 */

(function () {
    'use strict';

    var badgeActive = false;

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function apiUrl() {
        return (window.VS_BASE_URL || '') + '/admin/update.php';
    }

    function parseJsonResponse(res) {
        return res.text().then(function (text) {
            try {
                return JSON.parse(text);
            } catch (e) {
                var err = new Error('服务器返回异常，请检查 PHP 错误日志');
                err.raw = text;
                throw err;
            }
        });
    }

    function postUpdate(action, extra) {
        var body = new FormData();
        body.append('action', action);
        body.append('csrf_token', window.VS_CSRF_TOKEN || '');
        if (extra) {
            Object.keys(extra).forEach(function (key) {
                body.append(key, extra[key]);
            });
        }
        return fetch(apiUrl(), {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
        }).then(function (res) {
            return parseJsonResponse(res);
        });
    }

    function buildUpdateHtml(data) {
        var html = '<div class="vs-update-modal">';
        html += '<p class="vs-update-modal__ver">当前版本：<strong>v' + escapeHtml(data.local_version) + '</strong>';
        html += ' → 最新版本：<strong>v' + escapeHtml(data.remote_version) + '</strong></p>';

        if (data.release_date) {
            html += '<p class="vs-update-modal__date">发布日期：' + escapeHtml(data.release_date) + '</p>';
        }
        if (data.title) {
            html += '<p class="vs-update-modal__title-text">' + escapeHtml(data.title) + '</p>';
        }
        if (data.changes && data.changes.length) {
            html += '<ul class="vs-update-modal__list">';
            data.changes.forEach(function (item) {
                html += '<li>' + escapeHtml(item) + '</li>';
            });
            html += '</ul>';
        }

        if (data.has_db_changes) {
            html += '<p class="vs-update-modal__tip vs-update-modal__tip--warn">本次更新包含数据库结构变更，更新后将自动执行结构更新 SQL。</p>';
        } else {
            html += '<p class="vs-update-modal__tip">本次更新<strong>不涉及</strong>数据库表结构变更，不会执行数据库结构更新脚本。</p>';
        }
        html += '<p class="vs-update-modal__tip">绝不会替换 config/database.php，完成后自动清理临时文件。</p>';
        html += '</div>';
        return html;
    }

    function runUpdateApply() {
        if (!window.VsModal || !window.VsModal.open) {
            return Promise.reject();
        }

        VsModal.open({
            title: '正在更新…',
            html: '<p class="vs-update-modal__loading">正在从 Gitee 下载更新包并安装，请勿关闭页面…</p>',
            closeOnOverlay: false,
            closeOnEscape: false,
            buttons: [],
        });

        return postUpdate('apply').then(function (res) {
            if (res.code === 1) {
                clearSidebarBadge();
                VsModal.open({
                    title: '更新成功',
                    message: res.msg || '系统已更新到最新版本',
                    buttons: [{
                        text: '刷新页面',
                        primary: true,
                        action: function () {
                            window.location.reload();
                        },
                    }],
                    closeOnOverlay: false,
                });
            } else {
                VsModal.alert(res.msg || '更新失败');
            }
            return res;
        }).catch(function () {
            VsModal.alert('网络异常，更新失败，请稍后重试');
        });
    }

    function confirmBackupThenUpdate(data) {
        var dbTip = data.has_db_changes
            ? '本次更新将执行数据库结构更新，请务必先备份数据库。'
            : '本次更新不涉及数据库结构变更，仍建议备份数据库与网站文件以防万一。';

        var html = '<div class="vs-update-modal">';
        html += '<p class="vs-update-modal__backup-tip">' + escapeHtml(dbTip) + '</p>';
        html += '<p class="vs-update-modal__backup-tip">请确认您已完成数据备份，是否继续安装更新？</p>';
        html += '</div>';

        return new Promise(function (resolve) {
            VsModal.open({
                title: '备份确认（二次确认）',
                html: html,
                closeOnOverlay: false,
                buttons: [
                    {
                        text: '取消',
                        action: function () {
                            VsModal.close(false);
                            resolve(false);
                        },
                    },
                    {
                        text: '已备份，继续更新',
                        primary: true,
                        danger: !!data.has_db_changes,
                        action: function () {
                            VsModal.close(false);
                            resolve(true);
                        },
                    },
                ],
            });
        }).then(function (ok) {
            if (ok) {
                return runUpdateApply();
            }
        });
    }

    function showUpdateModal(data, options) {
        options = options || {};
        if (!window.VsModal || !window.VsModal.open) {
            return;
        }

        var buttons = [
            {
                text: options.cancelText || '稍后提醒',
                action: function () {
                    if (options.onDismiss) {
                        options.onDismiss(data);
                    }
                    VsModal.close(false);
                },
            },
            {
                text: options.confirmText || '立即更新',
                primary: true,
                action: function () {
                    VsModal.close(false);
                    confirmBackupThenUpdate(data);
                },
            },
        ];

        if (options.hideDismiss) {
            buttons.shift();
        }

        VsModal.open({
            title: '发现新版本 v' + data.remote_version,
            html: buildUpdateHtml(data),
            closeOnOverlay: false,
            buttons: buttons,
        });
    }

    function shouldShowSidebarBadge(data) {
        return !!(data && data.code === 1 && data.update_available && !data.show_modal);
    }

    function refreshSidebarBadgePlacement() {
        var groupBadge = document.getElementById('vsUpdateBadgeGroup');
        var itemBadge = document.getElementById('vsUpdateBadgeUpgrade');
        var systemGroup = document.querySelector('.vs-sidebar__group[data-group="system"]');

        if (!badgeActive) {
            if (groupBadge) {
                groupBadge.hidden = true;
            }
            if (itemBadge) {
                itemBadge.hidden = true;
            }
            return;
        }

        if (!groupBadge || !itemBadge || !systemGroup) {
            return;
        }

        var isOpen = systemGroup.classList.contains('is-open');
        groupBadge.hidden = isOpen;
        itemBadge.hidden = !isOpen;
    }

    function syncSidebarBadge(data) {
        badgeActive = shouldShowSidebarBadge(data);
        refreshSidebarBadgePlacement();
    }

    function clearSidebarBadge() {
        badgeActive = false;
        refreshSidebarBadgePlacement();
    }

    function checkUpdate(options) {
        options = options || {};
        return fetch(apiUrl() + '?action=check', { credentials: 'same-origin' })
            .then(function (res) {
                return parseJsonResponse(res);
            })
            .then(function (res) {
                if (options.onResult) {
                    options.onResult(res);
                }
                return res;
            });
    }

    window.VsUpdate = {
        check: checkUpdate,
        showModal: showUpdateModal,
        confirmBackupThenUpdate: confirmBackupThenUpdate,
        runApply: runUpdateApply,
        buildUpdateHtml: buildUpdateHtml,
        syncSidebarBadge: syncSidebarBadge,
        refreshSidebarBadgePlacement: refreshSidebarBadgePlacement,
        clearSidebarBadge: clearSidebarBadge,
        dismiss: function (version) {
            return postUpdate('dismiss', { version: version }).then(function (res) {
                if (res.code === 1) {
                    syncSidebarBadge({
                        code: 1,
                        update_available: true,
                        show_modal: false,
                    });
                }
                return res;
            });
        },
    };
})();
