        {{-- Unsaved changes confirmation modal (Super Admin editing only) --}}
        @if(!$readOnly)
        <div id="unsaved-confirm-modal" class="fixed inset-0 z-[10002] hidden flex items-center justify-center p-4 overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="unsaved-confirm-title">
            <div class="absolute inset-0 bg-gray-500/75 transition-opacity" id="unsaved-confirm-backdrop"></div>
            <div class="relative z-10 bg-white rounded-xl shadow-2xl max-w-md w-full p-6 my-8">
                <h3 id="unsaved-confirm-title" class="text-lg font-semibold text-gray-900 mb-2">Unsaved changes</h3>
                <p class="text-sm text-gray-600 mb-6">You have unsaved changes. Are you sure you want to leave without saving?</p>
                <div class="flex flex-col sm:flex-row gap-2 justify-end">
                    <button type="button" id="unsaved-confirm-cancel" class="order-2 sm:order-3 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" id="unsaved-confirm-leave" class="order-1 sm:order-2 px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-md hover:bg-gray-200">
                        Leave without saving
                    </button>
                    <button type="button" id="unsaved-confirm-save-exit" class="order-0 sm:order-1 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                        Save &amp; Exit
                    </button>
                </div>
            </div>
        </div>
        <script>
        (function() {
            var modal = document.getElementById('unsaved-confirm-modal');
            var backdrop = document.getElementById('unsaved-confirm-backdrop');
            var btnCancel = document.getElementById('unsaved-confirm-cancel');
            var btnLeave = document.getElementById('unsaved-confirm-leave');
            var btnSaveExit = document.getElementById('unsaved-confirm-save-exit');
            var pendingBackUrl = '';
            function attachBackLinkHandler() {
                var backLink = document.getElementById('back-to-form-link');
                if (backLink) {
                    backLink.addEventListener('click', function(e) {
                        var href = backLink.getAttribute('data-href') || backLink.getAttribute('href');
                        if (!href || href === '#') return;
                        if (window.tableDataDirty === true && typeof window.performSaveTableData === 'function') {
                            e.preventDefault();
                            pendingBackUrl = href;
                            if (modal) {
                                modal.classList.remove('hidden');
                                modal.classList.add('flex');
                            }
                        } else {
                            window.location.href = href;
                        }
                    });
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', attachBackLinkHandler);
            } else {
                attachBackLinkHandler();
            }
            function closeModal() {
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
                pendingBackUrl = '';
            }
            if (btnCancel) btnCancel.addEventListener('click', closeModal);
            if (backdrop) backdrop.addEventListener('click', closeModal);
            if (btnLeave) btnLeave.addEventListener('click', function() {
                if (pendingBackUrl) window.location.href = pendingBackUrl;
                closeModal();
            });
            if (btnSaveExit) btnSaveExit.addEventListener('click', function() {
                if (!pendingBackUrl || typeof window.performSaveTableData !== 'function') return;
                btnSaveExit.disabled = true;
                window.performSaveTableData({
                    onSuccess: function() {
                        window.tableDataDirty = false;
                        window.location.href = pendingBackUrl;
                    },
                    onDone: function() { btnSaveExit.disabled = false; }
                });
            });
        })();
        </script>
        @endif
