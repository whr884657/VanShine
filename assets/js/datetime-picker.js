/**
 * 文件：assets/js/datetime-picker.js
 * 作用：分享过期日期（手动输入 YYYY-MM-DD）
 * @version 1.0.61
 */

(function () {
    'use strict';

    var DATE_RE = /^\d{4}-\d{2}-\d{2}$/;

    function getInput(root) {
        var el = typeof root === 'string' ? document.querySelector(root) : root;
        if (!el) return null;
        return el.querySelector('.vs-date-input') || el.querySelector('input[type="text"]');
    }

    window.VsDatetime = {
        /**
         * @param {HTMLElement|string} root
         * @return {string}
         */
        getValue: function (root) {
            var input = getInput(root);
            if (!input) return '';
            var v = String(input.value || '').trim();
            if (v === '') return '';
            if (!DATE_RE.test(v)) return v;
            return v;
        },

        /**
         * @param {HTMLElement|string} root
         * @return {void}
         */
        clear: function (root) {
            var input = getInput(root);
            if (input) input.value = '';
        },

        /**
         * @param {HTMLElement|string} root
         * @param {string} value
         * @return {void}
         */
        setValue: function (root, value) {
            var input = getInput(root);
            if (!input) return;
            value = String(value || '').trim();
            if (!value) {
                input.value = '';
                return;
            }
            var datePart = value.replace('T', ' ').split(' ')[0] || '';
            input.value = datePart;
        },
    };
})();
