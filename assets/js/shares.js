/**
 * 文件：assets/js/shares.js
 * 作用：分享管理页
 * @version 1.0.53
 */

(function () {
    'use strict';

    var root = document.getElementById('shareManager');
    if (!root) return;

    var tbody = document.getElementById('sharesTableBody');
    var flash = document.getElementById('sharesFlash');
    var editModal = document.getElementById('shareEditModal');
    var editForm = document.getElementById('shareEditForm');
    var shares = [];

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function showFlash(msg, ok) {
        if (!flash) return;
        flash.textContent = msg;
        flash.className = 'vs-settings-flash' + (ok ? ' vs-settings-flash--ok' : ' vs-settings-flash--err');
        flash.hidden = false;
        setTimeout(function () { flash.hidden = true; }, 4000);
    }

    function post(action, data) {
        var fd = new FormData();
        fd.append('action', action);
        Object.keys(data || {}).forEach(function (k) {
            if (data[k] !== undefined && data[k] !== null) {
                fd.append(k, data[k]);
            }
        });
        return fetch(window.location.pathname, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    function statusLabel(row) {
        if (!row.enabled) return '<span class="vs-shares__badge vs-shares__badge--off">已停用</span>';
        if (row.expires_at && new Date(row.expires_at).getTime() < Date.now()) {
            return '<span class="vs-shares__badge vs-shares__badge--off">已过期</span>';
        }
        if (row.max_downloads > 0 && row.download_count >= row.max_downloads) {
            return '<span class="vs-shares__badge vs-shares__badge--off">次数已满</span>';
        }
        return '<span class="vs-shares__badge vs-shares__badge--on">有效</span>';
    }

    function render() {
        if (!tbody) return;
        if (!shares.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="vs-shares__empty">暂无分享链接，可在文件管理中创建</td></tr>';
            return;
        }
        tbody.innerHTML = shares.map(function (row) {
            var type = row.share_type === 'folder' ? '文件夹' : '单文件';
            var pwd = row.has_password ? '需密码' : '公开';
            var stats = row.view_count + ' / ' + row.download_count;
            return '<tr data-id="' + row.id + '">'
                + '<td>' + escapeHtml(row.title) + '</td>'
                + '<td>' + type + '</td>'
                + '<td class="vs-shares__target">' + escapeHtml(row.target) + '</td>'
                + '<td>' + pwd + '</td>'
                + '<td>' + stats + '</td>'
                + '<td>' + statusLabel(row) + '</td>'
                + '<td><input type="text" class="vs-shares__url" readonly value="' + escapeHtml(row.share_url) + '">'
                + '<button type="button" class="vs-btn vs-btn--default vs-shares__copy" data-copy-url="' + escapeHtml(row.share_url) + '">复制</button></td>'
                + '<td class="vs-shares__actions">'
                + '<button type="button" class="vs-btn vs-btn--default" data-edit-share="' + row.id + '">编辑</button> '
                + '<button type="button" class="vs-btn vs-btn--default vs-shares__del" data-del-share="' + row.id + '">删除</button>'
                + '</td></tr>';
        }).join('');
    }

    function openEdit(row) {
        document.getElementById('shareEditId').value = row.id;
        document.getElementById('shareEditTitleInput').value = row.title || '';
        document.getElementById('shareEditPassword').value = '';
        document.getElementById('shareEditMaxDl').value = row.max_downloads || 0;
        document.getElementById('shareEditPreview').checked = !!row.allow_preview;
        document.getElementById('shareEditEnabled').checked = !!row.enabled;
        var exp = document.getElementById('shareEditExpires');
        if (exp) {
            if (row.expires_at) {
                exp.value = row.expires_at.replace(' ', 'T').slice(0, 16);
            } else {
                exp.value = '';
            }
        }
        editModal.hidden = false;
    }

    function closeEdit() {
        editModal.hidden = true;
    }

    try {
        var initial = JSON.parse(root.getAttribute('data-initial') || '{}');
        shares = initial.shares || [];
    } catch (e) {
        shares = [];
    }
    render();

    if (tbody) {
        tbody.addEventListener('click', function (e) {
            var copyBtn = e.target.closest('[data-copy-url]');
            if (copyBtn) {
                var url = copyBtn.getAttribute('data-copy-url');
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function () {
                        showFlash('链接已复制', true);
                    });
                }
                return;
            }
            var editBtn = e.target.closest('[data-edit-share]');
            if (editBtn) {
                var id = parseInt(editBtn.getAttribute('data-edit-share'), 10);
                var row = shares.filter(function (s) { return s.id === id; })[0];
                if (row) openEdit(row);
                return;
            }
            var delBtn = e.target.closest('[data-del-share]');
            if (delBtn) {
                var delId = parseInt(delBtn.getAttribute('data-del-share'), 10);
                if (!window.confirm('确定删除此分享链接？删除后原短链接将失效。')) return;
                post('delete', { id: delId }).then(function (res) {
                    if (res.code !== 1) {
                        showFlash(res.msg || '删除失败', false);
                        return;
                    }
                    shares = shares.filter(function (s) { return s.id !== delId; });
                    render();
                    showFlash('已删除', true);
                });
            }
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var id = document.getElementById('shareEditId').value;
            var expVal = document.getElementById('shareEditExpires').value;
            post('update', {
                id: id,
                title: document.getElementById('shareEditTitleInput').value,
                password: document.getElementById('shareEditPassword').value,
                expires_at: expVal ? expVal.replace('T', ' ') + ':00' : '',
                max_downloads: document.getElementById('shareEditMaxDl').value,
                allow_preview: document.getElementById('shareEditPreview').checked ? 1 : 0,
                enabled: document.getElementById('shareEditEnabled').checked ? 1 : 0,
            }).then(function (res) {
                if (res.code !== 1) {
                    showFlash(res.msg || '保存失败', false);
                    return;
                }
                if (res.share) {
                    shares = shares.map(function (s) {
                        return s.id === res.share.id ? res.share : s;
                    });
                }
                render();
                closeEdit();
                showFlash('已保存', true);
            });
        });
    }

    document.querySelectorAll('[data-close-share-edit]').forEach(function (btn) {
        btn.addEventListener('click', closeEdit);
    });
})();
