/**
 * 文件：assets/js/share-public.js
 * 作用：公开分享页交互
 * @version 1.0.65
 */

(function () {
    'use strict';

    var files = window.VS_SHARE_FILES || [];
    var list = document.getElementById('shareFileList');
    var preview = document.getElementById('sharePreview');
    var mount = document.getElementById('sharePreviewMount');
    var state = document.getElementById('sharePreviewState');
    var hint = document.getElementById('sharePreviewHint');
    var layout = document.getElementById('shareLayout');

    if (!window.VsFilePreview) {
        return;
    }

    function findFile(id) {
        id = parseInt(id, 10);
        for (var i = 0; i < files.length; i++) {
            if (parseInt(files[i].id, 10) === id) {
                return files[i];
            }
        }
        return null;
    }

    function setActive(id) {
        if (!list) return;
        var items = list.querySelectorAll('.vs-share-files__item');
        items.forEach(function (el) {
            el.classList.toggle('is-active', parseInt(el.getAttribute('data-file-id'), 10) === id);
        });
    }

    function setHint(text) {
        if (hint) {
            hint.textContent = text;
        }
    }

    function revealPreviewPanel() {
        if (!preview || !layout) return;
        preview.hidden = false;
        layout.classList.add('has-preview');
    }

    function openPreview(file) {
        if (!file || !mount) return;
        revealPreviewPanel();
        if (state) {
            state.hidden = false;
            state.textContent = '正在加载预览…';
        }
        setHint('正在加载预览…');
        setActive(file.id);

        VsFilePreview.mount(mount, file).then(function () {
            if (state) state.hidden = true;
            setHint(file.original_name || file.stored_name || '预览中');
        }).catch(function () {
            if (state) {
                state.hidden = false;
                state.textContent = '无法预览此文件，请下载后查看';
            }
            setHint('无法预览，请下载后查看');
        });

        if (preview && window.matchMedia('(max-width: 991px)').matches) {
            preview.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    if (list) {
        list.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-preview-file]');
            if (!btn) return;
            var file = findFile(btn.getAttribute('data-preview-file'));
            if (file) openPreview(file);
        });
    }
})();
