/**
 * 文件：assets/js/flyfish-viewer.js
 * 作用：Flyfish Viewer 懒加载与挂载封装（150+ 格式纯前端预览）
 * @version 1.0.43
 * @see https://doc.flyfish.dev/
 */

(function (global) {
    'use strict';

    var PINNED_VERSION = '2.1.3';
    var state = {
        controller: null,
        scriptPromise: null
    };

    function config() {
        return global.VS_FLYFISH_VIEWER || {};
    }

    function scriptUrl() {
        var c = config();
        if (c.scriptUrl) {
            return c.scriptUrl;
        }
        return 'https://cdn.jsdelivr.net/npm/@file-viewer/web-full@'
            + PINNED_VERSION
            + '/dist/flyfish-file-viewer-web-full.iife.js';
    }

    function isSameOrigin(url) {
        if (!url) {
            return false;
        }
        try {
            return new URL(url, global.location.origin).origin === global.location.origin;
        } catch (e) {
            return false;
        }
    }

    function buildPreviewUrl(file) {
        var c = config();
        var publicUrl = file.public_url || '';

        if (publicUrl && isSameOrigin(publicUrl)) {
            return publicUrl;
        }

        if (c.streamBase && file.id) {
            var base = String(c.streamBase);
            var sep = base.indexOf('?') >= 0 ? '&' : '?';
            return base + sep + 'id=' + encodeURIComponent(String(file.id));
        }

        return publicUrl;
    }

    function ensureScript() {
        if (global.FlyfishFileViewerWebFull) {
            return Promise.resolve(global.FlyfishFileViewerWebFull);
        }
        if (state.scriptPromise) {
            return state.scriptPromise;
        }

        state.scriptPromise = new Promise(function (resolve, reject) {
            var src = scriptUrl();
            var existing = document.querySelector('script[data-vs-flyfish="1"]');
            if (existing) {
                existing.addEventListener('load', function () {
                    if (global.FlyfishFileViewerWebFull) {
                        resolve(global.FlyfishFileViewerWebFull);
                    } else {
                        reject(new Error('flyfish_load_failed'));
                    }
                });
                existing.addEventListener('error', function () {
                    reject(new Error('flyfish_script_error'));
                });
                return;
            }

            var script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.setAttribute('data-vs-flyfish', '1');
            script.onload = function () {
                if (global.FlyfishFileViewerWebFull) {
                    resolve(global.FlyfishFileViewerWebFull);
                } else {
                    reject(new Error('flyfish_load_failed'));
                }
            };
            script.onerror = function () {
                reject(new Error('flyfish_script_error'));
            };
            document.head.appendChild(script);
        });

        return state.scriptPromise;
    }

    global.VsFlyfishViewer = {
        /**
         * @param {HTMLElement} container
         * @param {object} file { id, stored_name, original_name, public_url, mime_type }
         * @param {object} callbacks { onReady, onError }
         * @returns {Promise}
         */
        mount: function (container, file, callbacks) {
            callbacks = callbacks || {};
            var self = this;

            if (!container || !file) {
                return Promise.reject(new Error('invalid_mount'));
            }

            return ensureScript().then(function (api) {
                self.destroy();

                var fileName = file.stored_name || file.original_name || 'file.bin';
                var previewUrl = buildPreviewUrl(file);

                if (!previewUrl) {
                    throw new Error('missing_preview_url');
                }

                state.controller = api.mountViewer(container, {
                    url: previewUrl,
                    name: fileName,
                    options: {
                        theme: 'light',
                        locale: 'zh-CN',
                        rendererMode: 'replace',
                        toolbar: {
                            position: 'bottom-right'
                        }
                    },
                    onEvent: function (event) {
                        if (!event) {
                            return;
                        }
                        if (event.type === 'load-complete' && callbacks.onReady) {
                            callbacks.onReady(event.payload);
                        }
                        if ((event.type === 'error' || event.type === 'viewer-error') && callbacks.onError) {
                            callbacks.onError(event.payload || event);
                        }
                    }
                });

                return state.controller;
            }).catch(function (err) {
                if (callbacks.onError) {
                    callbacks.onError(err);
                }
                throw err;
            });
        },

        destroy: function () {
            if (state.controller && typeof state.controller.destroy === 'function') {
                try {
                    state.controller.destroy();
                } catch (e) {}
            }
            state.controller = null;
        },

        reload: function () {
            if (state.controller && typeof state.controller.reload === 'function') {
                return state.controller.reload();
            }
            return Promise.resolve();
        }
    };
})(window);
