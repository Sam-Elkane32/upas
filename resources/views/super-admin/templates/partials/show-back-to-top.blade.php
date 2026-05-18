    {{-- Back to Top button (fixed bottom-right, smooth scroll to top, animated) --}}
    <style>
        #back-to-top-btn {
            opacity: 0;
            transform: translateY(0.75rem);
            pointer-events: none;
            transition: opacity 0.3s ease, transform 0.3s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }
        #back-to-top-btn.back-to-top-visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        #back-to-top-btn:hover {
            transform: translateY(0) scale(1.08);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        #back-to-top-btn:active {
            transform: translateY(0) scale(0.98);
        }
        @keyframes back-to-top-pulse {
            0%, 100% { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15); }
            50% { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25); }
        }
        #back-to-top-btn.back-to-top-visible {
            animation: back-to-top-pulse 2.5s ease-in-out infinite;
        }
        #back-to-top-btn.back-to-top-visible:hover {
            animation: none;
        }
    </style>
    <button type="button" id="back-to-top-btn" aria-label="Back to top" class="fixed right-6 z-50 flex items-center justify-center w-12 h-12 rounded-full bg-gray-700 text-white shadow-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 {{ $hasQuickAccessFooter ? 'bottom-20' : 'bottom-6' }}">
        <svg class="w-6 h-6 transition-transform duration-200 group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
        </svg>
    </button>
    <script>
        (function() {
            var btn = document.getElementById('back-to-top-btn');
            if (!btn) return;
            var scrollThreshold = 280;
            var isTicking = false;
            function updateVisibility() {
                if (window.scrollY > scrollThreshold) {
                    btn.classList.add('back-to-top-visible');
                } else {
                    btn.classList.remove('back-to-top-visible');
                }
            }
            window.addEventListener('scroll', function() {
                if (isTicking) return;
                isTicking = true;
                requestAnimationFrame(function() {
                    updateVisibility();
                    isTicking = false;
                });
            });
            updateVisibility();
            btn.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        })();
    </script>
