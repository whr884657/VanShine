/**
 * 文件：assets/js/file-preview.js
 * 作用：文件在线预览（本地资源 + 原生媒体）
 * @version 1.0.53
 */

(function () {
    'use strict';

    var loadedScripts = {};
    var loadedStyles = {};
    var activeMount = null;

    var ARCHIVE_EXT = ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz', 'tgz', 'tbz', 'tbz2', 'lz', 'lzma', 'cab', 'iso'];
    var IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif'];
    var AUDIO_EXT = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus'];
    var VIDEO_EXT = ['mp4', 'webm', 'ogv', 'mov', 'm4v', 'mkv'];
    var WORD_EXT = ['doc', 'docx'];
    var EXCEL_EXT = ['xls', 'xlsx', 'csv'];
    var MARKDOWN_EXT = ['md', 'markdown'];
    var CODE_EXT = ['html', 'htm', 'php', 'css', 'js', 'mjs', 'cjs', 'json', 'xml', 'txt', 'log', 'sql', 'yaml', 'yml', 'ini', 'sh', 'bat', 'vue', 'ts', 'tsx', 'jsx'];

    var ICON_MUSIC = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 3v10.55A4 4 0 1 0 14 17V7h4V3h-6z"/></svg>';
    var ICON_PLAY = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M8 5v14l11-7z"/></svg>';
    var ICON_PAUSE = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M6 5h4v14H6V5zm8 0h4v14h-4V5z"/></svg>';

    var ICON_VOL = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3 10v4h4l5 5V5L7 10H3zm13.5 2c0-1.77-1.02-3.29-2.5-4.03v8.06c1.48-.74 2.5-2.26 2.5-4.03zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77 0-4.28-2.99-7.86-7-8.77z"/></svg>';
    var ICON_VOL_MUTE = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M16.5 12A4.5 4.5 0 0 0 14 7.97v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51A8.796 8.796 0 0 0 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06a8.99 8.99 0 0 0 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>';

    function fmtTime(sec) {
        sec = Math.max(0, Math.floor(sec || 0));
        var m = Math.floor(sec / 60);
        var s = sec % 60;
        return m + ':' + (s < 10 ? '0' : '') + s;
    }

    function applyShellLayout(container, mode) {
        if (!container) return;
        var shell = container.closest('.vs-file-preview__viewer-shell');
        if (!shell) return;
        shell.classList.remove('is-compact-audio', 'is-fit-video', 'is-fit-image', 'is-doc-view', 'is-fill');
        if (mode === 'audio') {
            shell.classList.add('is-compact-audio');
        } else if (mode === 'video') {
            shell.classList.add('is-fit-video');
        } else if (mode === 'image' || mode === 'unsupported') {
            shell.classList.add('is-fit-image');
        } else if (mode === 'word' || mode === 'pdf' || mode === 'excel' || mode === 'markdown') {
            shell.classList.add('is-doc-view');
        } else {
            shell.classList.add('is-doc-view');
        }
    }

    function bindProgressBar(track, fill, media, onSeek) {
        track.addEventListener('click', function (e) {
            if (!media.duration) return;
            var rect = track.getBoundingClientRect();
            var ratio = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            media.currentTime = ratio * media.duration;
            if (onSeek) onSeek();
        });
        media.addEventListener('timeupdate', function () {
            if (!media.duration) return;
            fill.style.width = ((media.currentTime / media.duration) * 100) + '%';
        });
    }

    function nativeControlsHtml() {
        return '<div class="vs-native-ctrl" data-native-ctrl="volume">'
            + '<button type="button" class="vs-native-ctrl__btn" data-native-volume-btn aria-label="音量" aria-expanded="false">' + ICON_VOL + '</button>'
            + '<div class="vs-native-popover vs-native-popover--up" data-native-volume-panel hidden>'
            + '<input type="range" class="vs-native-popover__vol" data-media-volume min="0" max="1" step="0.05" value="1">'
            + '</div></div>'
            + '<div class="vs-native-ctrl" data-native-ctrl="speed">'
            + '<button type="button" class="vs-native-ctrl__btn vs-native-ctrl__speed" data-native-speed-btn aria-label="播放速度" aria-expanded="false">1×</button>'
            + '<div class="vs-native-popover vs-native-popover--up" data-native-speed-panel hidden>'
            + '<button type="button" class="vs-native-popover__opt is-active" data-rate="0.5">0.5×</button>'
            + '<button type="button" class="vs-native-popover__opt" data-rate="0.75">0.75×</button>'
            + '<button type="button" class="vs-native-popover__opt" data-rate="1">正常</button>'
            + '<button type="button" class="vs-native-popover__opt" data-rate="1.25">1.25×</button>'
            + '<button type="button" class="vs-native-popover__opt" data-rate="1.5">1.5×</button>'
            + '<button type="button" class="vs-native-popover__opt" data-rate="2">2×</button>'
            + '</div></div>';
    }

    function closeAllNativePopovers(except) {
        document.querySelectorAll('[data-native-volume-panel], [data-native-speed-panel]').forEach(function (panel) {
            if (except && (panel === except || except.contains(panel))) return;
            panel.hidden = true;
            var btn = panel.parentElement && panel.parentElement.querySelector('[aria-expanded]');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    }

    function bindNativeMediaControls(media, wrap) {
        ensureNativePopoverClose();
        media.volume = 1;
        media.muted = false;
        media.defaultMuted = false;

        var volBtn = wrap.querySelector('[data-native-volume-btn]');
        var volPanel = wrap.querySelector('[data-native-volume-panel]');
        var volInput = wrap.querySelector('[data-media-volume]');
        var speedBtn = wrap.querySelector('[data-native-speed-btn]');
        var speedPanel = wrap.querySelector('[data-native-speed-panel]');
        var speedOpts = wrap.querySelectorAll('[data-rate]');

        function syncVolIcon() {
            if (!volBtn) return;
            volBtn.innerHTML = (media.muted || media.volume <= 0) ? ICON_VOL_MUTE : ICON_VOL;
        }

        function setRate(rate) {
            media.playbackRate = rate;
            if (speedBtn) {
                speedBtn.textContent = (rate === 1 ? '1×' : rate + '×');
            }
            speedOpts.forEach(function (opt) {
                opt.classList.toggle('is-active', parseFloat(opt.getAttribute('data-rate')) === rate);
            });
        }

        if (volBtn && volPanel && volInput) {
            volInput.value = String(media.volume);
            syncVolIcon();
            volBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = volPanel.hidden;
                closeAllNativePopovers(wrap);
                volPanel.hidden = !open;
                volBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            volInput.addEventListener('input', function () {
                media.volume = parseFloat(volInput.value);
                media.muted = media.volume <= 0;
                syncVolIcon();
            });
        }

        if (speedBtn && speedPanel) {
            speedBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = speedPanel.hidden;
                closeAllNativePopovers(wrap);
                speedPanel.hidden = !open;
                speedBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            speedOpts.forEach(function (opt) {
                opt.addEventListener('click', function (e) {
                    e.stopPropagation();
                    setRate(parseFloat(opt.getAttribute('data-rate')) || 1);
                    speedPanel.hidden = true;
                    speedBtn.setAttribute('aria-expanded', 'false');
                });
            });
        }
    }

    var nativePopoverDocBound = false;

    function ensureNativePopoverClose() {
        if (nativePopoverDocBound) return;
        nativePopoverDocBound = true;
        document.addEventListener('click', function () {
            closeAllNativePopovers();
        });
    }

    function autoPlayMedia(media) {
        ensureNativePopoverClose();
        media.volume = 1;
        media.muted = false;
        var play = function () {
            var p = media.play();
            if (p && typeof p.catch === 'function') {
                p.catch(function () { /* 浏览器策略阻止时静默 */ });
            }
        };
        if (media.readyState >= 2) {
            play();
        } else {
            media.addEventListener('canplay', function onReady() {
                media.removeEventListener('canplay', onReady);
                play();
            });
        }
    }

    function fitDocxToWidth(box) {
        var wrapper = box.querySelector('.docx-wrapper');
        if (!wrapper) return;
        var containerW = box.clientWidth || box.parentElement.clientWidth;
        if (!containerW) return;
        var pages = wrapper.querySelectorAll('section.docx, .docx');
        pages.forEach(function (page) {
            var pw = page.scrollWidth || page.offsetWidth;
            if (pw > containerW && pw > 0) {
                var scale = (containerW - 8) / pw;
                page.style.transform = 'scale(' + scale + ')';
                page.style.transformOrigin = 'top center';
                var parent = page.parentElement;
                if (parent) {
                    parent.style.height = (page.offsetHeight * scale) + 'px';
                    parent.style.overflow = 'hidden';
                }
            }
        });
    }

    function assetBase() {
        var cfg = window.VS_FILE_PREVIEW || {};
        var base = cfg.assetBase || '/assets/vendor/preview/';
        if (base.charAt(base.length - 1) !== '/') {
            base += '/';
        }
        return base;
    }

    function asset(path) {
        return assetBase() + path.replace(/^\//, '');
    }

    function getExt(file) {
        var name = (file && (file.stored_name || file.original_name)) || '';
        var dot = name.lastIndexOf('.');
        if (dot < 0) return '';
        return name.slice(dot + 1).toLowerCase();
    }

    function getPreviewKind(file) {
        var ext = getExt(file);
        if (ext === 'pdf') return 'pdf';
        if (IMAGE_EXT.indexOf(ext) >= 0) return 'image';
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
        if (cfg.shareMode && cfg.shareStreams && file && file.id) {
            var key = String(file.id);
            return cfg.shareStreams[key] || cfg.shareStreams[file.id] || '';
        }
        var streamBase = cfg.streamBase || '';
        var publicUrl = file && file.public_url ? String(file.public_url) : '';
        if (publicUrl) {
            try {
                var u = new URL(publicUrl, window.location.origin);
                if (u.origin === window.location.origin) {
                    return publicUrl;
                }
            } catch (e) { /* ignore */ }
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
        if (loadedStyles[url]) {
            return loadedStyles[url];
        }
        loadedStyles[url] = new Promise(function (resolve, reject) {
            var l = document.createElement('link');
            l.rel = 'stylesheet';
            l.href = url;
            l.onload = function () { resolve(); };
            l.onerror = function () { reject(new Error('style')); };
            document.head.appendChild(l);
        });
        return loadedStyles[url];
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
        container.className = 'vs-file-preview__viewer-mount vs-preview-stage';
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str == null ? '' : String(str);
        return d.innerHTML;
    }

    function showIcon(container, file, message) {
        clearContainer(container);
        container.classList.add('vs-preview-stage--icon');
        applyShellLayout(container, 'unsupported');
        var ext = getExt(file);
        var kind = ARCHIVE_EXT.indexOf(ext) >= 0 ? 'archive' : 'file';
        container.innerHTML = '<div class="vs-preview-icon">'
            + '<span class="vs-preview-icon__glyph vs-preview-icon__glyph--' + kind + '" aria-hidden="true"></span>'
            + '<p class="vs-preview-icon__name">' + escapeHtml(file.stored_name || file.original_name || '') + '</p>'
            + '<p class="vs-preview-icon__tip">' + escapeHtml(message || '该格式不支持在线预览') + '</p>'
            + '</div>';
    }

    function renderImage(container, url, file) {
        clearContainer(container);
        container.classList.add('vs-preview-stage--image');
        applyShellLayout(container, 'image');
        var wrap = document.createElement('div');
        wrap.className = 'vs-preview-image-wrap';
        var img = document.createElement('img');
        img.className = 'vs-preview-image';
        img.alt = (file && file.original_name) || '';
        img.src = url;
        wrap.appendChild(img);
        container.appendChild(wrap);
        return Promise.resolve();
    }

    function renderPdf(container, url) {
        return loadScript(asset('pdfjs/pdf.min.js')).then(function () {
            if (!window.pdfjsLib) throw new Error('pdf');
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = asset('pdfjs/pdf.worker.min.js');
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
                                var viewport = page.getViewport({ scale: 1.2 });
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
        applyShellLayout(container, 'word');
        return loadScript(asset('docx/jszip.min.js'))
            .then(function () { return loadScript(asset('docx/docx-preview.min.js')); })
            .then(function () {
                return fetchArrayBuffer(url).then(function (buf) {
                    clearContainer(container);
                    container.classList.add('vs-preview-stage--doc');
                    applyShellLayout(container, 'word');
                    var box = document.createElement('div');
                    box.className = 'vs-preview-doc';
                    container.appendChild(box);
                    if (!window.docx || !window.docx.renderAsync) {
                        throw new Error('docx');
                    }
                    return window.docx.renderAsync(buf, box, null, {
                        className: 'docx',
                        inWrapper: true,
                        ignoreWidth: true,
                        ignoreHeight: false,
                        breakPages: true,
                        useBase64URL: true,
                        renderHeaders: true,
                        renderFooters: true,
                    }).then(function () {
                        requestAnimationFrame(function () {
                            fitDocxToWidth(box);
                        });
                    });
                });
            });
    }

    function renderExcel(container, url, ext) {
        if (ext === 'xls') {
            showIcon(container, { stored_name: 'xls' }, '旧版 .xls 请下载后本地查看，在线预览仅支持 .xlsx / .csv');
            return Promise.resolve();
        }
        return loadScript(asset('exceljs/exceljs.min.js')).then(function () {
            return fetchArrayBuffer(url).then(function (buf) {
                if (!window.ExcelJS) throw new Error('excel');
                var wb = new window.ExcelJS.Workbook();
                return wb.xlsx.load(buf).then(function () {
                    clearContainer(container);
                    var wrap = document.createElement('div');
                    wrap.className = 'vs-preview-sheet-wrap';
                    wb.eachSheet(function (worksheet) {
                        var section = document.createElement('div');
                        section.className = 'vs-preview-sheet';
                        var title = document.createElement('h4');
                        title.className = 'vs-preview-sheet__title';
                        title.textContent = worksheet.name;
                        section.appendChild(title);
                        var table = document.createElement('table');
                        table.className = 'vs-preview-sheet__table';
                        worksheet.eachRow({ includeEmpty: false }, function (row, rowNumber) {
                            var tr = document.createElement('tr');
                            row.eachCell({ includeEmpty: true }, function (cell) {
                                var td = document.createElement(rowNumber === 1 ? 'th' : 'td');
                                td.textContent = cell.text || '';
                                tr.appendChild(td);
                            });
                            if (tr.childNodes.length) table.appendChild(tr);
                        });
                        section.appendChild(table);
                        wrap.appendChild(section);
                    });
                    container.appendChild(wrap);
                });
            });
        });
    }

    function renderMarkdown(container, url) {
        return loadScript(asset('marked/marked.min.js')).then(function () {
            return fetchText(url).then(function (text) {
                clearContainer(container);
                var box = document.createElement('div');
                box.className = 'vs-preview-markdown';
                if (window.marked && window.marked.parse) {
                    if (window.marked.setOptions) {
                        window.marked.setOptions({ gfm: true, breaks: true });
                    }
                    box.innerHTML = window.marked.parse(text);
                } else {
                    box.textContent = text;
                }
                container.appendChild(box);
            });
        });
    }

    function renderCode(container, url, lang) {
        return loadScript(asset('highlight/highlight.min.js'))
            .then(function () { return loadStyle(asset('highlight/github.min.css')); })
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

    function renderAudio(container, url, file) {
        clearContainer(container);
        container.classList.add('vs-preview-stage--audio');
        applyShellLayout(container, 'audio');

        var name = (file && (file.stored_name || file.original_name)) || '音频';
        var wrap = document.createElement('div');
        wrap.className = 'vs-audio-bar';
        wrap.innerHTML = '<div class="vs-audio-bar__main">'
            + '<p class="vs-audio-bar__title">' + escapeHtml(name) + '</p>'
            + '<div class="vs-audio-bar__row">'
            + '<button type="button" class="vs-audio-bar__play" aria-label="播放">' + ICON_PLAY + '</button>'
            + '<div class="vs-audio-bar__track"><div class="vs-audio-bar__fill"></div></div>'
            + '<span class="vs-audio-bar__time">0:00 / 0:00</span>'
            + nativeControlsHtml()
            + '</div></div>';

        var audio = document.createElement('audio');
        audio.className = 'vs-audio-bar__media';
        audio.src = url;
        audio.preload = 'metadata';
        wrap.appendChild(audio);
        container.appendChild(wrap);

        var playBtn = wrap.querySelector('.vs-audio-bar__play');
        var fill = wrap.querySelector('.vs-audio-bar__fill');
        var track = wrap.querySelector('.vs-audio-bar__track');
        var timeEl = wrap.querySelector('.vs-audio-bar__time');

        function syncTime() {
            var total = audio.duration && isFinite(audio.duration) ? audio.duration : 0;
            timeEl.textContent = fmtTime(audio.currentTime) + ' / ' + (total ? fmtTime(total) : '--:--');
        }

        function togglePlay() {
            if (audio.paused) {
                audio.volume = 1;
                audio.muted = false;
                audio.play();
            } else {
                audio.pause();
            }
        }

        function setPlaying(playing) {
            wrap.classList.toggle('is-playing', playing);
            playBtn.innerHTML = playing ? ICON_PAUSE : ICON_PLAY;
            playBtn.setAttribute('aria-label', playing ? '暂停' : '播放');
        }

        playBtn.addEventListener('click', togglePlay);
        bindProgressBar(track, fill, audio, syncTime);

        audio.addEventListener('loadedmetadata', syncTime);
        audio.addEventListener('timeupdate', syncTime);
        audio.addEventListener('play', function () { setPlaying(true); });
        audio.addEventListener('pause', function () { setPlaying(false); });
        audio.addEventListener('ended', function () { setPlaying(false); });

        bindNativeMediaControls(audio, wrap);
        autoPlayMedia(audio);
    }

    function layoutVideoFrame(video, frame) {
        var vw = video.videoWidth;
        var vh = video.videoHeight;
        if (!vw || !vh) return;
        var maxW = frame.parentElement ? frame.parentElement.clientWidth : 640;
        if (!maxW) maxW = Math.min(window.innerWidth - 48, 720);
        var maxH = Math.min(window.innerHeight * 0.42, 420);
        var ratio = vw / vh;
        var w = maxW;
        var h = w / ratio;
        if (h > maxH) {
            h = maxH;
            w = h * ratio;
        }
        frame.style.width = Math.round(w) + 'px';
        frame.style.aspectRatio = vw + ' / ' + vh;
    }

    function renderVideo(container, url) {
        clearContainer(container);
        container.classList.add('vs-preview-stage--video');
        applyShellLayout(container, 'video');

        var wrap = document.createElement('div');
        wrap.className = 'vs-video-frame';
        wrap.innerHTML = '<video class="vs-video-frame__media" playsinline webkit-playsinline preload="auto"></video>'
            + '<div class="vs-video-frame__bar">'
            + '<button type="button" class="vs-video-frame__play" aria-label="播放">' + ICON_PLAY + '</button>'
            + '<div class="vs-video-frame__track"><div class="vs-video-frame__fill"></div></div>'
            + '<span class="vs-video-frame__time">0:00 / 0:00</span>'
            + nativeControlsHtml()
            + '</div>';

        var video = wrap.querySelector('video');
        video.src = url;
        var playBtn = wrap.querySelector('.vs-video-frame__play');
        var fill = wrap.querySelector('.vs-video-frame__fill');
        var track = wrap.querySelector('.vs-video-frame__track');
        var timeEl = wrap.querySelector('.vs-video-frame__time');

        function syncTime() {
            var total = video.duration && isFinite(video.duration) ? video.duration : 0;
            timeEl.textContent = fmtTime(video.currentTime) + ' / ' + (total ? fmtTime(total) : '--:--');
        }

        function togglePlay() {
            if (video.paused) {
                video.volume = 1;
                video.muted = false;
                video.play();
            } else {
                video.pause();
            }
        }

        function setPlaying(playing) {
            wrap.classList.toggle('is-playing', playing);
            playBtn.innerHTML = playing ? ICON_PAUSE : ICON_PLAY;
            playBtn.setAttribute('aria-label', playing ? '暂停' : '播放');
        }

        playBtn.addEventListener('click', togglePlay);
        video.addEventListener('click', togglePlay);
        bindProgressBar(track, fill, video, syncTime);

        video.addEventListener('loadedmetadata', function () {
            layoutVideoFrame(video, wrap);
            syncTime();
        });
        video.addEventListener('timeupdate', syncTime);
        video.addEventListener('play', function () { setPlaying(true); });
        video.addEventListener('pause', function () { setPlaying(false); });
        video.addEventListener('ended', function () { setPlaying(false); });

        bindNativeMediaControls(video, wrap);
        autoPlayMedia(video);

        container.appendChild(wrap);
        window.addEventListener('resize', function () {
            layoutVideoFrame(video, wrap);
        }, { passive: true });
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
            case 'image':
                chain = renderImage(container, url, file);
                break;
            case 'pdf':
                chain = renderPdf(container, url);
                break;
            case 'word':
                chain = renderWord(container, url, ext);
                break;
            case 'excel':
                chain = renderExcel(container, url, ext);
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
                renderAudio(container, url, file);
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
