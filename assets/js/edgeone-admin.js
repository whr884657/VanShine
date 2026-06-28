/**
 * EdgeOne 后台交互
 */
(function () {
    'use strict';

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

    function bindApiForms() {
        document.querySelectorAll('.vs-edgeone-api-form').forEach(function (form) {
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
                            setTimeout(function () { window.location.reload(); }, 600);
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

    function bindZoneForm() {
        var form = document.getElementById('edgeoneZoneForm');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var body = new FormData(form);
            postFormData(body)
                .then(function (data) {
                    if (data.code === 1) {
                        toast(data.msg || '已切换', 'success');
                        window.location.reload();
                    } else {
                        toast(data.msg || '切换失败', 'error');
                    }
                })
                .catch(function () {
                    toast('网络异常', 'error');
                });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindApiForms();
        bindZoneForm();
    });
})();
