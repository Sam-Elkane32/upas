<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(isset($error))
                <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                    <p class="text-sm text-yellow-700 font-medium">{{ $error }}</p>
                </div>
            @endif

            <!-- Welcome Pop-up Message (only shown after login) -->
            @if(session('show_welcome_popup'))
            <div id="welcomePopup" class="fixed top-4 right-4 z-50 transform translate-x-full transition-transform duration-500 ease-in-out" style="display: none;">
                <div class="bg-gradient-to-r from-indigo-600 to-blue-700 rounded-xl shadow-2xl border border-indigo-200 max-w-md overflow-hidden">
                    <div class="px-6 py-5 text-white">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <h2 class="text-xl font-bold mb-1">Welcome back, {{ Auth::user()->name }}!</h2>
                                <p class="text-indigo-100 text-sm font-medium">Super Admin Dashboard</p>
                                <p class="text-indigo-200/90 mt-2 text-sm">Monitor university-wide performance and manage system-wide operations.</p>
                            </div>
                            <button type="button" onclick="closeWelcomePopup()" class="shrink-0 p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-colors focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Stats & overview -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-5 mb-6">
                <p class="text-sm font-medium text-gray-600 mb-4">
                    University-wide consolidated analytics and monitoring
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden border-l-4 border-blue-500">
                        <div class="p-4">
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Total Campuses</p>
                            <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ $totalCampuses ?? 0 }}</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden border-l-4 border-green-500">
                        <div class="p-4">
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Total KRAs</p>
                            <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ $totalKras ?? 0 }}</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden border-l-4 border-purple-500">
                        <div class="p-4">
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Total KPIs</p>
                            <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ $totalKpis ?? 0 }}</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden border-l-4 border-amber-500">
                        <div class="p-4">
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Avg. Accomplishment Rate</p>
                            <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ number_format($averageAccomplishmentRate ?? 0, 1) }}%</p>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Campus Performance Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Campus Performance Chart -->
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/80">
                        <h3 class="text-lg font-semibold text-gray-900">Campus Performance Overview</h3>
                        <p class="text-sm text-gray-600 mt-0.5">Accomplishment rates across all campuses</p>
                    </div>
                    <div class="p-5">
                        @if(isset($campusPerformance) && $campusPerformance->count() > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($campusPerformance as $campus)
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-900 truncate pr-2">{{ $campus->name ?? 'Unknown' }}</span>
                                        <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">{{ number_format($campus->accomplishment_rate ?? 0, 1) }}%</span>
                                    </div>
                                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-indigo-500 to-blue-600 rounded-full transition-all duration-500" style="width: {{ min($campus->accomplishment_rate ?? 0, 100) }}%"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-10">
                                <p class="text-sm text-gray-500">No campus performance data available yet.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- KPI Achievement Summary -->
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/80">
                        <h3 class="text-lg font-semibold text-gray-900">KPI Achievement Summary</h3>
                        <p class="text-sm text-gray-600 mt-0.5">Performance rating distribution</p>
                    </div>
                    <div class="p-5">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg border-l-4 border-green-500">
                                <span class="text-sm font-medium text-gray-900">Outstanding (100%+)</span>
                                <span class="text-lg font-bold text-green-600">{{ $kpiStats['outstanding'] ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                <span class="text-sm font-medium text-gray-900">Very Satisfactory (90-99%)</span>
                                <span class="text-lg font-bold text-blue-600">{{ $kpiStats['very_satisfactory'] ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-amber-50 rounded-lg border-l-4 border-amber-500">
                                <span class="text-sm font-medium text-gray-900">Satisfactory (80-89%)</span>
                                <span class="text-lg font-bold text-amber-600">{{ $kpiStats['satisfactory'] ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg border-l-4 border-red-500">
                                <span class="text-sm font-medium text-gray-900">Needs Improvement (&lt;80%)</span>
                                <span class="text-lg font-bold text-red-600">{{ $kpiStats['needs_improvement'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @if(session('show_welcome_popup'))
    <script>
        // Show welcome popup instantly on page load (only after login)
        document.addEventListener('DOMContentLoaded', function() {
            const popup = document.getElementById('welcomePopup');
            if (popup) {
                popup.style.display = 'block';
                // Trigger animation
                setTimeout(function() {
                    popup.classList.remove('translate-x-full');
                }, 10);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    closeWelcomePopup();
                }, 5000);
            }
        });

        function closeWelcomePopup() {
            const popup = document.getElementById('welcomePopup');
            if (popup) {
                popup.classList.add('translate-x-full');
                setTimeout(function() {
                    popup.style.display = 'none';
                    // Clear the session flag via AJAX
                    fetch('{{ route("session.clear-welcome-popup") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                        },
                    });
                }, 500);
            }
        }
    </script>
    @endif
</x-app-layout>

