    @if(!$readOnly)
    {{-- Editing History modal (Audit Trailing) --}}
    <div id="audit-trail-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" id="audit-trail-backdrop"></div>
            <div class="relative bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden">
                <div class="flex-shrink-0 px-6 py-5 border-b border-gray-200 bg-gray-50/80">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900" id="modal-title">Editing History</h3>
                            <p id="audit-trail-subtitle" class="mt-0.5 text-sm text-gray-500">Template <span id="audit-trail-template-code" class="font-medium text-gray-700">{{ $template->template_code ?? '' }}</span></p>
                        </div>
                        <span id="audit-trail-count-badge" class="hidden inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800">0 entries</span>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-4 min-h-0">
                    <div id="audit-trail-loading" class="flex flex-col items-center justify-center py-12 text-gray-500">
                        <svg class="animate-spin h-8 w-8 text-indigo-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm">Loading history…</span>
                    </div>
                    <div id="audit-trail-content" class="hidden space-y-4">
                        <div id="audit-trail-list"></div>
                    </div>
                    <div id="audit-trail-empty" class="hidden flex flex-col items-center justify-center py-12 text-center">
                        <div class="rounded-full bg-gray-100 p-4 mb-3">
                            <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">No edit history yet</p>
                        <p class="mt-1 text-sm text-gray-500">Changes to this template and its submissions will appear here.</p>
                    </div>
                    <div id="audit-trail-error" class="hidden flex flex-col items-center justify-center py-12 text-center">
                        <div class="rounded-full bg-red-50 p-4 mb-3">
                            <svg class="h-10 w-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700">Failed to load history</p>
                        <p id="audit-trail-error-msg" class="mt-1 text-sm text-gray-500">Please try again later.</p>
                    </div>
                </div>
                <div class="flex-shrink-0 px-6 py-4 border-t border-gray-200 bg-gray-50/50 flex flex-wrap items-center justify-between gap-3">
                    <button type="button" id="audit-trail-clear-btn" class="inline-flex justify-center items-center px-4 py-2.5 border border-red-300 text-red-700 text-sm font-medium rounded-lg hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                        Clear history
                    </button>
                    <button type="button" id="audit-trail-close-btn" class="inline-flex justify-center items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
(function() {
    var modal = document.getElementById('audit-trail-modal');
    var showBtn = document.getElementById('audit-trail-show-btn');
    var closeBtn = document.getElementById('audit-trail-close-btn');
    var backdrop = document.getElementById('audit-trail-backdrop');
    var loadingEl = document.getElementById('audit-trail-loading');
    var contentEl = document.getElementById('audit-trail-content');
    var listEl = document.getElementById('audit-trail-list');
    var emptyEl = document.getElementById('audit-trail-empty');
    var errorEl = document.getElementById('audit-trail-error');
    var errorMsgEl = document.getElementById('audit-trail-error-msg');
    var countBadge = document.getElementById('audit-trail-count-badge');
    var subtitleEl = document.getElementById('audit-trail-subtitle');
    var templateCodeEl = document.getElementById('audit-trail-template-code');
    var editHistoryUrl = '{{ route("super-admin.templates.edit-history", $template) }}';
    var clearEditHistoryUrl = '{{ route("super-admin.templates.clear-edit-history", $template) }}';

    function esc(s) {
        if (s == null) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
    function showLoading() {
        loadingEl.classList.remove('hidden');
        contentEl.classList.add('hidden');
        emptyEl.classList.add('hidden');
        errorEl.classList.add('hidden');
    }
    function showContent(data) {
        loadingEl.classList.add('hidden');
        var history = data.history || [];
        var templateCode = data.template_code || '';
        var count = data.count != null ? data.count : history.length;

        if (templateCodeEl) templateCodeEl.textContent = templateCode;
        if (countBadge) {
            countBadge.textContent = count === 1 ? '1 entry' : count + ' entries';
            countBadge.classList.toggle('hidden', count === 0);
        }

        if (history.length === 0) {
            contentEl.classList.add('hidden');
            emptyEl.classList.remove('hidden');
            errorEl.classList.add('hidden');
            return;
        }
        emptyEl.classList.add('hidden');
        errorEl.classList.add('hidden');
        contentEl.classList.remove('hidden');

        listEl.innerHTML = '';
        history.forEach(function(h, i) {
            var card = document.createElement('div');
            card.className = 'rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-gray-300 hover:shadow transition-shadow';
            card.innerHTML =
                '<div class="flex flex-wrap items-start justify-between gap-3">' +
                    '<div class="flex items-center gap-3 min-w-0">' +
                        '<div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-semibold text-indigo-700">' + esc((h.who_edited || '').charAt(0).toUpperCase()) + '</div>' +
                        '<div class="min-w-0">' +
                            '<p class="text-sm font-semibold text-gray-900">' + esc(h.who_edited || '') + '</p>' +
                        '</div>' +
                    '</div>' +
                    '<div class="flex-shrink-0 text-right">' +
                        '<p class="text-sm font-medium text-gray-700">' + esc(h.created_at || '') + '</p>' +
                        (h.created_at_relative ? '<p class="text-xs text-gray-500 mt-0.5">' + esc(h.created_at_relative) + '</p>' : '') +
                    '</div>' +
                '</div>' +
                '<p class="mt-3 text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-3">' + esc(h.what_edited || '') + '</p>';
            listEl.appendChild(card);
        });
    }
    function showError(msg) {
        loadingEl.classList.add('hidden');
        contentEl.classList.add('hidden');
        emptyEl.classList.add('hidden');
        errorEl.classList.remove('hidden');
        if (errorMsgEl) errorMsgEl.textContent = msg || 'Please try again later.';
    }

    if (showBtn) showBtn.addEventListener('click', function() {
        openModal();
        showLoading();
        fetch(editHistoryUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                showContent(data);
            })
            .catch(function() {
                showError('Failed to load edit history.');
            });
    });
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    var clearBtn = document.getElementById('audit-trail-clear-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to clear all editing history for this template? This cannot be undone.')) return;
            var token = document.querySelector('meta[name="csrf-token"]');
            token = token ? token.getAttribute('content') : '';
            clearBtn.disabled = true;
            fetch(clearEditHistoryUrl, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token,
                },
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                clearBtn.disabled = false;
                if (data.success) {
                    if (typeof window.showToast === 'function') window.showToast('success', data.message || 'History cleared.');
                    showContent({ history: [], template_code: (templateCodeEl && templateCodeEl.textContent) ? templateCodeEl.textContent : '', count: 0 });
                } else {
                    if (typeof window.showToast === 'function') window.showToast('error', data.message || 'Failed to clear history.');
                }
            })
            .catch(function() {
                clearBtn.disabled = false;
                if (typeof window.showToast === 'function') window.showToast('error', 'Failed to clear history.');
            });
        });
    }
})();
    </script>
    @endif
