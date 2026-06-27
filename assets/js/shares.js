/**
 * 文件：assets/js/shares.js
 * 作用：分享管理页
 * @version 1.0.61
 */

(function () {
    'use strict';

    var root = document.getElementById('shareManager');
    if (!root) return;

    var tbody = document.getElementById('sharesTableBody');
    var cardList = document.getElementById('sharesCardList');
    var flash = document.getElementById('sharesFlash');
    var editModal = document.getElementById('shareEditModal');
    var editForm = document.getElementById('shareEditForm');
    var shares = [];
    var expandedCards = {};

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
        if (!row.enabled) return { text: '已停用', cls: 'off' };
        if (row.expires_at && new Date(row.expires_at).getTime() < Date.now()) {
            return { text: '已过期', cls: 'off' };
        }
        if (row.max_downloads > 0 && row.download_count >= row.max_downloads) {
            return { text: '次数已满', cls: 'off' };
        }
        return { text: '有效', cls: 'on' };
    }

    function isAccessible(row) {
        return statusLabel(row).cls === 'on';
    }

    function typeText(row) {
        return row.share_type === 'folder' ? '文件夹' : '单文件';
    }

    function expiryText(row) {
        if (!row.expires_at) return '永不过期';
        return String(row.expires_at).split(' ')[0];
    }

    function passwordText(row) {
        return row.has_password ? '需密码' : '公开访问';
    }

    function storageText(row) {
        var key = row.storage_key ? (row.storage_key + '. ') : '';
        return key + (row.storage_name || '未知储存');
    }

    function sortShares(list) {
        return list.slice().sort(function (a, b) {
            var aa = isAccessible(a) ? 0 : 1;
            var bb = isAccessible(b) ? 0 : 1;
            if (aa !== bb) return aa - bb;
            return (b.id || 0) - (a.id || 0);
        });
    }

    function mountModalToBody(modal) {
        if (modal && modal.parentNode !== document.body) {
            document.body.appendChild(modal);
        }
    }

    function renderTable() {
        if (!tbody) return;
        var rows = sortShares(shares);
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="9" class="vs-shares__empty">暂无分享链接，可在文件管理中创建</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(function (row) {
            var st = statusLabel(row);
            return '<tr data-id="' + row.id + '">'
                + '<td>' + escapeHtml(row.title) + '</td>'
                + '<td>' + typeText(row) + '</td>'
                + '<td class="vs-shares__target">' + escapeHtml(row.target) + '</td>'
                + '<td>' + escapeHtml(storageText(row)) + '</td>'
                + '<td>' + escapeHtml(passwordText(row)) + '</td>'
                + '<td>' + row.view_count + ' / ' + row.download_count + '</td>'
                + '<td><span class="vs-shares__badge vs-shares__badge--' + st.cls + '">' + st.text + '</span></td>'
                + '<td><input type="text" class="vs-shares__url" readonly value="' + escapeHtml(row.share_url) + '"></td>'
                + '<td class="vs-shares__actions">'
                + '<button type="button" class="vs-btn vs-btn--default vs-btn--rect vs-btn--xs" data-copy-url="' + escapeHtml(row.share_url) + '">复制</button> '
                + '<button type="button" class="vs-btn vs-btn--default vs-btn--rect vs-btn--xs" data-edit-share="' + row.id + '">编辑</button> '
                + '<button type="button" class="vs-btn vs-btn--default vs-btn--rect vs-btn--xs vs-shares__del" data-del-share="' + row.id + '">删除</button>'
                + '</td></tr>';
        }).join('');
    }

    function renderCards() {
        if (!cardList) return;
        var rows = sortShares(shares);
        if (!rows.length) {
            cardList.innerHTML = '<div class="vs-shares__empty">暂无分享链接，可在文件管理中创建</div>';
            return;
        }
        cardList.innerHTML = rows.map(function (row) {
            var st = statusLabel(row);
            var accessible = isAccessible(row);
            var expanded = !!expandedCards[row.id];
            var dlLimit = row.max_downloads > 0 ? row.max_downloads + ' 次' : '不限';
            var cardClass = 'vs-share-card is-collapsed';
            if (!accessible) cardClass += ' is-inactive';
            if (expanded) cardClass += ' is-expanded';

            return '<article class="' + cardClass + '" data-id="' + row.id + '">'
                + '<button type="button" class="vs-share-card__toggle" data-toggle-share="' + row.id + '" aria-expanded="' + (expanded ? 'true' : 'false') + '">'
                + '<span class="vs-share-card__toggle-main">'
                + '<span class="vs-share-card__title">' + escapeHtml(row.title) + '</span>'
                + '<span class="vs-share-card__toggle-sub">' + typeText(row) + '</span>'
                + '</span>'
                + '<span class="vs-shares__badge vs-shares__badge--' + st.cls + '">' + st.text + '</span>'
                + '<span class="vs-share-card__chevron" aria-hidden="true"></span>'
                + '</button>'
                + '<div class="vs-share-card__body"' + (expanded ? '' : ' hidden') + '>'
                + '<p class="vs-share-card__sub">' + escapeHtml(row.target) + '</p>'
                + '<div class="vs-share-card__url">' + escapeHtml(row.share_url) + '</div>'
                + '<div class="vs-share-card__tags">'
                + '<span class="vs-share-card__tag">' + escapeHtml(storageText(row)) + '</span>'
                + '<span class="vs-share-card__tag">' + escapeHtml(passwordText(row)) + '</span>'
                + '<span class="vs-share-card__tag">过期 ' + escapeHtml(expiryText(row)) + '</span>'
                + '<span class="vs-share-card__tag">下载上限 ' + escapeHtml(dlLimit) + '</span>'
                + '<span class="vs-share-card__tag">访问 ' + row.view_count + ' / 下载 ' + row.download_count + '</span>'
                + '</div>'
                + '<div class="vs-share-card__actions">'
                + '<button type="button" class="vs-btn vs-btn--primary vs-btn--rect vs-btn--block" data-copy-url="' + escapeHtml(row.share_url) + '">复制短链接</button>'
                + '<div class="vs-share-card__actions-row">'
                + '<button type="button" class="vs-btn vs-btn--default vs-btn--rect" data-edit-share="' + row.id + '">编辑</button>'
                + '<button type="button" class="vs-btn vs-btn--default vs-btn--rect vs-shares__del" data-del-share="' + row.id + '">删除</button>'
                + '</div></div></div></article>';
        }).join('');
    }

    function render() {
        renderTable();
        renderCards();
    }

    function openEdit(row) {
        mountModalToBody(editModal);
        document.getElementById('shareEditId').value = row.id;
        document.getElementById('shareEditTitleInput').value = row.title || '';
        document.getElementById('shareEditPassword').value = '';
        document.getElementById('shareEditMaxDl').value = row.max_downloads || 0;
        document.getElementById('shareEditPreview').checked = !!row.allow_preview;
        document.getElementById('shareEditEnabled').checked = !!row.enabled;
        if (window.VsDatetime) {
            VsDatetime.setValue('#shareEditExpiresWrap', row.expires_at || '');
        }
        editModal.hidden = false;
        editModal.classList.add('is-open');
        document.body.classList.add('vs-modal-open');
    }

    function closeEdit() {
        editModal.hidden = true;
        editModal.classList.remove('is-open');
        document.body.classList.remove('vs-modal-open');
    }

    function handleActionClick(e) {
        var toggleBtn = e.target.closest('[data-toggle-share]');
        if (toggleBtn) {
            var tid = parseInt(toggleBtn.getAttribute('data-toggle-share'), 10);
            expandedCards[tid] = !expandedCards[tid];
            renderCards();
            return;
        }

        var copyBtn = e.target.closest('[data-copy-url]');
        if (copyBtn) {
            var url = copyBtn.getAttribute('data-copy-url');
            if (navigator.clipboard && url) {
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
                delete expandedCards[delId];
                render();
                showFlash('已删除', true);
            });
        }
    }

    try {
        var initial = JSON.parse(root.getAttribute('data-initial') || '{}');
        shares = initial.shares || [];
    } catch (e) {
        shares = [];
    }

    mountModalToBody(editModal);
    render();

    if (tbody) tbody.addEventListener('click', handleActionClick);
    if (cardList) cardList.addEventListener('click', handleActionClick);

    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var id = document.getElementById('shareEditId').value;
            var exp = window.VsDatetime ? VsDatetime.getValue('#shareEditExpiresWrap') : '';
            post('update', {
                id: id,
                title: document.getElementById('shareEditTitleInput').value,
                password: document.getElementById('shareEditPassword').value,
                expires_at: exp,
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

    if (editModal) {
        editModal.addEventListener('click', function (e) {
            if (e.target === editModal) closeEdit();
        });
    }
})();
