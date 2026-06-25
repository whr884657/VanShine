/**
 * 文件：assets/js/upgrade.js
 * 作用：系统升级页面交互
 * @version 1.0.17
 */

(function () {
    'use strict';

    var statusEl = document.getElementById('upgradeStatus');
    var checkBtn = document.getElementById('upgradeCheckBtn');
    var updateBtn = document.getElementById('upgradeApplyBtn');
    var lastCheck = null;

    function setStatus(text, type) {
        if (!statusEl) return;
        statusEl.textContent = text;
        statusEl.className = 'vs-alert vs-alert--' + (type || 'info');
        statusEl.hidden = false;
    }

    function renderCheckResult(res) {
        lastCheck = res;
        if (res.code !== 1) {
            setStatus(res.msg || '检测失败', 'error');
            if (updateBtn) updateBtn.disabled = true;
            return;
        }

        if (res.update_available) {
            setStatus('发现新版本 v' + res.remote_version + '（当前 v' + res.local_version + '）', 'warning');
            if (updateBtn) updateBtn.disabled = false;
        } else if (res.ahead_of_remote) {
            setStatus('当前版本 v' + res.local_version + ' 高于仓库版本（测试环境）', 'info');
            if (updateBtn) updateBtn.disabled = true;
        } else {
            setStatus('当前已是最新版本 v' + res.local_version, 'success');
            if (updateBtn) updateBtn.disabled = true;
        }
    }

    if (checkBtn) {
        checkBtn.addEventListener('click', function () {
            checkBtn.disabled = true;
            setStatus('正在检测 Gitee 最新版本…', 'info');
            VsUpdate.check({ onResult: renderCheckResult })
                .catch(function () {
                    setStatus('网络异常，请稍后重试', 'error');
                })
                .finally(function () {
                    checkBtn.disabled = false;
                });
        });
    }

    if (updateBtn) {
        updateBtn.disabled = true;
        updateBtn.addEventListener('click', function () {
            if (!lastCheck || !lastCheck.update_available) {
                setStatus('请先检测更新', 'error');
                return;
            }
            VsUpdate.showModal(lastCheck, {
                hideDismiss: true,
                cancelText: '取消',
                confirmText: '继续更新',
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (checkBtn) {
            checkBtn.click();
        }
    });
})();
