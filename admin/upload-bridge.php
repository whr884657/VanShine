<?php
/**
 * 文件：admin/upload-bridge.php
 * 作用：后台跨页面文件上传桥接窗口（接收 postMessage 后在后台完成 XHR 上传）
 * @version 1.0.42
 */

require_once __DIR__ . '/init.php';

$filesUrl = vs_base_url() . '/admin/files.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>VanShine 上传</title>
</head>
<body>
<script>
(function () {
    'use strict';

    var CHANNEL = 'vs-upload-queue';
    var channel = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel(CHANNEL) : null;
    var filesBaseUrl = <?php echo json_encode($filesUrl, JSON_UNESCAPED_UNICODE); ?>;

    function parseJson(text) {
        var s = String(text || '').replace(/^\uFEFF/, '').trim();
        if (!s) return null;
        try {
            return JSON.parse(s);
        } catch (e1) {
            var start = s.indexOf('{');
            var end = s.lastIndexOf('}');
            if (start >= 0 && end > start) {
                try {
                    return JSON.parse(s.substring(start, end + 1));
                } catch (e2) {}
            }
        }
        return null;
    }

    function resolveUploadUrl(url) {
        if (!url) return filesBaseUrl;
        if (/^https?:\/\//i.test(url)) return url;
        var origin = window.location.origin;
        if (url.charAt(0) === '/') return origin + url;
        return origin + '/' + url;
    }

    function broadcast(data) {
        if (channel) {
            channel.postMessage(data);
        }
        try {
            var key = 'vs_upload_event';
            localStorage.setItem(key, JSON.stringify({ t: Date.now(), data: data }));
            localStorage.removeItem(key);
        } catch (e) {}
    }

    function uploadOne(payload) {
        var jobId = payload.jobId;
        var file = payload.file;
        var folderId = payload.folderId;
        var uploadUrl = resolveUploadUrl(payload.uploadUrl || filesBaseUrl);

        if (!file || !jobId) {
            return;
        }

        broadcast({ type: 'progress', jobId: jobId, percent: 0, status: 'uploading', message: '准备上传…' });

        var body = new FormData();
        body.append('action', 'upload');
        body.append('folder_id', String(folderId));
        body.append('file[]', file);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', uploadUrl, true);
        xhr.withCredentials = true;

        xhr.upload.addEventListener('progress', function (e) {
            if (!e.lengthComputable) {
                return;
            }
            var pct = Math.round((e.loaded / e.total) * 100);
            broadcast({
                type: 'progress',
                jobId: jobId,
                percent: pct,
                status: 'uploading',
                message: '上传中 ' + pct + '%'
            });
        });

        xhr.addEventListener('load', function () {
            var data = parseJson(xhr.responseText);
            if (!data) {
                broadcast({
                    type: 'done',
                    jobId: jobId,
                    ok: false,
                    status: 'error',
                    message: '响应异常'
                });
                return;
            }

            if (data.code === 1) {
                broadcast({
                    type: 'done',
                    jobId: jobId,
                    ok: true,
                    status: 'done',
                    message: '上传完成',
                    folderId: folderId,
                    payload: data
                });
            } else {
                broadcast({
                    type: 'done',
                    jobId: jobId,
                    ok: false,
                    status: 'error',
                    message: data.msg || '上传失败'
                });
            }
        });

        xhr.addEventListener('error', function () {
            broadcast({
                type: 'done',
                jobId: jobId,
                ok: false,
                status: 'error',
                message: '网络异常'
            });
        });

        xhr.send(body);
    }

    window.addEventListener('message', function (e) {
        if (!e.data || e.data.type !== 'vs-upload-start') {
            return;
        }
        if (e.origin !== window.location.origin) {
            return;
        }
        uploadOne(e.data);
    });

    if (window.opener) {
        try {
            window.opener.postMessage({ type: 'vs-upload-bridge-ready' }, window.location.origin);
        } catch (err) {}
    }
})();
</script>
</body>
</html>
