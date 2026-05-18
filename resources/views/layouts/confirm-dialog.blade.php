{{-- Custom confirm/alert dialog. Replaces browser confirm() and alert(). --}}
<div id="app-confirm-dialog" class="fixed inset-0 z-[10000] hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="app-dialog-title">
    <div class="fixed inset-0 bg-black/50 transition-opacity" id="app-dialog-backdrop"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div id="app-dialog-panel" class="relative w-full max-w-md rounded-2xl bg-white shadow-xl ring-1 ring-black/5 transform transition-all">
            <div class="p-6">
                <h3 id="app-dialog-title" class="text-lg font-semibold text-gray-900 mb-2"></h3>
                <p id="app-dialog-message" class="text-sm text-gray-600 mb-6"></p>
                <div id="app-dialog-actions" class="flex flex-wrap gap-3 justify-end">
                    <button type="button" id="app-dialog-cancel" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </button>
                    <button type="button" id="app-dialog-confirm" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    var dialog = document.getElementById('app-confirm-dialog');
    var titleEl = document.getElementById('app-dialog-title');
    var messageEl = document.getElementById('app-dialog-message');
    var actionsEl = document.getElementById('app-dialog-actions');
    var cancelBtn = document.getElementById('app-dialog-cancel');
    var confirmBtn = document.getElementById('app-dialog-confirm');
    var backdrop = document.getElementById('app-dialog-backdrop');

    function hide() {
        dialog.classList.add('hidden');
        dialog.setAttribute('aria-hidden', 'true');
    }

    function show() {
        dialog.classList.remove('hidden');
        dialog.setAttribute('aria-hidden', 'false');
    }

    function open(options) {
        var isConfirm = options.type === 'confirm';
        titleEl.textContent = options.title || (isConfirm ? 'Confirm' : 'Notice');
        messageEl.textContent = options.message || '';
        cancelBtn.textContent = options.cancelText || 'Cancel';
        confirmBtn.textContent = options.confirmText || 'OK';
        cancelBtn.style.display = isConfirm ? '' : 'none';
        confirmBtn.focus();
        show();
        return { hide: hide, onConfirm: options.onConfirm, onCancel: options.onCancel, onClose: options.onClose };
    }

    cancelBtn.addEventListener('click', function() {
        var opts = window._appDialogCurrent;
        hide();
        if (opts && opts.onCancel) opts.onCancel();
        window._appDialogCurrent = null;
    });

    confirmBtn.addEventListener('click', function() {
        var opts = window._appDialogCurrent;
        hide();
        if (opts && opts.onConfirm) opts.onConfirm();
        if (opts && opts.onClose) opts.onClose();
        window._appDialogCurrent = null;
    });

    backdrop.addEventListener('click', function() {
        var opts = window._appDialogCurrent;
        hide();
        if (opts && opts.onCancel) opts.onCancel();
        window._appDialogCurrent = null;
    });

    window.showConfirm = function(options) {
        options = options || {};
        options.type = 'confirm';
        window._appDialogCurrent = open(options);
    };

    window.showAlert = function(options) {
        options = options || {};
        options.type = 'alert';
        options.onConfirm = options.onClose;
        window._appDialogCurrent = open(options);
    };

    // Forms with data-confirm: intercept submit, show custom dialog, submit on confirm
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (form && form._appDialogConfirmed) return;
            var msg = form && form.getAttribute && form.getAttribute('data-confirm');
            if (!msg) return;
            e.preventDefault();
            var formToSubmit = form;
            window.showConfirm({
                title: 'Confirm',
                message: msg,
                confirmText: 'Confirm',
                cancelText: 'Cancel',
                onConfirm: function() {
                    if (formToSubmit) {
                        formToSubmit._appDialogConfirmed = true;
                        formToSubmit.submit();
                    }
                }
            });
        }, true);
    });
})();
</script>
