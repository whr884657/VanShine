/**
 * 文件：assets/js/files.js
 * 作用：后台文件管理页（批量/拖拽上传、预览、重命名）
 * @version 1.0.53
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
    var shareCreateModal = document.getElementById('shareCreateModal');
    var shareCreateForm = document.getElementById('shareCreateForm');
    var filePreviewShareBox = document.getElementById('filePreviewShareBox');
    var filePreviewShareUrl = document.getElementById('filePreviewShareUrl');
    var filePreviewShareCreate = document.getElementById('filePreviewShareCreate');
    var filePreviewShareCopy = document.getElementById('filePreviewShareCopy');
    var filePreviewShareSingle = document.getElementById('filePreviewShareSingle');
    var filePreviewShareMulti = document.getElementById('filePreviewShareMulti');
    var filePreviewShareCount = document.getElementById('filePreviewShareCount');
    var filePreviewShareToggle = document.getElementById('filePreviewShareToggle');
    var filePreviewShareList = document.getElementById('filePreviewShareList');

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

    var FILE_CATEGORIES = {
        image:    { label: '图片', exts: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif', 'tiff', 'tif'] },
        video:    { label: '视频', exts: ['mp4', 'webm', 'ogv', 'mov', 'm4v', 'mkv', 'avi', 'flv', 'wmv', '3gp'] },
        audio:    { label: '音频', exts: ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'wma'] },
        pdf:      { label: 'PDF', exts: ['pdf'] },
        word:     { label: 'Word', exts: ['doc', 'docx'] },
        excel:    { label: 'Excel', exts: ['xls', 'xlsx', 'csv'] },
        markdown: { label: 'Markdown', exts: ['md', 'markdown'] },
        code:     { label: '代码', exts: ['html', 'htm', 'php', 'css', 'js', 'mjs', 'cjs', 'json', 'xml', 'txt', 'log', 'sql', 'yaml', 'yml', 'ini', 'sh', 'bat', 'vue', 'ts', 'tsx', 'jsx'] },
        archive:  { label: '压缩包', exts: ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz', 'tgz', 'cab', 'iso'] }
    };

    var EXT_LABELS = {
        mp3: 'MP3', mp4: 'MP4', wav: 'WAV', flac: 'FLAC', ogg: 'OGG',
        md: 'Markdown', markdown: 'Markdown',
        doc: 'Word', docx: 'Word', xls: 'Excel', xlsx: 'Excel', csv: 'CSV',
        zip: 'ZIP', rar: 'RAR', '7z': '7Z', pdf: 'PDF'
    };

    function getFileExt(file) {
        var name = (file && (file.stored_name || file.original_name)) || '';
        var dot = name.lastIndexOf('.');
        if (dot < 0) return '';
        return name.slice(dot + 1).toLowerCase();
    }

    function getFileCategory(file) {
        var ext = getFileExt(file);
        var key;
        for (key in FILE_CATEGORIES) {
            if (FILE_CATEGORIES[key].exts.indexOf(ext) >= 0) {
                return key;
            }
        }
        return 'other';
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

    function mgrActionButton(modifier, attrs, label, iconHtml) {
        var cls = 'vs-filemgr__action' + (modifier ? ' ' + modifier : '');
        return '<button type="button" class="' + cls + '" ' + attrs + ' title="' + escapeHtml(label) + '">'
            + (iconHtml || '')
            + '<span class="vs-filemgr__action-text">' + escapeHtml(label) + '</span></button>';
    }

    function shareFolderActionHtml(folderId) {
        return mgrActionButton(
            'vs-filemgr__action--share',
            'data-share-folder="' + folderId + '"',
            '分享',
            '<span class="vs-icon vs-icon--share" aria-hidden="true"></span>'
        );
    }

    function shareFileActionHtml(file) {
        var title = file.original_name || file.stored_name || '文件';
        return mgrActionButton(
            'vs-filemgr__action--share',
            'data-share-file="' + file.id + '" data-share-title="' + escapeHtml(title) + '"',
            '分享',
            '<span class="vs-icon vs-icon--share" aria-hidden="true"></span>'
        );
    }

    function renameFolderActionHtml(folderId) {
        return mgrActionButton(
            'vs-filemgr__action--edit',
            'data-rename-folder="' + folderId + '"',
            '重命名',
            '<span class="vs-filemgr__action-glyph" aria-hidden="true">✎</span>'
        );
    }

    function deleteFolderActionHtml(folderId) {
        return mgrActionButton(
            '',
            'data-delete-folder="' + folderId + '"',
            '删除',
            '<span class="vs-filemgr__action-glyph" aria-hidden="true">×</span>'
        );
    }

    function deleteFileActionHtml(fileId) {
        return mgrActionButton(
            '',
            'data-delete-file="' + fileId + '"',
            '删除',
            '<span class="vs-filemgr__action-glyph" aria-hidden="true">×</span>'
        );
    }

    function fileIconHtml(file, listMode) {
        var cat = getFileCategory(file);
        if (cat === 'image' && file.public_url) {
            return '<img src="' + escapeHtml(file.public_url) + '" alt="" class="vs-filemgr__thumb" loading="lazy">';
        }
        if (cat === 'video' && file.public_url) {
            return '<div class="vs-filemgr__vid-thumb">'
                + '<video src="' + escapeHtml(file.public_url) + '" preload="metadata" muted playsinline'
                + ' onloadeddata="if(this.duration>0.5)this.currentTime=0.5"></video>'
                + '<span class="vs-filemgr__vid-play" aria-hidden="true">▶</span></div>';
        }
        return '<span class="vs-ftype-icon vs-ftype-icon--' + cat + '" aria-hidden="true"></span>';
    }

    function fileTypeLabel(file) {
        var ext = getFileExt(file);
        if (EXT_LABELS[ext]) {
            return EXT_LABELS[ext];
        }
        var cat = getFileCategory(file);
        if (FILE_CATEGORIES[cat]) {
            return FILE_CATEGORIES[cat].label;
        }
        return ext ? ext.toUpperCase() : '文件';
    }

    function fileTypeDetail(file) {
        var label = fileTypeLabel(file);
        var mime = String(file.mime_type || '');
        if (!mime || mime === 'application/octet-stream') {
            return label;
        }
        return label + ' · ' + mime;
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
                html += shareFolderActionHtml(folder.id);
                html += renameFolderActionHtml(folder.id);
                html += deleteFolderActionHtml(folder.id);
                html += '</div></div>';
                return;
            }

            html += '<div class="vs-filemgr__item is-folder" data-folder-id="' + folder.id + '">';
            html += '<button type="button" class="vs-filemgr__open" data-folder-id="' + folder.id + '">';
            html += '<span class="vs-filemgr__folder-icon">📁</span>';
            html += '<span class="vs-filemgr__name">' + escapeHtml(folder.name) + '</span>';
            html += '</button>';
            html += '<div class="vs-filemgr__actions">';
            html += shareFolderActionHtml(folder.id);
            html += renameFolderActionHtml(folder.id);
            html += deleteFolderActionHtml(folder.id);
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
                html += shareFileActionHtml(file);
                html += deleteFileActionHtml(file.id);
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
            html += shareFileActionHtml(file);
            html += deleteFileActionHtml(file.id);
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

        contentEl.querySelectorAll('[data-share-file]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                e.preventDefault();
                var id = parseInt(btn.getAttribute('data-share-file'), 10);
                var title = btn.getAttribute('data-share-title') || '文件';
                openShareCreateModal('file', id, 0, title);
            });
        });

        contentEl.querySelectorAll('[data-share-folder]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var id = parseInt(btn.getAttribute('data-share-folder'), 10);
                var folder = null;
                state.folders.forEach(function (f) {
                    if (f.id === id) folder = f;
                });
                openShareCreateModal('folder', 0, id, folder ? folder.name : '文件夹');
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
        if (window.VsFilePreview) {
            VsFilePreview.destroy();
        }
        if (filePreviewViewerMount) {
            filePreviewViewerMount.innerHTML = '';
            filePreviewViewerMount.className = 'vs-file-preview__viewer-mount';
        }
        var shell = document.getElementById('filePreviewViewerShell');
        if (document.fullscreenElement && document.exitFullscreen) {
            document.exitFullscreen().catch(function () { /* ignore */ });
        }
        if (shell) {
            shell.classList.remove('is-expanded', 'is-compact-audio', 'is-fit-video', 'is-fit-image', 'is-doc-view', 'is-fill');
        }
        clearViewerState();
    }

    function mountFileViewer(file) {
        if (!filePreviewViewerMount || !file) {
            setViewerState('暂无预览内容', true);
            return;
        }

        if (!window.VsFilePreview) {
            setViewerState('预览组件未加载', true);
            return;
        }

        destroyFileViewer();
        setViewerState('正在加载预览…', false);

        VsFilePreview.mount(filePreviewViewerMount, file, {
            onReady: function () {
                clearViewerState();
            },
            onError: function () {
                setViewerState('预览加载失败，可尝试新窗口打开或下载', true);
            }
        });
    }

    function openFilePreview(file) {
        if (!filePreview || !file) return;
        state.previewFile = file;

        var displayName = file.stored_name || file.original_name || '文件';
        if (filePreviewTitle) filePreviewTitle.textContent = displayName;

        mountFileViewer(file);

        if (filePreviewMeta) {
            var origName = file.original_name || '—';
            var storedName = file.stored_name || '—';
            filePreviewMeta.innerHTML = ''
                + metaRow('原始文件名', origName + ' | ' + storedName)
                + metaRow('类型', fileTypeDetail(file) + ' | ' + formatSize(file.file_size))
                + metaRow('储存', storageLabelForFile());
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
        loadPreviewShares(file.id);
    }

    function loadPreviewShares(fileId) {
        if (!fileId) {
            renderPreviewShares([]);
            return;
        }
        var fd = new FormData();
        fd.append('action', 'list_file_shares');
        fd.append('file_id', String(fileId));
        fetch(window.location.pathname, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.code === 1 && res.shares) {
                    renderPreviewShares(res.shares);
                } else {
                    renderPreviewShares([]);
                }
            })
            .catch(function () {
                renderPreviewShares([]);
            });
    }

    function shareStatusText(row) {
        if (!row.enabled) return '已停用';
        if (row.expires_at && new Date(row.expires_at).getTime() < Date.now()) return '已过期';
        if (row.max_downloads > 0 && row.download_count >= row.max_downloads) return '次数已满';
        return '有效';
    }

    function renderPreviewShares(shares) {
        shares = shares || [];
        if (filePreviewShareSingle) filePreviewShareSingle.hidden = true;
        if (filePreviewShareMulti) filePreviewShareMulti.hidden = true;
        if (filePreviewShareList) {
            filePreviewShareList.hidden = true;
            filePreviewShareList.innerHTML = '';
        }

        if (shares.length === 0) {
            return;
        }

        if (shares.length === 1) {
            if (filePreviewShareSingle) filePreviewShareSingle.hidden = false;
            if (filePreviewShareUrl) filePreviewShareUrl.value = shares[0].share_url || '';
            return;
        }

        if (filePreviewShareMulti) filePreviewShareMulti.hidden = false;
        if (filePreviewShareCount) filePreviewShareCount.textContent = String(shares.length);
        if (filePreviewShareList) {
            filePreviewShareList.innerHTML = shares.map(function (row) {
                var pwd = row.has_password ? '需密码' : '公开';
                var exp = row.expires_at ? row.expires_at : '永不过期';
                return '<li class="vs-file-preview__share-item">'
                    + '<input type="text" class="vs-file-preview__link-input" readonly value="' + escapeHtml(row.share_url) + '">'
                    + '<span class="vs-file-preview__share-meta">' + escapeHtml(pwd) + ' · ' + escapeHtml(exp) + ' · ' + escapeHtml(shareStatusText(row)) + '</span>'
                    + '<button type="button" class="vs-btn vs-btn--default vs-btn--rect vs-btn--xs" data-copy-share-url="' + escapeHtml(row.share_url) + '">复制</button>'
                    + '</li>';
            }).join('');
        }
    }

    function resetPreviewShareBox() {
        renderPreviewShares([]);
    }

    function openShareCreateModal(type, fileId, folderId, defaultTitle) {
        if (!shareCreateModal) return;
        document.getElementById('shareCreateType').value = type;
        document.getElementById('shareCreateFileId').value = fileId ? String(fileId) : '';
        document.getElementById('shareCreateFolderId').value = folderId ? String(folderId) : '';
        document.getElementById('shareCreateTitleInput').value = defaultTitle || '';
        document.getElementById('shareCreatePassword').value = '';
        document.getElementById('shareCreateMaxDl').value = '0';
        document.getElementById('shareCreatePreview').checked = true;
        document.getElementById('shareCreateTitle').textContent = type === 'folder' ? '分享文件夹' : '分享文件';
        if (window.VsDatetime) {
            VsDatetime.clear('#shareCreateExpiresWrap');
        }
        shareCreateModal.hidden = false;
        shareCreateModal.classList.add('is-open');
    }

    function closeShareCreateModal() {
        if (!shareCreateModal) return;
        shareCreateModal.hidden = true;
        shareCreateModal.classList.remove('is-open');
        if (shareCreateForm) shareCreateForm.reset();
    }

    function submitShareCreate(e) {
        if (e) e.preventDefault();
        if (!shareCreateForm) return;
        var fd = new FormData(shareCreateForm);
        fd.append('action', 'create_share');
        fd.append('allow_preview', document.getElementById('shareCreatePreview').checked ? '1' : '0');
        var exp = window.VsDatetime ? VsDatetime.getValue('#shareCreateExpiresWrap') : '';
        if (exp) {
            fd.set('expires_at', exp);
        }
        fetch(window.location.pathname, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.code !== 1 || !res.share) {
                    showFlash(res.msg || '创建失败', 'error');
                    return;
                }
                closeShareCreateModal();
                showFlash('分享短链接已生成', 'success');
                if (state.previewFile && res.share.share_type === 'file') {
                    loadPreviewShares(state.previewFile.id);
                }
                if (navigator.clipboard && res.share.share_url) {
                    navigator.clipboard.writeText(res.share.share_url).catch(function () { /* ignore */ });
                }
            })
            .catch(function () {
                showFlash('创建失败', 'error');
            });
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

    if (filePreviewShareCreate) {
        filePreviewShareCreate.addEventListener('click', function () {
            if (!state.previewFile) return;
            openShareCreateModal('file', state.previewFile.id, 0, state.previewFile.original_name || state.previewFile.stored_name);
        });
    }

    if (filePreviewShareCopy && filePreviewShareUrl) {
        filePreviewShareCopy.addEventListener('click', function () {
            var url = filePreviewShareUrl.value;
            if (!url) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    showFlash('短链接已复制', 'success');
                });
            }
        });
    }

    if (filePreviewShareList) {
        filePreviewShareList.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-copy-share-url]');
            if (!btn) return;
            var url = btn.getAttribute('data-copy-share-url');
            if (navigator.clipboard && url) {
                navigator.clipboard.writeText(url).then(function () {
                    showFlash('短链接已复制', 'success');
                });
            }
        });
    }

    if (filePreviewShareToggle && filePreviewShareList) {
        filePreviewShareToggle.addEventListener('click', function () {
            var open = filePreviewShareList.hidden;
            filePreviewShareList.hidden = !open;
            filePreviewShareToggle.textContent = open ? '收起' : '展开查看';
        });
    }

    if (shareCreateForm) {
        shareCreateForm.addEventListener('submit', submitShareCreate);
    }

    if (shareCreateModal) {
        shareCreateModal.querySelectorAll('[data-close-share-create]').forEach(function (btn) {
            btn.addEventListener('click', closeShareCreateModal);
        });
    }

    var filePreviewExpand = document.getElementById('filePreviewExpand');
    if (filePreviewExpand) {
        filePreviewExpand.addEventListener('click', function () {
            var shell = document.getElementById('filePreviewViewerShell');
            if (!shell) return;
            if (document.fullscreenElement) {
                document.exitFullscreen().catch(function () { /* ignore */ });
                filePreviewExpand.setAttribute('aria-pressed', 'false');
                return;
            }
            var req = shell.requestFullscreen || shell.webkitRequestFullscreen || shell.msRequestFullscreen;
            if (!req) return;
            req.call(shell).then(function () {
                filePreviewExpand.setAttribute('aria-pressed', 'true');
            }).catch(function () { /* ignore */ });
        });
        document.addEventListener('fullscreenchange', function () {
            if (!document.fullscreenElement && filePreviewExpand) {
                filePreviewExpand.setAttribute('aria-pressed', 'false');
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (document.fullscreenElement) {
                return;
            }
            if (filePreview && filePreview.classList.contains('is-open')) {
                closeFilePreview();
            } else if (folderModal && folderModal.classList.contains('is-open')) {
                closeFolderModal();
            } else if (renameModal && renameModal.classList.contains('is-open')) {
                closeRenameModal();
            } else if (shareCreateModal && shareCreateModal.classList.contains('is-open')) {
                closeShareCreateModal();
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
