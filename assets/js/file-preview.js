/**
 * 文件：assets/js/file-preview.js
 * 作用：文件在线预览（PDF / Office / 代码 / 音视频等）
 * @version 1.0.48
 */

(function () {
    'use strict';

    var CDN = 'https://cdn.jsdelivr.net/npm/';
    var loadedScripts = {};
    var activeMount = null;

    var ARCHIVE_EXT = ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz', 'tgz', 'tbz', 'tbz2', 'lz', 'lzma', 'cab', 'iso'];
    var AUDIO_EXT = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'webm'];
    var VIDEO_EXT = ['mp4', 'webm', 'ogv', 'mov', 'm4v', 'mkv'];
    var WORD_EXT = ['doc', 'docx'];
    var EXCEL_EXT = ['xls', 'xlsx', 'csv'];
    var MARKDOWN_EXT = ['md', 'markdown'];
    var CODE_EXT = ['html', 'htm', 'php', 'css', 'js', 'mjs', 'cjs', 'json', 'xml', 'txt', 'log', 'sql', 'yaml', 'yml', 'ini', 'sh', 'bat', 'vue', 'ts', 'tsx', 'jsx'];

    function getExt(file) {
        var name = (file && (file.stored_name || file.original_name)) || '';
        var dot = name.lastIndexOf('.');
        if (dot < 0) return '';
        return name.slice(dot + 1).toLowerCase();
    }

    function getPreviewKind(file) {
        var ext = getExt(file);
        if (ext === 'pdf') return 'pdf';
        if (WORD_EXT.indexOf(ext) >= 0) return 'word';
        if (EXCEL_EXT.indexOf(ext) >= 0) return 'excel';
        if (MARKDOWN_EXT.indexOf(ext) >= 0) return 'markdown';
        if (ext === 'html' || ext === 'htm') return 'html';
        if (ext === 'php') return 'php';
        if (ext === 'css') return 'css';
        if (ext === 'js' || ext === 'mjs' || ext === 'cjs') return 'js';
        if (AUDIO_EXT.indexOf(ext) >= 0) return 'audio';
        if (VIDEO_EXT.indexOf(ext) >= 0) return 'video';
        if (CODE_EXT.indexOf(ext) >= 0) return 'code';
        if (ARCHIVE_EXT.indexOf(ext) >= 0) return 'archive';
        return 'unsupported';
    }

    function isPreviewable(file) {
        var kind = getPreviewKind(file);
        return kind !== 'unsupported' && kind !== 'archive';
    }

    function buildPreviewUrl(file) {
        var cfg = window.VS_FILE_PREVIEW || {};
        var streamBase = cfg.streamBase || '';
        var publicUrl = file && file.public_url ? String(file.public_url) : '';
        if (publicUrl) {
            try {
                var u = new URL(publicUrl, window.location.origin);
                if (u.origin === window.location.origin) {
                    return publicUrl;
                }
            } catch (e) {
                /* ignore */
            }
        }
        if (streamBase && file && file.id) {
            return streamBase + (streamBase.indexOf('?') >= 0 ? '&' : '?') + 'id=' + encodeURIComponent(file.id);
        }
        return publicUrl || '';
    }

    function loadScript(url) {
        if (loadedScripts[url]) {
            return loadedScripts[url];
        }
        loadedScripts[url] = new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = url;
            s.async = true;
            s.onload = function () { resolve(); };
            s.onerror = function () { reject(new Error('script')); };
            document.head.appendChild(s);
        });
        return loadedScripts[url];
    }

    function loadStyle(url) {
        if (document.querySelector('link[data-vs-preview="' + url + '"]')) {
            return Promise.resolve();
        }
        return new Promise(function (resolve, reject) {
            var l = document.createElement('link');
            l.rel = 'stylesheet';
            l.href = url;
            l.setAttribute('data-vs-preview', url);
            l.onload = function () { resolve(); };
            l.onerror = function () { reject(new Error('style')); };
            document.head.appendChild(l);
        });
    }

    function fetchText(url) {
        return fetch(url, { credentials: 'same-origin' }).then(function (res) {
            if (!res.ok) throw new Error('fetch');
            return res.text();
        });
    }

    function fetchArrayBuffer(url) {
        return fetch(url, { credentials: 'same-origin' }).then(function (res) {
            if (!res.ok) throw new Error('fetch');
            return res.arrayBuffer();
        });
    }

    function clearContainer(container) {
        if (!container) return;
        container.innerHTML = '';
        container.className = 'vs-preview-stage';
    }

    function showIcon(container, file, message) {
        clearContainer(container);
        container.classList.add('vs-preview-stage--icon');
        var ext = getExt(file);
        var icon = ARCHIVE_EXT.indexOf(ext) >= 0 ? '🗜️' : '📄';
        container.innerHTML = '<div class="vs-preview-icon">'
            + '<div class="vs-preview-icon__glyph">' + icon + '</div>'
            + '<p class="vs-preview-icon__name">' + escapeHtml(file.stored_name || file.original_name || '') + '</p>'
            + '<p class="vs-preview-icon__tip">' + escapeHtml(message || '该格式不支持在线预览') + '</p>'
            + '</div>';
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str == null ? '' : String(str);
        return d.innerHTML;
    }

    function renderPdf(container, url) {
        return loadScript(CDN + 'pdfjs-dist@3.11.174/build/pdf.min.js').then(function () {
            if (!window.pdfjsLib) throw new Error('pdf');
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = CDN + 'pdfjs-dist@3.11.174/build/pdf.worker.min.js';
            clearContainer(container);
            var wrap = document.createElement('div');
            wrap.className = 'vs-preview-pdf';
            container.appendChild(wrap);
            return window.pdfjsLib.getDocument(url).promise.then(function (pdf) {
                var chain = Promise.resolve();
                for (var pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                    (function (num) {
                        chain = chain.then(function () {
                            return pdf.getPage(num).then(function (page) {
                                var scale = 1.2;
                                var viewport = page.getViewport({ scale: scale });
                                var canvas = document.createElement('canvas');
                                canvas.className = 'vs-preview-pdf__page';
                                var ctx = canvas.getContext('2d');
                                canvas.width = viewport.width;
                                canvas.height = viewport.height;
                                wrap.appendChild(canvas);
                                return page.render({ canvasContext: ctx, viewport: viewport }).promise;
                            });
                        });
                    })(pageNum);
                }
                return chain;
            });
        });
    }

    function renderWord(container, url, ext) {
        if (ext !== 'docx') {
            showIcon(container, { stored_name: 'word' }, '旧版 .doc 请下载后本地查看，在线预览仅支持 .docx');
            return Promise.resolve();
        }
        return loadScript(CDN + 'mammoth@1.6.0/mammoth.browser.min.js').then(function () {
            return fetchArrayBuffer(url).then(function (buf) {
                return window.mammoth.convertToHtml({ arrayBuffer: buf });
            }).then(function (result) {
                clearContainer(container);
                var box = document.createElement('div');
                box.className = 'vs-preview-doc';
                box.innerHTML = result.value;
                container.appendChild(box);
            });
        });
    }

    function renderExcel(container, url) {
        return loadScript(CDN + 'xlsx@0.18.5/dist/xlsx.full.min.js').then(function () {
            return fetchArrayBuffer(url).then(function (buf) {
                var wb = window.XLSX.read(buf, { type: 'array' });
                clearContainer(container);
                var wrap = document.createElement('div');
                wrap.className = 'vs-preview-sheet-wrap';
                wb.SheetNames.forEach(function (name) {
                    var html = window.XLSX.utils.sheet_to_html(wb.Sheets[name]);
                    var section = document.createElement('div');
                    section.className = 'vs-preview-sheet';
                    section.innerHTML = '<h4 class="vs-preview-sheet__title">' + escapeHtml(name) + '</h4>' + html;
                    wrap.appendChild(section);
                });
                container.appendChild(wrap);
            });
        });
    }

    function renderMarkdown(container, url) {
        return loadScript(CDN + 'marked@12.0.0/marked.min.js').then(function () {
            return fetchText(url).then(function (text) {
                clearContainer(container);
                var box = document.createElement('div');
                box.className = 'vs-preview-markdown';
                box.innerHTML = window.marked.parse(text);
                container.appendChild(box);
            });
        });
    }

    function renderCode(container, url, lang) {
        return loadScript(CDN + 'highlight.js@11.9.0/build/highlight.min.js')
            .then(function () {
                return loadStyle(CDN + 'highlight.js@11.9.0/styles/github.min.css');
            })
            .then(function () {
                return fetchText(url).then(function (text) {
                    clearContainer(container);
                    var pre = document.createElement('pre');
                    var code = document.createElement('code');
                    code.className = 'language-' + (lang || 'plaintext');
                    code.textContent = text;
                    pre.appendChild(code);
                    pre.className = 'vs-preview-code';
                    container.appendChild(pre);
                    if (window.hljs) {
                        window.hljs.highlightElement(code);
                    }
                });
            });
    }

    function renderHtml(container, url) {
        return fetchText(url).then(function (html) {
            clearContainer(container);
            var frame = document.createElement('iframe');
            frame.className = 'vs-preview-iframe';
            frame.setAttribute('sandbox', 'allow-same-origin');
            frame.srcdoc = html;
            container.appendChild(frame);
        });
    }

    function renderAudio(container, url) {
        clearContainer(container);
        container.classList.add('vs-preview-stage--audio');
        var wrap = document.createElement('div');
        wrap.className = 'vs-preview-audio';
        wrap.innerHTML = '<div class="vs-preview-audio__disc" aria-hidden="true">♪</div>'
            + '<div class="vs-preview-audio__waves" aria-hidden="true">'
            + '<span></span><span></span><span></span><span></span><span></span>'
            + '</div>'
            + '<div class="vs-preview-audio__controls">'
            + '<button type="button" class="vs-preview-media-btn" data-act="play">播放</button>'
            + '<div class="vs-preview-media-track"><div class="vs-preview-media-fill"></div></div>'
            + '<span class="vs-preview-media-time">00:00</span>'
            + '</div>';
        var audio = document.createElement('audio');
        audio.preload = 'metadata';
        audio.src = url;
        wrap.appendChild(audio);
        container.appendChild(wrap);

        var btn = wrap.querySelector('[data-act="play"]');
        var fill = wrap.querySelector('.vs-preview-media-fill');
        var timeEl = wrap.querySelector('.vs-preview-media-time');
        var waves = wrap.querySelector('.vs-preview-audio__waves');

        function fmt(sec) {
            sec = Math.floor(sec || 0);
            var m = Math.floor(sec / 60);
            var s = sec % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }

        audio.addEventListener('timeupdate', function () {
            if (!audio.duration) return;
            fill.style.width = ((audio.currentTime / audio.duration) * 100) + '%';
            timeEl.textContent = fmt(audio.currentTime) + ' / ' + fmt(audio.duration);
        });
        audio.addEventListener('play', function () {
            btn.textContent = '暂停';
            wrap.classList.add('is-playing');
        });
        audio.addEventListener('pause', function () {
            btn.textContent = '播放';
            wrap.classList.remove('is-playing');
        });
        btn.addEventListener('click', function () {
            if (audio.paused) audio.play();
            else audio.pause();
        });
        wrap.querySelector('.vs-preview-media-track').addEventListener('click', function (e) {
            if (!audio.duration) return;
            var rect = this.getBoundingClientRect();
            var ratio = (e.clientX - rect.left) / rect.width;
            audio.currentTime = Math.max(0, Math.min(1, ratio)) * audio.duration;
        });
        if (waves) waves.style.display = '';
    }

    function renderVideo(container, url) {
        clearContainer(container);
        container.classList.add('vs-preview-stage--video');
        var wrap = document.createElement('div');
        wrap.className = 'vs-preview-video';
        var screen = document.createElement('div');
        screen.className = 'vs-preview-video__screen';
        var video = document.createElement('video');
        video.className = 'vs-preview-video__el';
        video.src = url;
        video.playsInline = true;
        screen.appendChild(video);
        var controls = document.createElement('div');
        controls.className = 'vs-preview-video__controls';
        controls.innerHTML = '<button type="button" class="vs-preview-media-btn" data-act="play">播放</button>'
            + '<div class="vs-preview-media-track"><div class="vs-preview-media-fill"></div></div>'
            + '<span class="vs-preview-media-time">00:00</span>';
        wrap.appendChild(screen);
        wrap.appendChild(controls);
        container.appendChild(wrap);

        var btn = controls.querySelector('[data-act="play"]');
        var fill = controls.querySelector('.vs-preview-media-fill');
        var timeEl = controls.querySelector('.vs-preview-media-time');

        function fmt(sec) {
            sec = Math.floor(sec || 0);
            var m = Math.floor(sec / 60);
            var s = sec % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }

        video.addEventListener('timeupdate', function () {
            if (!video.duration) return;
            fill.style.width = ((video.currentTime / video.duration) * 100) + '%';
            timeEl.textContent = fmt(video.currentTime) + ' / ' + fmt(video.duration);
        });
        video.addEventListener('play', function () { btn.textContent = '暂停'; });
        video.addEventListener('pause', function () { btn.textContent = '播放'; });
        btn.addEventListener('click', function () {
            if (video.paused) video.play();
            else video.pause();
        });
        controls.querySelector('.vs-preview-media-track').addEventListener('click', function (e) {
            if (!video.duration) return;
            var rect = this.getBoundingClientRect();
            var ratio = (e.clientX - rect.left) / rect.width;
            video.currentTime = Math.max(0, Math.min(1, ratio)) * video.duration;
        });
        screen.addEventListener('click', function () {
            if (video.paused) video.play();
            else video.pause();
        });
    }

    function mount(container, file, options) {
        options = options || {};
        if (!container || !file) {
            return Promise.reject(new Error('invalid'));
        }

        destroy();
        activeMount = { container: container, file: file };

        var url = buildPreviewUrl(file);
        if (!url) {
            showIcon(container, file, '暂无可用预览地址');
            if (options.onError) options.onError();
            return Promise.resolve();
        }

        var kind = getPreviewKind(file);
        if (kind === 'unsupported' || kind === 'archive') {
            showIcon(container, file, kind === 'archive' ? '压缩包不支持在线预览' : '该格式不支持在线预览');
            if (options.onReady) options.onReady();
            return Promise.resolve();
        }

        var ext = getExt(file);
        var chain;

        switch (kind) {
            case 'pdf':
                chain = renderPdf(container, url);
                break;
            case 'word':
                chain = renderWord(container, url, ext);
                break;
            case 'excel':
                chain = renderExcel(container, url);
                break;
            case 'markdown':
                chain = renderMarkdown(container, url);
                break;
            case 'html':
                chain = renderHtml(container, url);
                break;
            case 'php':
                chain = renderCode(container, url, 'php');
                break;
            case 'css':
                chain = renderCode(container, url, 'css');
                break;
            case 'js':
                chain = renderCode(container, url, 'javascript');
                break;
            case 'code':
                chain = renderCode(container, url, ext);
                break;
            case 'audio':
                renderAudio(container, url);
                chain = Promise.resolve();
                break;
            case 'video':
                renderVideo(container, url);
                chain = Promise.resolve();
                break;
            default:
                showIcon(container, file, '该格式不支持在线预览');
                chain = Promise.resolve();
        }

        return chain.then(function () {
            if (options.onReady) options.onReady();
        }).catch(function () {
            showIcon(container, file, '预览加载失败，请尝试下载或新窗口打开');
            if (options.onError) options.onError();
        });
    }

    function destroy() {
        if (!activeMount || !activeMount.container) {
            return;
        }
        var media = activeMount.container.querySelector('audio, video');
        if (media) {
            try { media.pause(); } catch (e) { /* ignore */ }
        }
        clearContainer(activeMount.container);
        activeMount = null;
    }

    window.VsFilePreview = {
        mount: mount,
        destroy: destroy,
        isPreviewable: isPreviewable,
        getPreviewKind: getPreviewKind,
        buildPreviewUrl: buildPreviewUrl,
    };
})();
