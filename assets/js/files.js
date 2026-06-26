/**
 * 文件：assets/js/files.js
 * 作用：后台文件管理页（批量/拖拽上传、预览、重命名）
 * @version 1.0.45
 */

(function () {
    'use strict';

    var root = document.getElementById('fileManager');
    if (!root) return;

    var flashEl = document.getElementById('filesFlash');
    var breadcrumbEl = document.getElementById('fileBreadcrumb');
    var contentEl = document.getElementById('fileContent');
    var folderMetaEl = document.getElementById('folderMeta');
    var uploadWrap = document.getElementById('uploadWrap');
    var uploadInput = document.getElementById('fileUploadInput');
    var folderModal = document.getElementById('folderModal');
    var renameModal = document.getElementById('renameModal');
    var folderForm = document.getElementById('folderForm');
    var renameForm = document.getElementById('renameForm');
    var renameInput = document.getElementById('renameInput');
    var renameTargetId = document.getElementById('renameTargetId');
    var storageSelect = document.getElementById('folderStorageSelect');
    var storageInheritTip = document.getElementById('storageInheritTip');
    var storagePickRow = document.getElementById('storagePickRow');
    var filePreview = document.getElementById('filePreview');
    var filePreviewTitle = document.getElementById('filePreviewTitle');
    var filePreviewViewerMount = document.getElementById('filePreviewViewerMount');
    var filePreviewViewerState = document.getElementById('filePreviewViewerState');
    var filePreviewMeta = document.getElementById('filePreviewMeta');
    var filePreviewLink = document.getElementById('filePreviewLink');
    var filePreviewCopy = document.getElementById('filePreviewCopy');
    var filePreviewOpen = document.getElementById('filePreviewOpen');
    var filePreviewDownload = document.getElementById('filePreviewDownload');
    var fileReplaceInput = document.getElementById('fileReplaceInput');
    var fileReplaceProgress = document.getElementById('fileReplaceProgress');
    var fileReplaceFill = document.getElementById('fileReplaceFill');
    var fileReplaceStatus = document.getElementById('fileReplaceStatus');

    var state = {
        folderId: 0,
        breadcrumb: [],
        currentFolder: null,
        folders: [],
        files: [],
        storages: [],
        view: localStorage.getItem('vs_file_view') || 'grid',
        replacing: false,
        dragDepth: 0,
        previewFile: null
    };

    try {
        applyPayload(JSON.parse(root.getAttribute('data-initial') || '{}'));
    } catch (e) {
        showFlash('初始化失败', 'error');
    }

    function showFlash(text, type) {
        if (window.VsToast) {
            VsToast.show(text, type === 'error' ? 'error' : (type === 'info' ? 'info' : 'success'));
            return;
        }
        if (!flashEl) return;
        flashEl.textContent = text;
        flashEl.className = 'vs-settings-flash vs-alert vs-alert--' + type;
        flashEl.hidden = false;
    }

    function decodeDisplayUrl(url) {
        if (!url) return '';
        try {
            var parsed = new URL(url, window.location.origin);
            var parts = parsed.pathname.split('/').map(function (seg) {
                if (!seg) return seg;
                try {
                    return decodeURIComponent(seg);
                } catch (e) {
                    return seg;
                }
            });
            parsed.pathname = parts.join('/');
            return parsed.href;
        } catch (err) {
            return String(url).replace(/%[0-9A-Fa-f]{2}/g, function (m) {
                try {
                    return decodeURIComponent(m);
                } catch (e2) {
                    return m;
                }
            });
        }
    }

    function parseResponse(res) {
        return res.text().then(function (text) {
            var data = window.VS && VS.parseJsonResponse ? VS.parseJsonResponse(text) : null;
            if (!data) {
                throw new Error('invalid_json');
            }
            return data;
        });
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

    function findFile(id) {
        id = Number(id);
        for (var i = 0; i < state.files.length; i++) {
            if (state.files[i].id === id) return state.files[i];
        }
        return null;
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
        }).then(parseResponse);
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

    function uploadFiles(fileList) {
        if (state.folderId <= 0) {
            showFlash('请先进入已绑定储存的文件夹再上传', 'error');
            return;
        }
        if (!fileList || !fileList.length) {
            return;
        }
        if (!window.VsUploadQueue) {
            showFlash('上传组件未加载', 'error');
            return;
        }

        var files = Array.prototype.slice.call(fileList);
        if (uploadInput) uploadInput.value = '';

        var uploadUrl = window.location.pathname;
        if (state.folderId > 0) {
            uploadUrl += '?folder=' + state.folderId;
        }
        if (window.VS_BASE_URL) {
            uploadUrl = window.VS_BASE_URL + uploadUrl;
        }

        files.forEach(function (file) {
            window.VsUploadQueue.enqueue(file, {
                folderId: state.folderId,
                uploadUrl: uploadUrl,
                localOnly: true
            });
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
                + escapeHtml(state.currentFolder.storage_key) + '. '
                + escapeHtml(state.currentFolder.storage_name) + '</strong>'
                + ' · 支持拖拽文件到下方区域批量上传';
            if (uploadWrap) uploadWrap.hidden = false;
            root.classList.add('can-upload');
        } else {
            folderMetaEl.hidden = true;
            folderMetaEl.innerHTML = '';
            if (uploadWrap) uploadWrap.hidden = true;
            root.classList.remove('can-upload');
        }
    }

    function fileIconHtml(file, listMode) {
        if (isImage(file.mime_type) && file.public_url) {
            return '<img src="' + escapeHtml(file.public_url) + '" alt="" class="vs-filemgr__thumb" loading="lazy">';
        }
        return '<span class="vs-filemgr__file-icon">' + (listMode ? '📄' : '📄') + '</span>';
    }

    function fileTypeLabel(file) {
        var mime = String(file.mime_type || '');
        if (mime.indexOf('image/') === 0) return '图片';
        if (mime.indexOf('audio/') === 0) {
            var audio = mime.split('/')[1] || '音频';
            return audio.toUpperCase();
        }
        if (mime.indexOf('video/') === 0) return '视频';
        if (mime.indexOf('text/') === 0) return '文本';
        if (mime === 'application/pdf') return 'PDF';
        var ext = '';
        var name = file.stored_name || file.original_name || '';
        var dot = name.lastIndexOf('.');
        if (dot >= 0) ext = name.substring(dot + 1).toUpperCase();
        return ext || '文件';
    }

    function storageLabelForFolder(folder) {
        return folder.storage_key + '. ' + folder.storage_name;
    }

    function storageLabelForFile() {
        if (state.currentFolder) {
            return state.currentFolder.storage_key + '. ' + state.currentFolder.storage_name;
        }
        return '—';
    }

    function renderContent() {
        if (!contentEl) return;

        contentEl.className = 'vs-filemgr__content view-' + state.view;
        var hasItems = state.folders.length > 0 || state.files.length > 0;
        var isList = state.view === 'list';

        if (!hasItems) {
            contentEl.innerHTML = '<p class="vs-form-tip vs-filemgr__empty">'
                + (state.folderId > 0
                    ? '此文件夹暂无内容。可拖拽文件到此处、点击「上传文件」批量选择，或新建子文件夹。'
                    : '根目录暂无文件夹，请先新建文件夹并绑定储存（KEY 1–7）。')
                + '</p>';
            return;
        }

        var html = '<div class="vs-filemgr__grid">';
        if (isList) {
            html += '<div class="vs-filemgr__list-head">'
                + '<span>名称</span><span>类型</span><span>储存</span><span>大小</span><span>操作</span>'
                + '</div>';
        }

        state.folders.forEach(function (folder) {
            if (isList) {
                html += '<div class="vs-filemgr__item is-folder" data-folder-id="' + folder.id + '">';
                html += '<button type="button" class="vs-filemgr__cell vs-filemgr__cell--name" data-folder-id="' + folder.id + '">';
                html += '<span class="vs-filemgr__folder-icon">📁</span>';
                html += '<span class="vs-filemgr__name">' + escapeHtml(folder.name) + '</span>';
                html += '</button>';
                html += '<span class="vs-filemgr__cell vs-filemgr__cell--type">文件夹</span>';
                html += '<span class="vs-filemgr__cell vs-filemgr__cell--storage">' + escapeHtml(storageLabelForFolder(folder)) + '</span>';
                html += '<span class="vs-filemgr__cell vs-filemgr__cell--size">—</span>';
                html += '<div class="vs-filemgr__cell vs-filemgr__cell--actions">';
                html += '<button type="button" class="vs-filemgr__action vs-filemgr__action--edit" data-rename-folder="'
                    + folder.id + '" title="重命名">✎</button>';
                html += '<button type="button" class="vs-filemgr__action" data-delete-folder="' + folder.id + '" title="删除">×</button>';
                html += '</div></div>';
                return;
            }

            html += '<div class="vs-filemgr__item is-folder" data-folder-id="' + folder.id + '">';
            html += '<button type="button" class="vs-filemgr__open" data-folder-id="' + folder.id + '">';
            html += '<span class="vs-filemgr__folder-icon">📁</span>';
            html += '<span class="vs-filemgr__name">' + escapeHtml(folder.name) + '</span>';
            html += '</button>';
            html += '<div class="vs-filemgr__actions">';
            html += '<button type="button" class="vs-filemgr__action vs-filemgr__action--edit" data-rename-folder="'
                + folder.id + '" title="重命名">✎</button>';
            html += '<button type="button" class="vs-filemgr__action" data-delete-folder="' + folder.id + '" title="删除">×</button>';
            html += '</div></div>';
        });

        state.files.forEach(function (file) {
            if (isList) {
                html += '<div class="vs-filemgr__item is-file" data-file-id="' + file.id + '">';
                html += '<button type="button" class="vs-filemgr__cell vs-filemgr__cell--name" data-preview-file="' + file.id + '">';
                html += fileIconHtml(file, true);
                html += '<span class="vs-filemgr__name-wrap">';
                html += '<span class="vs-filemgr__name" title="' + escapeHtml(file.original_name) + '">'
                    + escapeHtml(file.stored_name) + '</span>';
                html += '<span class="vs-filemgr__size-inline">' + formatSize(file.file_size) + '</span>';
                html += '</span>';
                html += '</button>';
                html += '<span class="vs-filemgr__cell vs-filemgr__cell--type">' + escapeHtml(fileTypeLabel(file)) + '</span>';
                html += '<span class="vs-filemgr__cell vs-filemgr__cell--storage">' + escapeHtml(storageLabelForFile()) + '</span>';
                html += '<span class="vs-filemgr__cell vs-filemgr__cell--size">' + formatSize(file.file_size) + '</span>';
                html += '<div class="vs-filemgr__cell vs-filemgr__cell--actions">';
                html += '<button type="button" class="vs-filemgr__action" data-delete-file="' + file.id + '" title="删除">×</button>';
                html += '</div></div>';
                return;
            }

            html += '<div class="vs-filemgr__item is-file" data-file-id="' + file.id + '">';
            html += '<button type="button" class="vs-filemgr__open" data-preview-file="' + file.id + '">';
            html += fileIconHtml(file, false);
            html += '<span class="vs-filemgr__name" title="' + escapeHtml(file.original_name) + '">'
                + escapeHtml(file.stored_name) + '</span>';
            html += '</button>';
            html += '<div class="vs-filemgr__actions">';
            html += '<button type="button" class="vs-filemgr__action" data-delete-file="' + file.id + '" title="删除">×</button>';
            html += '</div></div>';
        });

        html += '</div>';
        contentEl.innerHTML = html;
        bindContentEvents();
    }

    function bindContentEvents() {
        contentEl.querySelectorAll('[data-folder-id]').forEach(function (btn) {
            if (btn.hasAttribute('data-preview-file')) return;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                loadFolder(parseInt(btn.getAttribute('data-folder-id'), 10));
            });
        });

        contentEl.querySelectorAll('[data-preview-file]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var file = findFile(btn.getAttribute('data-preview-file'));
                if (file) openFilePreview(file);
            });
            btn.addEventListener('dblclick', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var file = findFile(btn.getAttribute('data-preview-file'));
                if (file) openFilePreview(file);
            });
        });

        contentEl.querySelectorAll('[data-rename-folder]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var id = parseInt(btn.getAttribute('data-rename-folder'), 10);
                var folder = null;
                state.folders.forEach(function (f) {
                    if (f.id === id) folder = f;
                });
                openRenameModal(id, folder ? folder.name : '');
            });
        });

        contentEl.querySelectorAll('[data-delete-folder]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                confirmDelete('确定删除该文件夹吗？', function () {
                    post('delete_folder', { target_id: btn.getAttribute('data-delete-folder') })
                        .then(handleActionResponse)
                        .catch(function () {
                            showFlash('网络异常', 'error');
                        });
                });
            });
        });

        contentEl.querySelectorAll('[data-delete-file]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var fileId = parseInt(btn.getAttribute('data-delete-file'), 10);
                confirmDelete('确定删除该文件吗？', function () {
                    post('delete_file', { target_id: String(fileId) }).then(function (data) {
                        handleActionResponse(data, { deletedFileId: fileId });
                    }).catch(function () {
                        showFlash('网络异常', 'error');
                    });
                });
            });
        });
    }

    function setViewerState(message, isError) {
        if (!filePreviewViewerState) return;
        filePreviewViewerState.hidden = false;
        filePreviewViewerState.textContent = message || '';
        filePreviewViewerState.classList.toggle('is-error', !!isError);
    }

    function clearViewerState() {
        if (!filePreviewViewerState) return;
        filePreviewViewerState.hidden = true;
        filePreviewViewerState.textContent = '';
        filePreviewViewerState.classList.remove('is-error');
    }

    function destroyFileViewer() {
        if (window.VsFlyfishViewer) {
            VsFlyfishViewer.destroy();
        }
        if (filePreviewViewerMount) {
            filePreviewViewerMount.innerHTML = '';
        }
        clearViewerState();
    }

    function mountFileViewer(file) {
        if (!filePreviewViewerMount || !file || !file.public_url) {
            setViewerState('暂无可用预览地址', true);
            return;
        }

        if (!window.VsFlyfishViewer) {
            setViewerState('预览组件未加载', true);
            return;
        }

        destroyFileViewer();
        setViewerState('正在加载预览…', false);

        VsFlyfishViewer.mount(filePreviewViewerMount, file, {
            onReady: function () {
                clearViewerState();
            },
            onError: function () {
                setViewerState('预览加载失败，可尝试新窗口打开或下载', true);
            }
        }).catch(function () {
            setViewerState('预览组件加载失败，请检查网络或稍后重试', true);
        });
    }

    function openFilePreview(file) {
        if (!filePreview || !file) return;
        state.previewFile = file;

        var displayName = file.stored_name || file.original_name || '文件';
        if (filePreviewTitle) filePreviewTitle.textContent = displayName;

        mountFileViewer(file);

        if (filePreviewMeta) {
            filePreviewMeta.innerHTML = ''
                + metaRow('文件名', file.original_name || file.stored_name)
                + metaRow('存储名', file.stored_name)
                + metaRow('类型', file.mime_type || '—')
                + metaRow('大小', formatSize(file.file_size));
        }

        var url = file.public_url || '';
        var displayUrl = decodeDisplayUrl(url);
        if (filePreviewLink) filePreviewLink.value = displayUrl;
        if (filePreviewOpen) {
            filePreviewOpen.href = url || '#';
            filePreviewOpen.style.display = url ? '' : 'none';
        }
        if (filePreviewDownload) {
            filePreviewDownload.href = url || '#';
            filePreviewDownload.setAttribute('download', file.stored_name || '');
            filePreviewDownload.style.display = url ? '' : 'none';
        }

        filePreview.hidden = false;
        filePreview.classList.add('is-open');
        filePreview.setAttribute('aria-hidden', 'false');
        document.body.classList.add('vs-modal-open');
        resetReplaceProgress();
    }

    function resetReplaceProgress() {
        state.replacing = false;
        if (fileReplaceProgress) fileReplaceProgress.hidden = true;
        if (fileReplaceFill) fileReplaceFill.style.width = '0%';
        if (fileReplaceStatus) fileReplaceStatus.textContent = '替换中…';
        if (fileReplaceInput) fileReplaceInput.value = '';
    }

    function setReplaceProgress(percent, message) {
        if (fileReplaceProgress) fileReplaceProgress.hidden = false;
        if (fileReplaceFill) fileReplaceFill.style.width = Math.max(0, Math.min(100, percent)) + '%';
        if (fileReplaceStatus) fileReplaceStatus.textContent = message;
    }

    function replaceCurrentFile(uploadFile) {
        if (!state.previewFile || state.replacing) return;
        if (!uploadFile) return;

        state.replacing = true;
        setReplaceProgress(0, '正在删除旧文件并上传…');

        var body = new FormData();
        body.append('action', 'replace_file');
        body.append('folder_id', String(state.folderId));
        body.append('target_id', String(state.previewFile.id));
        body.append('file', uploadFile);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        xhr.withCredentials = true;

        xhr.upload.addEventListener('progress', function (e) {
            if (!e.lengthComputable) return;
            var pct = Math.round((e.loaded / e.total) * 100);
            setReplaceProgress(pct, '替换上传中 ' + pct + '%');
        });

        xhr.addEventListener('load', function () {
            state.replacing = false;
            var data = window.VS && VS.parseJsonResponse
                ? VS.parseJsonResponse(xhr.responseText)
                : null;
            if (!data) {
                setReplaceProgress(100, '响应异常');
                showFlash('替换失败', 'error');
                if (fileReplaceInput) fileReplaceInput.value = '';
                return;
            }

            if (data.code === 1) {
                setReplaceProgress(100, '替换完成');
                applyPayload(data);
                var updated = findFile(state.previewFile.id);
                if (updated) {
                    openFilePreview(updated);
                } else {
                    closeFilePreview();
                }
                showFlash(data.msg || '文件已替换', 'success');
                window.setTimeout(resetReplaceProgress, 2000);
            } else {
                setReplaceProgress(100, data.msg || '替换失败');
                showFlash(data.msg || '替换失败', 'error');
                if (fileReplaceInput) fileReplaceInput.value = '';
            }
        });

        xhr.addEventListener('error', function () {
            state.replacing = false;
            setReplaceProgress(100, '网络异常');
            showFlash('替换失败', 'error');
            if (fileReplaceInput) fileReplaceInput.value = '';
        });

        xhr.send(body);
    }

    function metaRow(label, value) {
        return '<div class="vs-file-preview__meta-row">'
            + '<span class="vs-file-preview__meta-label">' + escapeHtml(label) + '</span>'
            + '<span class="vs-file-preview__meta-value">' + escapeHtml(value == null ? '—' : value) + '</span>'
            + '</div>';
    }

    function closeFilePreview() {
        if (!filePreview) return;
        destroyFileViewer();
        filePreview.classList.remove('is-open');
        filePreview.hidden = true;
        filePreview.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('vs-modal-open');
        state.previewFile = null;
        resetReplaceProgress();
    }

    function copyPreviewLink() {
        var url = filePreviewLink ? filePreviewLink.value : '';
        if (!url) {
            showFlash('暂无分享链接', 'error');
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function () {
                showFlash('链接已复制到剪贴板', 'success');
            }).catch(fallbackCopy);
        } else {
            fallbackCopy();
        }

        function fallbackCopy() {
            if (!filePreviewLink) return;
            filePreviewLink.select();
            filePreviewLink.setSelectionRange(0, 99999);
            try {
                document.execCommand('copy');
                showFlash('链接已复制到剪贴板', 'success');
            } catch (e) {
                showFlash('复制失败，请手动选择链接', 'error');
            }
        }
    }

    function handleActionResponse(data, options) {
        options = options || {};
        if (data.code === 1) {
            if (options.deletedFileId && state.previewFile
                && Number(state.previewFile.id) === Number(options.deletedFileId)) {
                closeFilePreview();
            }
            applyPayload(data);
            showFlash(data.msg || '操作成功', 'success');
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
            opt.textContent = item.key + '. ' + item.name;
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

    function openRenameModal(id, name) {
        if (!renameModal) return;
        renameTargetId.value = id;
        renameInput.value = name || '';
        renameModal.hidden = false;
        renameModal.classList.add('is-open');
        renameInput.focus();
    }

    function closeRenameModal() {
        if (!renameModal) return;
        renameModal.hidden = true;
        renameModal.classList.remove('is-open');
        if (renameForm) renameForm.reset();
    }

    function render() {
        renderBreadcrumb();
        renderMeta();
        renderContent();
        renderViewButtons();
    }

    document.getElementById('btnNewFolder').addEventListener('click', openFolderModal);

    if (folderModal) {
        folderModal.querySelectorAll('[data-close-modal]').forEach(function (btn) {
            btn.addEventListener('click', closeFolderModal);
        });
    }

    if (renameModal) {
        renameModal.querySelectorAll('[data-close-rename]').forEach(function (btn) {
            btn.addEventListener('click', closeRenameModal);
        });
    }

    if (filePreview) {
        filePreview.querySelectorAll('[data-close-preview]').forEach(function (btn) {
            btn.addEventListener('click', closeFilePreview);
        });
    }

    if (filePreviewCopy) {
        filePreviewCopy.addEventListener('click', copyPreviewLink);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (filePreview && filePreview.classList.contains('is-open')) {
                closeFilePreview();
            } else if (folderModal && folderModal.classList.contains('is-open')) {
                closeFolderModal();
            } else if (renameModal && renameModal.classList.contains('is-open')) {
                closeRenameModal();
            }
        }
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

    if (renameForm) {
        renameForm.addEventListener('submit', function (e) {
            e.preventDefault();
            post('rename_folder', {
                target_id: renameTargetId.value,
                name: renameInput.value
            }).then(function (data) {
                if (data.code === 1) {
                    closeRenameModal();
                    handleActionResponse(data);
                } else {
                    showFlash(data.msg || '重命名失败', 'error');
                }
            }).catch(function () {
                showFlash('网络异常', 'error');
            });
        });
    }

    if (uploadInput) {
        uploadInput.addEventListener('change', function () {
            if (!uploadInput.files || !uploadInput.files.length) return;
            uploadFiles(uploadInput.files);
        });
    }

    if (fileReplaceInput) {
        fileReplaceInput.addEventListener('change', function () {
            if (!fileReplaceInput.files || !fileReplaceInput.files.length) return;
            replaceCurrentFile(fileReplaceInput.files[0]);
        });
    }

    function isFileDrag(e) {
        if (!e.dataTransfer || !e.dataTransfer.types) return false;
        var types = e.dataTransfer.types;
        if (typeof types.contains === 'function') {
            return types.contains('Files');
        }
        for (var i = 0; i < types.length; i++) {
            if (types[i] === 'Files') return true;
        }
        return false;
    }

    function clearDragState() {
        state.dragDepth = 0;
        root.classList.remove('is-dragover');
    }

    ['dragenter', 'dragover'].forEach(function (evtName) {
        root.addEventListener(evtName, function (e) {
            if (!root.classList.contains('can-upload') || !isFileDrag(e)) return;
            e.preventDefault();
            e.stopPropagation();
            if (evtName === 'dragenter') {
                state.dragDepth += 1;
            }
            if (state.dragDepth > 0) {
                root.classList.add('is-dragover');
            }
        });
    });

    root.addEventListener('dragleave', function (e) {
        if (!root.classList.contains('can-upload')) return;
        e.preventDefault();
        e.stopPropagation();
        state.dragDepth = Math.max(0, state.dragDepth - 1);
        if (state.dragDepth === 0) {
            root.classList.remove('is-dragover');
        }
    });

    root.addEventListener('drop', function (e) {
        if (!root.classList.contains('can-upload')) return;
        e.preventDefault();
        e.stopPropagation();
        clearDragState();
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
            uploadFiles(e.dataTransfer.files);
        }
    });

    window.addEventListener('dragend', clearDragState);
    window.addEventListener('drop', clearDragState);

    window.vsFilesOnUploadComplete = function (folderId, data) {
        if (Number(folderId) !== Number(state.folderId) || !data) {
            return;
        }
        if (data.code === 1) {
            applyPayload(data);
        }
    };
})();
