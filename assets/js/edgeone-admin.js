/**
 * EdgeOne 后台交互
 */
(function () {
    'use strict';

    function apiUrl() {
        return window.VS_EDGEONE_API || '';
    }

    function toast(msg, type) {
        if (window.VsToast) {
            VsToast.show(msg, type || 'success');
            return;
        }
        alert(msg);
    }

    function postFormData(body) {
        return fetch(apiUrl(), {
            method: 'POST',
            body: body,
            credentials: 'same-origin'
        }).then(function (res) {
            return res.text();
        }).then(function (text) {
            var data = window.VS && VS.parseJsonResponse ? VS.parseJsonResponse(text) : null;
            if (!data) throw new Error('invalid_json');
            return data;
        });
    }

    function bindApiForms() {
        document.querySelectorAll('.vs-edgeone-api-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var action = form.getAttribute('data-action');
                var body = new FormData(form);
                body.set('action', action);
                var btn = form.querySelector('[type="submit"]');
                if (btn) btn.disabled = true;
                postFormData(body)
                    .then(function (data) {
                        if (data.code === 1) {
                            toast(data.msg || '操作成功', 'success');
                            setTimeout(function () { window.location.reload(); }, 600);
                        } else {
                            toast(data.msg || '操作失败', 'error');
                        }
                    })
                    .catch(function () {
                        toast('网络异常', 'error');
                    })
                    .finally(function () {
                        if (btn) btn.disabled = false;
                    });
            });
        });
    }

    function bindZoneForm() {
        var form = document.getElementById('edgeoneZoneForm');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var body = new FormData(form);
            postFormData(body)
                .then(function (data) {
                    if (data.code === 1) {
                        toast(data.msg || '已切换', 'success');
                        window.location.reload();
                    } else {
                        toast(data.msg || '切换失败', 'error');
                    }
                })
                .catch(function () {
                    toast('网络异常', 'error');
                });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindApiForms();
        bindZoneForm();
        bindCharts();
    });

    function formatChartValue(value, unit) {
        var n = Number(value) || 0;
        if (unit === 'bytes') {
            var units = ['B', 'KB', 'MB', 'GB', 'TB'];
            var i = 0;
            while (n >= 1024 && i < units.length - 1) {
                n /= 1024;
                i++;
            }
            return (i === 0 ? Math.round(n) : n.toFixed(2)) + ' ' + units[i];
        }
        if (unit === 'bps') {
            if (n >= 1e9) return (n / 1e9).toFixed(2) + ' Gbps';
            if (n >= 1e6) return (n / 1e6).toFixed(2) + ' Mbps';
            if (n >= 1e3) return (n / 1e3).toFixed(2) + ' Kbps';
            return Math.round(n) + ' bps';
        }
        if (unit === 'ms') return n.toFixed(2) + ' ms';
        return n.toLocaleString();
    }

    function bindCharts() {
        document.querySelectorAll('.vs-edgeone-chart-data').forEach(function (node) {
            var targetId = node.getAttribute('data-target');
            var unit = node.getAttribute('data-unit') || 'number';
            var canvas = document.getElementById(targetId);
            if (!canvas) return;
            var points;
            try {
                points = JSON.parse(node.textContent || '[]');
            } catch (e) {
                return;
            }
            drawLineChart(canvas, points, unit);
        });
    }

    function drawLineChart(canvas, points, unit) {
        if (!points || points.length === 0) return;

        var wrap = canvas.parentElement;
        var width = wrap ? wrap.clientWidth : 900;
        if (width < 320) width = 320;
        var height = 260;
        var padL = 56;
        var padR = 16;
        var padT = 20;
        var padB = 36;
        var dpr = window.devicePixelRatio || 1;

        canvas.width = Math.floor(width * dpr);
        canvas.height = Math.floor(height * dpr);
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';

        var ctx = canvas.getContext('2d');
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.clearRect(0, 0, width, height);

        var values = points.map(function (p) { return Number(p.value) || 0; });
        var max = Math.max.apply(null, values);
        if (max <= 0) max = 1;

        var plotW = width - padL - padR;
        var plotH = height - padT - padB;

        ctx.strokeStyle = '#eef0f3';
        ctx.lineWidth = 1;
        for (var g = 0; g <= 4; g++) {
            var gy = padT + (plotH / 4) * g;
            ctx.beginPath();
            ctx.moveTo(padL, gy);
            ctx.lineTo(width - padR, gy);
            ctx.stroke();
        }

        ctx.fillStyle = '#888';
        ctx.font = '11px sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText(formatChartValue(max, unit), padL - 8, padT + 4);
        ctx.fillText('0', padL - 8, padT + plotH);

        ctx.strokeStyle = '#1677ff';
        ctx.lineWidth = 2;
        ctx.beginPath();
        points.forEach(function (p, i) {
            var x = padL + (plotW * i) / Math.max(points.length - 1, 1);
            var y = padT + plotH - (plotH * (Number(p.value) || 0)) / max;
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });
        ctx.stroke();

        ctx.lineTo(padL + plotW, padT + plotH);
        ctx.lineTo(padL, padT + plotH);
        ctx.closePath();
        ctx.fillStyle = 'rgba(22, 119, 255, 0.08)';
        ctx.fill();

        if (points.length >= 2) {
            var firstTs = points[0].ts ? new Date(points[0].ts * 1000) : null;
            var lastTs = points[points.length - 1].ts ? new Date(points[points.length - 1].ts * 1000) : null;
            ctx.fillStyle = '#888';
            ctx.textAlign = 'left';
            ctx.fillText(firstTs ? formatTs(firstTs) : '', padL, height - 10);
            ctx.textAlign = 'right';
            ctx.fillText(lastTs ? formatTs(lastTs) : '', width - padR, height - 10);
        }
    }

    function formatTs(d) {
        var m = (d.getMonth() + 1).toString().padStart(2, '0');
        var day = d.getDate().toString().padStart(2, '0');
        var h = d.getHours().toString().padStart(2, '0');
        var min = d.getMinutes().toString().padStart(2, '0');
        return m + '-' + day + ' ' + h + ':' + min;
    }
})();
