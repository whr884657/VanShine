/**
 * 文件：assets/js/files.js
 * 作用：后台文件管理页
 * @version 1.0.30
 */

(function () {
    'use strict';

    var root = document.getElementById('fileManager');
    if (!root) return;

    var flashEl = document.getElementById('filesFlash');
    var breadcrumbEl = document.getElementById('fileBreadcrumb');
    var contentEl = document.getElementById('fileContent');
    var emptyTipEl = document.getElementById('fileEmptyTip');
    var folderMetaEl = document.getElementById('folderMeta');
    var uploadWrap = document.getElementById('uploadWrap');
    var uploadInput = document.getElementById('fileUploadInput');
    var folderModal = document.getElementById('folderModal');
    var folderForm = document.getElementById('folderForm');
    var storageSelect = document.getElementById('folderStorageSelect');
    var storageInheritTip = document.getElementById('storageInheritTip');
    var storagePickRow = document.getElementById('storagePickRow');

    var state = {
        folderId: 0,
        breadcrumb: [],
        currentFolder: null,
        folders: [],
        files: [],
        storages: [],
        view: localStorage.getItem('vs_file_view') || 'grid'
    };

    try {
        var initial = JSON.parse(root.getAttribute('data-initial') || '{}');
        applyPayload(initial);
    } catch (e) {
        showFlash('初始化失败', 'error');
    }

    function showFlash(text, type) {
        if (!flashEl) return;
        flashEl.textContent = text;
        flashEl.className = 'vs-settings-flash vs-alert vs-alert--' + type;
        flashEl.hidden = false;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function formatSize(bytes) {
        bytes = Number(bytes) || 0;
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(2) + ' MB';
    }

    function isImage(mime) {
        return String(mime || '').indexOf('image/') === 0;
    }

    function applyPayload(data) {
        if (!data) return;
        state.folderId = data.folder_id || 0;
        state.breadcrumb = data.breadcrumb || [];
        state.currentFolder = data.current_folder || null;
        state.folders = data.folders || [];
        state.files = data.files || [];
        state.storages = data.storages || [];
        render();
        syncUrl();
    }

    function syncUrl() {
        var url = window.location.pathname;
        if (state.folderId > 0) {
            url += '?folder=' + state.folderId;
        }
        window.history.replaceState({}, '', url);
    }

    function post(action, fields) {
        var body = new FormData();
        body.append('action', action);
        body.append('folder_id', String(state.folderId));
        Object.keys(fields || {}).forEach(function (key) {
            body.append(key, fields[key]);
        });
        return fetch(window.location.href, {
            method: 'POST',
            body: body,
            credentials: 'same-origin'
        }).then(function (res) { return res.json(); });
    }

    function loadFolder(folderId) {
        state.folderId = folderId;
        post('list').then(function (data) {
            if (data.code === 1) {
                applyPayload(data);
            } else {
                showFlash(data.msg || '加载失败', 'error');
            }
        }).catch(function () {
            showFlash('网络异常', 'error');
        });
    }

    function renderBreadcrumb() {
        if (!breadcrumbEl) return;
        var html = '';
        state.breadcrumb.forEach(function (item, index) {
            if (index > 0) html += '<span class="vs-filemgr__sep">/</span>';
            html += '<button type="button" class="vs-filemgr__crumb" data-folder-id="' + item.id + '">'
                + escapeHtml(item.name) + '</button>';
        });
        breadcrumbEl.innerHTML = html;
        breadcrumbEl.querySelectorAll('.vs-filemgr__crumb').forEach(function (btn) {
            btn.addEventListener('click', function () {
                loadFolder(parseInt(btn.getAttribute('data-folder-id'), 10) || 0);
            });
        });
    }

    function renderMeta() {
        if (!folderMetaEl) return;
        if (state.currentFolder) {
            folderMetaEl.hidden = false;
            folderMetaEl.innerHTML = '当前文件夹绑定储存：<strong>'
                + escapeHtml(state.currentFolder.storage_name) + '</strong>';
            if (uploadWrap) uploadWrap.hidden = false;
        } else {
            folderMetaEl.hidden = true;
            folderMetaEl.innerHTML = '';
            if (uploadWrap) uploadWrap.hidden = true;
        }
    }

    function fileIcon(file) {
        if (isImage(file.mime_type) && file.public_url) {
            return '<img src="' + escapeHtml(file.public_url) + '" alt="" class="vs-filemgr__thumb" loading="lazy">';
        }
        return '<span class="vs-filemgr__file-icon">📄</span>';
    }

    function renderContent() {
        if (!contentEl) return;

        contentEl.className = 'vs-filemgr__content view-' + state.view;
        var hasItems = state.folders.length > 0 || state.files.length > 0;

        if (!hasItems) {
            contentEl.innerHTML = '<p class="vs-form-tip">'
                + (state.folderId > 0 ? '此文件夹暂无内容，可上传文件或创建子文件夹。' : '根目录暂无文件夹，请先新建文件夹并绑定储存。')
                + '</p>';
            return;
        }

        var html = '<div class="vs-filemgr__grid">';

        state.folders.forEach(function (folder) {
            html += '<div class="vs-filemgr__item is-folder" data-folder-id="' + folder.id + '">';
            html += '<button type="button" class="vs-filemgr__open" data-folder-id="' + folder.id + '">';
            html += '<span class="vs-filemgr__folder-icon">📁</span>';
            html += '<span class="vs-filemgr__name">' + escapeHtml(folder.name) + '</span>';
            if (state.view === 'list') {
                html += '<span class="vs-filemgr__col vs-filemgr__col--type">文件夹</span>';
                html += '<span class="vs-filemgr__col vs-filemgr__col--storage">' + escapeHtml(folder.storage_name) + '</span>';
                html += '<span class="vs-filemgr__col vs-filemgr__col--size">—</span>';
            }
            html += '</button>';
            html += '<div class="vs-filemgr__actions">';
            html += '<button type="button" class="vs-filemgr__action" data-delete-folder="' + folder.id + '" title="删除">×</button>';
            html += '</div></div>';
        });

        state.files.forEach(function (file) {
            html += '<div class="vs-filemgr__item is-file" data-file-id="' + file.id + '">';
            html += '<a class="vs-filemgr__open" href="' + escapeHtml(file.public_url) + '" target="_blank" rel="noopener">';
            html += fileIcon(file);
            html += '<span class="vs-filemgr__name" title="' + escapeHtml(file.original_name) + '">'
                + escapeHtml(file.stored_name) + '</span>';
            if (state.view === 'list') {
                html += '<span class="vs-filemgr__col vs-filemgr__col--type">' + escapeHtml(file.mime_type) + '</span>';
                html += '<span class="vs-filemgr__col vs-filemgr__col--storage">—</span>';
                html += '<span class="vs-filemgr__col vs-filemgr__col--size">' + formatSize(file.file_size) + '</span>';
            }
            html += '</a>';
            html += '<div class="vs-filemgr__actions">';
            html += '<button type="button" class="vs-filemgr__action" data-delete-file="' + file.id + '" title="删除">×</button>';
            html += '</div></div>';
        });

        html += '</div>';
        contentEl.innerHTML = html;

        contentEl.querySelectorAll('.vs-filemgr__open[data-folder-id]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                loadFolder(parseInt(btn.getAttribute('data-folder-id'), 10));
            });
        });

        contentEl.querySelectorAll('[data-delete-folder]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var id = btn.getAttribute('data-delete-folder');
                confirmDelete('确定删除该文件夹吗？', function () {
                    post('delete_folder', { target_id: id }).then(handleActionResponse);
                });
            });
        });

        contentEl.querySelectorAll('[data-delete-file]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var id = btn.getAttribute('data-delete-file');
                confirmDelete('确定删除该文件吗？', function () {
                    post('delete_file', { target_id: id }).then(handleActionResponse);
                });
            });
        });
    }

    function handleActionResponse(data) {
        if (data.code === 1) {
            showFlash(data.msg || '操作成功', 'success');
            applyPayload(data);
        } else {
            showFlash(data.msg || '操作失败', 'error');
        }
    }

    function confirmDelete(message, onOk) {
        if (window.VsModal) {
            VsModal.confirm(message, '确认', { danger: true }).then(function (ok) {
                if (ok) onOk();
            });
        } else if (confirm(message)) {
            onOk();
        }
    }

    function renderViewButtons() {
        document.querySelectorAll('.vs-filemgr__view-btn').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-view') === state.view);
        });
    }

    document.querySelectorAll('.vs-filemgr__view-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            state.view = btn.getAttribute('data-view');
            localStorage.setItem('vs_file_view', state.view);
            renderViewButtons();
            renderContent();
        });
    });

    function renderStorageSelect() {
        if (!storageSelect) return;
        storageSelect.innerHTML = '';
        if (state.storages.length === 0) {
            storageSelect.innerHTML = '<option value="">请先在系统设置中启用储存</option>';
            return;
        }
        state.storages.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = String(item.key);
            opt.textContent = item.name;
            storageSelect.appendChild(opt);
        });
    }

    function openFolderModal() {
        if (!folderModal) return;
        renderStorageSelect();

        var isRoot = state.folderId <= 0;
        if (storagePickRow) storagePickRow.hidden = !isRoot;
        if (storageInheritTip) storageInheritTip.hidden = isRoot;
        if (storageSelect) storageSelect.required = isRoot;

        folderModal.hidden = false;
        folderModal.classList.add('is-open');
    }

    function closeFolderModal() {
        if (!folderModal) return;
        folderModal.hidden = true;
        folderModal.classList.remove('is-open');
        if (folderForm) folderForm.reset();
    }

    function render() {
        renderBreadcrumb();
        renderMeta();
        renderContent();
        renderViewButtons();
    }

    document.getElementById('btnNewFolder').addEventListener('click', openFolderModal);

    folderModal.querySelectorAll('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', closeFolderModal);
    });

    if (folderForm) {
        folderForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(folderForm);
            var fields = { name: fd.get('name') };
            if (state.folderId <= 0) {
                fields.storage_key = fd.get('storage_key');
            }
            post('create_folder', fields).then(function (data) {
                if (data.code === 1) {
                    closeFolderModal();
                    handleActionResponse(data);
                } else {
                    showFlash(data.msg || '创建失败', 'error');
                }
            }).catch(function () {
                showFlash('网络异常', 'error');
            });
        });
    }

    if (uploadInput) {
        uploadInput.addEventListener('change', function () {
            if (!uploadInput.files || !uploadInput.files.length) return;
            var file = uploadInput.files[0];
            var body = new FormData();
            body.append('action', 'upload');
            body.append('folder_id', String(state.folderId));
            body.append('file', file);

            fetch(window.location.href, {
                method: 'POST',
                body: body,
                credentials: 'same-origin'
            }).then(function (res) { return res.json(); })
                .then(function (data) {
                    uploadInput.value = '';
                    handleActionResponse(data);
                })
                .catch(function () {
                    uploadInput.value = '';
                    showFlash('上传失败', 'error');
                });
        });
    }
})();
