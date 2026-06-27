/**
 * 文件：assets/js/datetime-picker.js
 * 作用：美化日期时间选择（date + time 组合）
 * @version 1.0.54
 */

(function () {
    'use strict';

    function pad(n) {
        return n < 10 ? '0' + n : String(n);
    }

    window.VsDatetime = {
        /**
         * @param {HTMLElement|string} root
         * @return {{date:HTMLInputElement|null,time:HTMLInputElement|null}}
         */
        getInputs: function (root) {
            var el = typeof root === 'string' ? document.querySelector(root) : root;
            if (!el) return { date: null, time: null };
            return {
                date: el.querySelector('.vs-datetime__date'),
                time: el.querySelector('.vs-datetime__time'),
            };
        },

        /**
         * @param {HTMLElement|string} root
         * @return {string}
         */
        getValue: function (root) {
            var inputs = this.getInputs(root);
            if (!inputs.date || !inputs.time) return '';
            var d = inputs.date.value;
            var t = inputs.time.value;
            if (!d || !t) return '';
            return d + ' ' + t + ':00';
        },

        /**
         * @param {HTMLElement|string} root
         * @return {void}
         */
        clear: function (root) {
            var inputs = this.getInputs(root);
            if (inputs.date) inputs.date.value = '';
            if (inputs.time) inputs.time.value = '';
        },

        /**
         * @param {HTMLElement|string} root
         * @param {string} value  YYYY-MM-DD HH:MM:SS
         * @return {void}
         */
        setValue: function (root, value) {
            var inputs = this.getInputs(root);
            if (!inputs.date || !inputs.time) return;
            value = String(value || '').trim();
            if (!value) {
                this.clear(root);
                return;
            }
            var parts = value.replace('T', ' ').split(' ');
            inputs.date.value = parts[0] || '';
            if (parts[1]) {
                inputs.time.value = parts[1].slice(0, 5);
            }
        },
    };
})();
