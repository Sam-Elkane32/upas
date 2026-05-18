@php
    $flashSuccess = session('success');
    $flashError = session('error');
    $flashInfo = session('info');
    $flashKind = null;
    $flashBody = '';
    if ($flashSuccess) {
        $flashKind = 'success';
        $flashBody = is_string($flashSuccess) ? $flashSuccess : '';
    } elseif ($flashError) {
        $flashKind = 'error';
        $flashBody = is_string($flashError) ? $flashError : '';
    } elseif ($flashInfo) {
        $flashKind = 'info';
        $flashBody = is_string($flashInfo) ? $flashInfo : '';
    }
@endphp
@if ($flashKind && $flashBody !== '')
    @php
        $titles = [
            'success' => 'Success',
            'error' => 'Notice',
            'info' => 'Information',
        ];
        $title = $titles[$flashKind] ?? 'Notice';
        $panelClass = match ($flashKind) {
            'success' => 'bg-emerald-600 text-white ring-emerald-700/30',
            'error' => 'bg-red-600 text-white ring-red-700/30',
            'info' => 'bg-blue-600 text-white ring-blue-700/30',
            default => 'bg-gray-800 text-white ring-gray-900/30',
        };
    @endphp
    <div id="global-flash-popup"
         class="fixed top-4 right-4 z-[9999] max-w-sm w-[calc(100%-2rem)] pointer-events-auto opacity-0 translate-x-4 transition-all duration-300 ease-out"
         role="alert"
         aria-live="polite">
        <div class="rounded-xl shadow-lg ring-1 {{ $panelClass }} px-4 py-3 flex gap-3 items-start">
            <div class="flex-shrink-0 mt-0.5">
                @if ($flashKind === 'success')
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                @elseif ($flashKind === 'error')
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm leading-tight">{{ $title }}</p>
                <p class="text-sm mt-1 opacity-95 leading-snug break-words">{{ $flashBody }}</p>
            </div>
            <button type="button" onclick="window.closeGlobalFlash && window.closeGlobalFlash()" class="flex-shrink-0 p-1 rounded-md text-white/90 hover:text-white hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Dismiss">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
    <script>
        (function () {
            // Visible for 3s then dismiss; short enter delay so the layout paints first.
            var VISIBLE_MS = 3000;
            var ENTER_DELAY_MS = 300;
            var hideTimer = null;

            function closeGlobalFlash() {
                var el = document.getElementById('global-flash-popup');
                if (!el || el.dataset.closing === '1') return;
                el.dataset.closing = '1';
                if (hideTimer) {
                    clearTimeout(hideTimer);
                    hideTimer = null;
                }
                el.classList.add('opacity-0', 'translate-x-4');
                setTimeout(function () {
                    el.remove();
                }, 300);
            }
            window.closeGlobalFlash = closeGlobalFlash;

            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('global-flash-popup');
                if (!el) return;
                requestAnimationFrame(function () {
                    setTimeout(function () {
                        el.classList.remove('opacity-0', 'translate-x-4');
                        hideTimer = setTimeout(closeGlobalFlash, VISIBLE_MS);
                    }, ENTER_DELAY_MS);
                });
            });
        })();
    </script>
@endif
