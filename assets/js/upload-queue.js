/**
 * 文件：assets/js/upload-queue.js
 * 作用：全局上传进度浮层（可折叠、跨页面同步、桥接窗口后台上传）
 * @version 1.0.39
 */

(function (global) {
    'use strict';

    var CHANNEL = 'vs-upload-queue';
    var STORAGE_JOBS = 'vs_upload_jobs';
    var STORAGE_COLLAPSED = 'vs_upload_collapsed';

    var channel = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel(CHANNEL) : null;
    var bridgeWindow = null;

    function uid() {
        return 'job_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);
    }

    function loadJobs() {
        try {
            var raw = sessionStorage.getItem(STORAGE_JOBS);
            var list = raw ? JSON.parse(raw) : [];
            return Array.isArray(list) ? list : [];
        } catch (e) {
            return [];
        }
    }

    function saveJobs(jobs) {
        try {
            sessionStorage.setItem(STORAGE_JOBS, JSON.stringify(jobs));
        } catch (e) {}
    }

    var VsUploadQueue = {
        jobs: [],
        collapsed: sessionStorage.getItem(STORAGE_COLLAPSED) === '1',
        shell: null,
        listEl: null,
        activeCount: 0,

        init: function () {
            this.jobs = loadJobs();
            this.renderShell();
            this.syncFromJobs();
            this.bindStorageFallback();

            if (channel) {
                channel.onmessage = this.onChannelMessage.bind(this);
            }

            global.addEventListener('message', this.onWindowMessage.bind(this));
            global.addEventListener('beforeunload', this.onBeforeUnload.bind(this));
            global.addEventListener('storage', this.onStorageEvent.bind(this));
        },

        bindStorageFallback: function () {
            var self = this;
            if (channel) {
                return;
            }
            global.setInterval(function () {
                self.syncFromJobs();
            }, 800);
        },

        onStorageEvent: function (e) {
            if (e.key === 'vs_upload_event' && e.newValue) {
                try {
                    var wrapped = JSON.parse(e.newValue);
                    if (wrapped && wrapped.data) {
                        this.handleEvent(wrapped.data);
                    }
                } catch (err) {}
            }
            if (e.key === STORAGE_JOBS) {
                this.jobs = loadJobs();
                this.syncFromJobs();
            }
        },

        onChannelMessage: function (e) {
            this.handleEvent(e.data);
        },

        onWindowMessage: function (e) {
            if (!e.data) {
                return;
            }
            if (e.data.type === 'vs-upload-bridge-ready' && e.source) {
                bridgeWindow = e.source;
            }
        },

        handleEvent: function (data) {
            if (!data || !data.jobId) {
                return;
            }
            var job = this.findJob(data.jobId);
            if (!job) {
                return;
            }

            if (data.type === 'progress') {
                job.percent = data.percent || 0;
                job.status = data.status || 'uploading';
                job.message = data.message || '';
                this.updateJobEl(job);
                saveJobs(this.jobs);
                return;
            }

            if (data.type === 'done') {
                job.percent = 100;
                job.status = data.status || (data.ok ? 'done' : 'error');
                job.message = data.message || (data.ok ? '上传完成' : '上传失败');
                this.activeCount = Math.max(0, this.activeCount - 1);
                this.updateJobEl(job);
                saveJobs(this.jobs);
                this.scheduleRemove(job.id);

                if (data.ok && data.payload && typeof global.vsFilesOnUploadComplete === 'function') {
                    global.vsFilesOnUploadComplete(data.folderId, data.payload);
                }
            }
        },

        findJob: function (id) {
            for (var i = 0; i < this.jobs.length; i++) {
                if (this.jobs[i].id === id) {
                    return this.jobs[i];
                }
            }
            return null;
        },

        renderShell: function () {
            if (document.getElementById('vsUploadQueue')) {
                this.shell = document.getElementById('vsUploadQueue');
                this.listEl = this.shell.querySelector('.vs-upload-queue__list');
                return;
            }

            var shell = document.createElement('div');
            shell.id = 'vsUploadQueue';
            shell.className = 'vs-upload-queue' + (this.collapsed ? ' is-collapsed' : '');
            shell.hidden = true;
            shell.innerHTML = ''
                + '<div class="vs-upload-queue__panel">'
                + '<div class="vs-upload-queue__head">'
                + '<span class="vs-upload-queue__title">文件上传</span>'
                + '<span class="vs-upload-queue__badge" id="vsUploadBadge">0</span>'
                + '<button type="button" class="vs-upload-queue__toggle" id="vsUploadToggle" title="折叠/展开">−</button>'
                + '</div>'
                + '<div class="vs-upload-queue__list"></div>'
                + '</div>'
                + '<button type="button" class="vs-upload-queue__fab" id="vsUploadFab" title="展开上传进度" hidden>'
                + '<span>↑</span><em id="vsUploadFabCount">0</em>'
                + '</button>';

            document.body.appendChild(shell);
            this.shell = shell;
            this.listEl = shell.querySelector('.vs-upload-queue__list');

            var self = this;
            shell.querySelector('#vsUploadToggle').addEventListener('click', function () {
                self.setCollapsed(!self.collapsed);
            });
            shell.querySelector('#vsUploadFab').addEventListener('click', function () {
                self.setCollapsed(false);
            });
        },

        setCollapsed: function (collapsed) {
            this.collapsed = !!collapsed;
            sessionStorage.setItem(STORAGE_COLLAPSED, this.collapsed ? '1' : '0');
            if (!this.shell) {
                return;
            }
            this.shell.classList.toggle('is-collapsed', this.collapsed);
            this.updateFab();
        },

        updateFab: function () {
            if (!this.shell) {
                return;
            }
            var fab = this.shell.querySelector('#vsUploadFab');
            var active = this.countActive();
            if (this.collapsed && (active > 0 || this.hasRecentJobs())) {
                fab.hidden = false;
                var fabCount = this.shell.querySelector('#vsUploadFabCount');
                if (fabCount) {
                    fabCount.textContent = String(active || this.jobs.length);
                }
            } else {
                fab.hidden = true;
            }
        },

        hasRecentJobs: function () {
            return this.jobs.length > 0;
        },

        countActive: function () {
            var n = 0;
            for (var i = 0; i < this.jobs.length; i++) {
                if (this.jobs[i].status === 'uploading') {
                    n++;
                }
            }
            return n;
        },

        syncFromJobs: function () {
            if (!this.listEl) {
                return;
            }
            this.listEl.innerHTML = '';
            for (var i = 0; i < this.jobs.length; i++) {
                this.appendJobEl(this.jobs[i]);
            }
            this.updateVisibility();
        },

        updateVisibility: function () {
            if (!this.shell) {
                return;
            }
            var badge = this.shell.querySelector('#vsUploadBadge');
            var active = this.countActive();
            if (badge) {
                badge.textContent = String(active || this.jobs.length);
            }
            if (this.jobs.length > 0) {
                this.shell.hidden = false;
            } else if (active === 0) {
                this.shell.hidden = true;
            }
            this.updateFab();
        },

        enqueue: function (file, options) {
            options = options || {};
            if (!file) {
                return null;
            }

            var job = {
                id: uid(),
                name: file.name || 'file',
                percent: 0,
                status: 'uploading',
                message: '准备上传…',
                folderId: options.folderId || 0
            };

            this.jobs.push(job);
            this.activeCount++;
            saveJobs(this.jobs);
            this.renderShell();
            this.appendJobEl(job);
            this.updateVisibility();
            this.setCollapsed(this.collapsed);

            this.startUpload(job, file, options);
            return job.id;
        },

        appendJobEl: function (job) {
            if (!this.listEl) {
                return;
            }
            var el = document.createElement('div');
            el.className = 'vs-upload-item is-uploading';
            el.setAttribute('data-job-id', job.id);
            el.innerHTML = ''
                + '<div class="vs-upload-item__head">'
                + '<span class="vs-upload-item__name"></span>'
                + '<span class="vs-upload-item__status"></span>'
                + '</div>'
                + '<div class="vs-upload-item__bar"><span class="vs-upload-item__fill"></span></div>';
            this.listEl.appendChild(el);
            this.updateJobEl(job, el);
        },

        updateJobEl: function (job, el) {
            el = el || (this.listEl ? this.listEl.querySelector('[data-job-id="' + job.id + '"]') : null);
            if (!el) {
                return;
            }
            var nameEl = el.querySelector('.vs-upload-item__name');
            var statusEl = el.querySelector('.vs-upload-item__status');
            var fill = el.querySelector('.vs-upload-item__fill');
            if (nameEl) {
                nameEl.textContent = job.name;
            }
            if (statusEl) {
                statusEl.textContent = job.message || job.status;
            }
            if (fill) {
                fill.style.width = Math.max(0, Math.min(100, job.percent || 0)) + '%';
            }
            el.classList.remove('is-uploading', 'is-done', 'is-error');
            if (job.status === 'done') {
                el.classList.add('is-done');
            } else if (job.status === 'error') {
                el.classList.add('is-error');
            } else {
                el.classList.add('is-uploading');
            }
            this.updateVisibility();
        },

        scheduleRemove: function (jobId) {
            var self = this;
            global.setTimeout(function () {
                self.jobs = self.jobs.filter(function (j) { return j.id !== jobId; });
                saveJobs(self.jobs);
                if (self.listEl) {
                    var el = self.listEl.querySelector('[data-job-id="' + jobId + '"]');
                    if (el && el.parentNode) {
                        el.parentNode.removeChild(el);
                    }
                }
                self.updateVisibility();
            }, 3200);
        },

        getBridge: function () {
            var base = global.VS_BASE_URL || '';
            var bridgeUrl = base + '/admin/upload-bridge.php';

            if (bridgeWindow && !bridgeWindow.closed) {
                return bridgeWindow;
            }

            try {
                bridgeWindow = global.open(bridgeUrl, 'vsUploadBridge', 'width=1,height=1,left=-2400,top=-2400');
            } catch (e) {
                bridgeWindow = null;
            }

            return bridgeWindow;
        },

        startUpload: function (job, file, options) {
            var bridge = this.getBridge();
            var uploadUrl = options.uploadUrl || (global.location.pathname + global.location.search);
            var payload = {
                type: 'vs-upload-start',
                jobId: job.id,
                folderId: options.folderId || 0,
                uploadUrl: uploadUrl,
                file: file
            };

            if (bridge) {
                var self = this;
                var send = function () {
                    try {
                        bridge.postMessage(payload, global.location.origin);
                    } catch (err) {
                        self.uploadLocal(job, file, options);
                    }
                };
                if (bridge.document && bridge.document.readyState === 'complete') {
                    send();
                } else {
                    global.setTimeout(send, 120);
                }
                return;
            }

            this.uploadLocal(job, file, options);
        },

        uploadLocal: function (job, file, options) {
            var self = this;
            var body = new FormData();
            body.append('action', 'upload');
            body.append('folder_id', String(options.folderId || 0));
            body.append('file[]', file);

            var xhr = new global.XMLHttpRequest();
            xhr.open('POST', options.uploadUrl || (global.location.pathname + global.location.search), true);
            xhr.withCredentials = true;

            xhr.upload.addEventListener('progress', function (e) {
                if (!e.lengthComputable) {
                    return;
                }
                var pct = Math.round((e.loaded / e.total) * 100);
                self.handleEvent({
                    type: 'progress',
                    jobId: job.id,
                    percent: pct,
                    status: 'uploading',
                    message: '上传中 ' + pct + '%'
                });
            });

            xhr.addEventListener('load', function () {
                var data = null;
                try {
                    data = JSON.parse(xhr.responseText || '{}');
                } catch (err) {
                    self.handleEvent({
                        type: 'done',
                        jobId: job.id,
                        ok: false,
                        status: 'error',
                        message: '响应异常'
                    });
                    return;
                }

                if (data.code === 1) {
                    self.handleEvent({
                        type: 'done',
                        jobId: job.id,
                        ok: true,
                        status: 'done',
                        message: '上传完成',
                        folderId: options.folderId,
                        payload: data
                    });
                } else {
                    self.handleEvent({
                        type: 'done',
                        jobId: job.id,
                        ok: false,
                        status: 'error',
                        message: data.msg || '上传失败'
                    });
                }
            });

            xhr.addEventListener('error', function () {
                self.handleEvent({
                    type: 'done',
                    jobId: job.id,
                    ok: false,
                    status: 'error',
                    message: '网络异常'
                });
            });

            xhr.send(body);
        },

        onBeforeUnload: function (e) {
            if (this.countActive() > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        }
    };

    global.VsUploadQueue = VsUploadQueue;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            VsUploadQueue.init();
        });
    } else {
        VsUploadQueue.init();
    }
})(window);
