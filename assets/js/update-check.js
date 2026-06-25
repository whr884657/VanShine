/**
 * 文件：assets/js/update-check.js
 * 作用：登录后自动检测更新（弹窗 + 侧边栏角标）
 * @version 1.0.26
 */

(function () {
    'use strict';

    var checked = false;

    document.addEventListener('DOMContentLoaded', function () {
        if (checked || !window.VsUpdate) {
            return;
        }
        checked = true;

        VsUpdate.check().then(function (res) {
            VsUpdate.syncSidebarBadge(res);

            if (res.code !== 1 || !res.show_modal) {
                return;
            }

            VsUpdate.showModal(res, {
                onDismiss: function (data) {
                    VsUpdate.dismiss(data.remote_version);
                },
            });
        }).catch(function () {
            /* 静默失败 */
        });
    });
})();
