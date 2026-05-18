                    <script>
                    // Toast stack (top-right): vertical gap so multiple messages never overlap; optional tag to dismiss as a group.
                    var _lastToastAt = 0;
                    var _toastDedupMs = 1500;
                    var _toastStackEl = null;
                    var TOAST_STACK_MAX = 6;
                    function ensureToastStack() {
                        if (_toastStackEl && document.body.contains(_toastStackEl)) return _toastStackEl;
                        var el = document.getElementById('uaps-toast-stack');
                        if (el) {
                            _toastStackEl = el;
                            return el;
                        }
                        el = document.createElement('div');
                        el.id = 'uaps-toast-stack';
                        el.className = 'fixed top-4 right-4 z-[10001] flex flex-col items-end gap-2 pointer-events-none max-w-[min(28rem,calc(100vw-2rem))]';
                        el.setAttribute('aria-live', 'polite');
                        document.body.appendChild(el);
                        _toastStackEl = el;
                        return el;
                    }
                    window.dismissUapsToastsByTag = function(tag) {
                        if (!tag) return;
                        var stack = document.getElementById('uaps-toast-stack');
                        if (!stack) return;
                        try {
                            stack.querySelectorAll('[data-toast-tag]').forEach(function(node) {
                                if (node.getAttribute('data-toast-tag') === String(tag)) node.remove();
                            });
                        } catch (err) {}
                    };
                    window.showToast = function(type, message, opts) {
                        opts = opts || {};
                        type = type || 'success';
                        var now = Date.now();
                        if (message && (message.indexOf('Saved') !== -1 || message.indexOf('saved') !== -1) && (now - _lastToastAt) < _toastDedupMs) {
                            return;
                        }
                        _lastToastAt = now;
                        var bg = type === 'error' ? 'bg-red-600' : (type === 'notice' ? 'bg-amber-500' : 'bg-green-600');
                        var stack = ensureToastStack();
                        while (stack.children.length >= TOAST_STACK_MAX) {
                            var first = stack.firstElementChild;
                            if (first) first.remove();
                        }
                        var toast = document.createElement('div');
                        toast.className = 'pointer-events-auto w-full px-4 py-3 sm:px-6 sm:py-4 rounded-lg shadow-lg flex items-center text-white text-sm sm:text-base ' + bg;
                        toast.setAttribute('role', 'alert');
                        if (opts.tag) toast.setAttribute('data-toast-tag', String(opts.tag));
                        var span = document.createElement('span');
                        span.className = 'font-medium break-words';
                        span.textContent = message || 'Saved successfully.';
                        toast.appendChild(span);
                        stack.appendChild(toast);
                        var hideMs = typeof opts.duration === 'number' ? opts.duration : 3000;
                        setTimeout(function() {
                            toast.style.opacity = '0';
                            toast.style.transform = 'translateX(8px)';
                            toast.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                            setTimeout(function() { if (toast.parentNode) toast.remove(); }, 280);
                        }, hideMs);
                    };
                    /** Direct child TD cells only — avoids wrong column index when a cell contains nested tables. */
                    window.getRowTdCells = function(tr) {
                        if (!tr || !tr.children) return [];
                        var out = [];
                        for (var i = 0; i < tr.children.length; i++) {
                            var el = tr.children[i];
                            if (el && el.nodeType === 1 && String(el.tagName).toUpperCase() === 'TD') out.push(el);
                        }
                        return out;
                    };
                    </script>
