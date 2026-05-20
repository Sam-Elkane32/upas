{{-- Shared single-flight autosave queue (Field Structure / template show). Prevents overlapping POSTs that cause 504 on Vercel. --}}
window.__templateAutosave = window.__templateAutosave || {
    inFlight: false,
    queued: false,
    pendingOpts: null,
    retryCount: 0,
    retryTimer: null
};
window.__templateAutosaveIsRetryable = function(status) {
    return status === 502 || status === 503 || status === 504 || status === 429;
};
window.__templateAutosaveFinish = function(opts) {
    var st = window.__templateAutosave;
    st.inFlight = false;
    if (opts && typeof opts.onDone === 'function') opts.onDone();
    if (st.queued && window.tableDataDirty) {
        st.queued = false;
        var nextOpts = st.pendingOpts || {};
        st.pendingOpts = null;
        setTimeout(function() {
            if (typeof window.performSaveTableData === 'function') window.performSaveTableData(nextOpts);
        }, 80);
    }
};
window.__templateAutosaveHandleFailure = function(opts, httpStatus, showToastFn) {
    var st = window.__templateAutosave;
    if (window.__templateAutosaveIsRetryable(httpStatus) && st.retryCount < 4) {
        st.retryCount++;
        if (st.retryTimer) clearTimeout(st.retryTimer);
        var delay = Math.min(8000, 1200 * st.retryCount);
        st.inFlight = false;
        if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
        st.retryTimer = setTimeout(function() {
            st.retryTimer = null;
            if (typeof window.performSaveTableData === 'function') window.performSaveTableData(opts || {});
        }, delay);
        return true;
    }
    st.retryCount = 0;
    if (st.retryTimer) {
        clearTimeout(st.retryTimer);
        st.retryTimer = null;
    }
    if (typeof setAutosaveStatus === 'function') setAutosaveStatus('error');
    if (typeof showToastFn === 'function') showToastFn();
    window.__templateAutosaveFinish(opts || {});
    return false;
};
window.__wrapTemplateTableSave = function(runSave) {
    window.performSaveTableData = function(opts) {
        opts = opts || {};
        var st = window.__templateAutosave;
        if (st.inFlight) {
            st.queued = true;
            st.pendingOpts = opts;
            return;
        }
        st.inFlight = true;
        runSave(opts);
    };
};
