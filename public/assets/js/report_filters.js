(function () {
    'use strict';

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    onReady(function () {
        var dialog = document.getElementById('report-filter-dialog');
        if (!dialog) {
            return;
        }

        function openDialog() {
            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else {
                dialog.setAttribute('open', 'open');
            }
        }

        function closeDialog() {
            if (typeof dialog.close === 'function') {
                dialog.close();
            } else {
                dialog.removeAttribute('open');
            }
        }

        document.querySelectorAll('[data-filter-open]').forEach(function (button) {
            button.addEventListener('click', openDialog);
        });

        dialog.querySelectorAll('[data-filter-close]').forEach(function (button) {
            button.addEventListener('click', closeDialog);
        });

        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) {
                closeDialog();
            }
        });
    });
})();
