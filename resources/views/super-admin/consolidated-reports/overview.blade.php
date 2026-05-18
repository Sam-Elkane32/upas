@extends('super-admin.consolidated-reports.layout')

@section('header_title')
    Overview Dashboard
@endsection

@section('subnav')
    {{-- Standalone page: no tab bar --}}
@endsection

@section('content')
    @include('super-admin.consolidated-reports.partials.overview-content')
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        function toggleFilters() {
            const filterContent = document.getElementById('filterContent');
            const filterIcon = document.getElementById('filterToggleIcon');
            if (filterContent && filterIcon) {
                if (filterContent.style.display === 'none') {
                    filterContent.style.display = 'block';
                    filterIcon.setAttribute('d', 'M19 9l-7 7-7-7');
                } else {
                    filterContent.style.display = 'none';
                    filterIcon.setAttribute('d', 'M5 15l7-7 7 7');
                }
            }
        }
        function toggleAllCampuses(isChecked) {
            document.querySelectorAll('input[name="campuses[]"]').forEach(function(cb) { cb.checked = isChecked; });
        }
        function deleteSubmission(submissionId, templateCode) {
            var msg = 'Are you sure you want to delete this submission' + (templateCode && templateCode !== 'N/A' ? ' (Template: ' + templateCode + ')' : '') + '? This action cannot be undone.';
            if (!confirm(msg)) return;
            var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('/super-admin/submissions/' + submissionId, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf || '', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    var el = document.querySelector('[data-submission-id="' + submissionId + '"]');
                    if (el) { el.style.opacity = '0'; setTimeout(function() { el.remove(); }, 300); }
                    if (window.showAlert) window.showAlert({ title: 'Success', message: 'Submission deleted.' });
                    else alert('Submission deleted.');
                } else {
                    if (window.showAlert) window.showAlert({ title: 'Error', message: data.message || 'Failed to delete.' });
                    else alert(data.message || 'Failed to delete.');
                }
            }).catch(function() {
                if (window.showAlert) window.showAlert({ title: 'Error', message: 'An error occurred.' });
                else alert('An error occurred.');
            });
        }
    </script>
    @if(isset($trends['monthly']) && count($trends['monthly']) > 0)
    <script>
        (function() {
            var ctx = document.getElementById('monthlyTrendChart');
            if (ctx) new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode(array_column($trends['monthly'], 'month')) !!},
                    datasets: [
                        { label: 'Submissions', data: {!! json_encode(array_column($trends['monthly'], 'total_submissions')) !!}, borderColor: 'rgb(59, 130, 246)', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.1 },
                        { label: 'Approved', data: {!! json_encode(array_column($trends['monthly'], 'approved_submissions')) !!}, borderColor: 'rgb(34, 197, 94)', backgroundColor: 'rgba(34, 197, 94, 0.1)', tension: 0.1 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: true, aspectRatio: 2, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
            });
        })();
    </script>
    @endif
    @if(isset($trends['quarterly']) && count($trends['quarterly']) > 0)
    <script>
        (function() {
            var ctx = document.getElementById('quarterlyTrendChart');
            if (ctx) new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode(array_column($trends['quarterly'], 'label')) !!},
                    datasets: [
                        { label: 'Submissions', data: {!! json_encode(array_column($trends['quarterly'], 'total_submissions')) !!}, backgroundColor: 'rgba(59, 130, 246, 0.8)' },
                        { label: 'Approved', data: {!! json_encode(array_column($trends['quarterly'], 'approved_submissions')) !!}, backgroundColor: 'rgba(34, 197, 94, 0.8)' }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: true, aspectRatio: 2, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
            });
        })();
    </script>
    @endif
    @if(isset($stats) && ($stats['total_submissions'] ?? 0) > 0)
    <script>
        (function() {
            var ctx = document.getElementById('statusChart');
            if (ctx) new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Pending Review', 'Approved', 'Returned'],
                    datasets: [{
                        data: [{{ $stats['pending_submissions'] ?? 0 }}, {{ $stats['approved_submissions'] ?? 0 }}, {{ $stats['returned_submissions'] ?? 0 }}],
                        backgroundColor: ['rgba(234, 179, 8, 0.8)', 'rgba(34, 197, 94, 0.8)', 'rgba(239, 68, 68, 0.8)']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true, aspectRatio: 1, plugins: { legend: { position: 'bottom' } } }
            });
        })();
    </script>
    @endif
    <script>
        (function() {
            var selectAll = document.getElementById('select_all_campuses');
            var checkboxes = document.querySelectorAll('input[name="campuses[]"]');
            if (selectAll && checkboxes.length) {
                checkboxes.forEach(function(cb) {
                    cb.addEventListener('change', function() {
                        var all = true;
                        checkboxes.forEach(function(c) { if (!c.checked) all = false; });
                        selectAll.checked = all;
                    });
                });
            }
        })();
    </script>
@endpush
