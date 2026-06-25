/**
 * 文件：assets/js/update-check.js
 * 作用：后台登录后自动检测 Gitee 版本并提示更新
 * @version 1.0.14
 */

(function () {
    'use strict';

    var checked = false;

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
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

        html += '<p class="vs-update-modal__tip">更新将自动执行数据库迁移，且绝不替换 config/database.php，完成后清理临时文件。</p>';
        html += '</div>';
        return html;
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
        return fetch((window.VS_BASE_URL || '') + '/admin/update.php', {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
        }).then(function (res) {
            return res.json();
        });
    }

    function dismissVersion(version) {
        return postUpdate('dismiss', { version: version });
    }

    function runUpdate(data) {
        if (!window.VsModal || !window.VsModal.open) {
            return;
        }

        VsModal.open({
            title: '正在更新…',
            html: '<p class="vs-update-modal__loading">正在从 Gitee 下载更新包并安装，请勿关闭页面…</p>',
            closeOnOverlay: false,
            closeOnEscape: false,
            buttons: [],
        });

        postUpdate('apply')
            .then(function (res) {
                if (res.code === 1) {
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
            })
            .catch(function () {
                VsModal.alert('网络异常，更新失败，请稍后重试');
            });
    }

    function showUpdateModal(data) {
        if (!window.VsModal || !window.VsModal.open) {
            return;
        }

        VsModal.open({
            title: '发现新版本 v' + data.remote_version,
            html: buildUpdateHtml(data),
            closeOnOverlay: false,
            buttons: [
                {
                    text: '稍后提醒',
                    action: function () {
                        dismissVersion(data.remote_version);
                        VsModal.close(false);
                    },
                },
                {
                    text: '立即更新',
                    primary: true,
                    action: function () {
                        VsModal.close(false);
                        runUpdate(data);
                    },
                },
            ],
        });
    }

    function checkUpdate() {
        if (checked) {
            return;
        }
        checked = true;

        fetch((window.VS_BASE_URL || '') + '/admin/update.php?action=check', {
            credentials: 'same-origin',
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (res) {
                if (res.code !== 1 || !res.show_modal) {
                    return;
                }
                showUpdateModal(res);
            })
            .catch(function () {
                /* 静默失败，不影响后台使用 */
            });
    }

    document.addEventListener('DOMContentLoaded', checkUpdate);
})();
