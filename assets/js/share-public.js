/**
 * 文件：assets/js/share-public.js
 * 作用：公开分享页交互
 * @version 1.0.53
 */

(function () {
    'use strict';

    var files = window.VS_SHARE_FILES || [];
    var list = document.getElementById('shareFileList');
    var preview = document.getElementById('sharePreview');
    var mount = document.getElementById('sharePreviewMount');
    var state = document.getElementById('sharePreviewState');
    var layout = document.getElementById('shareLayout');

    if (!list || !window.VsFilePreview) {
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
        var items = list.querySelectorAll('.vs-share-files__item');
        items.forEach(function (el) {
            el.classList.toggle('is-active', parseInt(el.getAttribute('data-file-id'), 10) === id);
        });
    }

    function openPreview(file) {
        if (!file || !mount) return;
        if (preview) preview.hidden = false;
        if (state) {
            state.hidden = false;
            state.textContent = '正在加载预览…';
        }
        setActive(file.id);

        if (layout && layout.getAttribute('data-share-type') === 'file') {
            layout.classList.add('is-single-file');
        }

        VsFilePreview.mount(mount, file).then(function () {
            if (state) state.hidden = true;
        }).catch(function () {
            if (state) {
                state.hidden = false;
                state.textContent = '无法预览此文件，请下载后查看';
            }
        });
    }

    list.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-preview-file]');
        if (!btn) return;
        var file = findFile(btn.getAttribute('data-preview-file'));
        if (file) openPreview(file);
    });

    if (layout && layout.getAttribute('data-allow-preview') === '1' && files.length === 1) {
        openPreview(files[0]);
    }
})();
