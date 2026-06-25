/**
 * 文件：assets/js/account.js
 * 作用：账号设置页头像链接实时预览
 * @version 1.0.20
 */
(function () {
    'use strict';

    var preview = document.getElementById('avatarPreview');
    var input = document.getElementById('avatarUrlInput');
    if (!preview || !input) {
        return;
    }

    var fallback = preview.getAttribute('data-fallback') || preview.src;
    var timer = null;

    function updatePreview() {
        var url = (input.value || '').trim();
        if (url === '') {
            preview.src = fallback;
            return;
        }
        preview.src = url;
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(updatePreview, 200);
    });

    preview.addEventListener('error', function () {
        if (preview.src !== fallback) {
            preview.src = fallback;
        }
    });
})();
