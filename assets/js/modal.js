/**
 * 文件：assets/js/modal.js
 * 作用：VanShine 统一弹窗（替代 alert / confirm）
 * @version 1.0.11
 */

(function () {
    'use strict';

    var root, overlay, titleEl, bodyEl, footEl;
    var resolveFn = null;

    function init() {
        root = document.getElementById('vsModalRoot');
        if (!root) {
            return;
        }
        overlay = document.getElementById('vsModalOverlay');
        titleEl = document.getElementById('vsModalTitle');
        bodyEl = document.getElementById('vsModalBody');
        footEl = document.getElementById('vsModalFoot');

        if (overlay) {
            overlay.addEventListener('click', function () {
                close(false);
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && root && !root.hidden) {
                close(false);
            }
        });
    }

    function close(result) {
        if (!root) {
            return;
        }
        root.hidden = true;
        root.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('vs-modal-open');
        if (resolveFn) {
            var fn = resolveFn;
            resolveFn = null;
            fn(result);
        }
    }

    function open(title, message, buttons) {
        if (!root || !titleEl || !bodyEl || !footEl) {
            return;
        }

        titleEl.textContent = title || '提示';
        bodyEl.textContent = message || '';
        footEl.innerHTML = '';

        buttons.forEach(function (btn) {
            var el = document.createElement('button');
            el.type = 'button';
            el.textContent = btn.text;
            el.className = 'vs-btn';

            if (btn.primary) {
                el.className += ' vs-btn--primary';
            } else {
                el.className += ' vs-btn--default';
            }
            if (btn.danger) {
                el.className += ' vs-btn--danger';
            }

            el.addEventListener('click', function () {
                if (btn.action) {
                    btn.action();
                }
            });
            footEl.appendChild(el);
        });

        root.hidden = false;
        root.setAttribute('aria-hidden', 'false');
        document.body.classList.add('vs-modal-open');
    }

    window.VsModal = {
        alert: function (message, title) {
            title = title || '提示';
            return new Promise(function (resolve) {
                resolveFn = function () {
                    resolve();
                };
                open(title, message, [{
                    text: '知道了',
                    primary: true,
                    action: function () {
                        close(true);
                    },
                }]);
            });
        },

        confirm: function (message, title, options) {
            options = options || {};
            title = title || '操作确认';
            return new Promise(function (resolve) {
                resolveFn = resolve;
                open(title, message, [
                    {
                        text: options.cancelText || '取消',
                        action: function () {
                            close(false);
                        },
                    },
                    {
                        text: options.confirmText || '确认',
                        primary: true,
                        danger: options.danger,
                        action: function () {
                            close(true);
                        },
                    },
                ]);
            });
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
