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
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-2xl border-2 border-white max-w-md">
                    <div class="px-6 py-6 text-white">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h1 class="text-2xl font-bold mb-2">Welcome back, {{ Auth::user()->name }}!</h1>
                                <p class="text-blue-100 text-base">QA Coordinator Dashboard</p>
                                <p class="text-blue-200 mt-2 text-sm">Review and approve submissions from Planning Coordinators</p>
                            </div>
                            <button onclick="closeWelcomePopup()" class="ml-4 text-white hover:text-blue-200 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Floating Info Container with Statistics Cards -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
                <div class="mb-6">
                    <p class="text-base font-medium text-gray-700">
                        Review and approve submissions from Planning Coordinators
                    </p>
                </div>
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-purple-500">
                        <div class="p-6 text-center">
                            <p class="text-lg font-semibold text-gray-700 mb-3">Campus Rate</p>
                            <p class="text-4xl font-bold text-gray-900">{{ number_format($campusAccomplishmentRate ?? 0, 1) }}%</p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-yellow-500">
                        <div class="p-6 text-center">
                            <p class="text-lg font-semibold text-gray-700 mb-3">Pending Reviews</p>
                            <p class="text-4xl font-bold text-gray-900">{{ $pendingReviews ?? 0 }}</p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-green-500">
                        <div class="p-6 text-center">
                            <p class="text-lg font-semibold text-gray-700 mb-3">Approved</p>
                            <p class="text-4xl font-bold text-gray-900">{{ $approvedSubmissionsCount ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Pending Submissions Card -->
                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border-2 border-yellow-200 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="mb-4">
                            <h3 class="text-xl font-bold text-gray-900">Pending Reviews</h3>
                            <p class="text-sm text-gray-600 mt-1">Submissions awaiting your review</p>
                        </div>
                        <div class="mt-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-4xl font-bold text-gray-900">{{ $pendingReviews ?? 0 }}</span>
                                <span class="text-sm text-gray-600">submissions</span>
                            </div>
                            <a href="{{ route('campus-admin.approvals.index') }}" class="block w-full mt-4 text-center px-6 py-3 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white font-semibold rounded-lg hover:from-yellow-600 hover:to-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                Review Submissions →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Approved Submissions Card -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 border-2 border-green-200 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="mb-4">
                            <h3 class="text-xl font-bold text-gray-900">Approved Submissions</h3>
                            <p class="text-sm text-gray-600 mt-1">Successfully reviewed submissions</p>
                        </div>
                        <div class="mt-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-4xl font-bold text-gray-900">{{ $approvedSubmissionsCount ?? 0 }}</span>
                                <span class="text-sm text-gray-600">submissions</span>
                            </div>
                            <a href="{{ route('campus-admin.approvals.all') }}?status=Approved" class="block w-full mt-4 text-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold rounded-lg hover:from-green-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                View Approved →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Returned Submissions Card -->
                <div class="bg-gradient-to-br from-red-100 to-red-200 border-2 border-red-300 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="mb-4">
                            <h3 class="text-xl font-bold text-gray-900">Returned Submissions</h3>
                            <p class="text-sm text-gray-600 mt-1">Submissions that need revision</p>
                        </div>
                        <div class="mt-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-4xl font-bold text-gray-900">{{ $returnedSubmissions ?? 0 }}</span>
                                <span class="text-sm text-gray-600">submissions</span>
                            </div>
                            <a href="{{ route('campus-admin.approvals.all') }}?status=Returned" class="block w-full mt-4 text-center px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-semibold rounded-lg hover:from-red-700 hover:to-red-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-600 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5" style="background: linear-gradient(to right, #dc2626, #b91c1c);">
                                View Returned →
                            </a>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Campus Performance Summary -->
            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
                <div class="px-6 py-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Campus Performance Summary</h3>
                            <p class="text-sm text-gray-600 mt-1">Overview of approved submissions and performance metrics</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6 border border-blue-100">
                            <h4 class="text-base font-semibold text-gray-900 mb-4">
                                Performance by Quarter
                            </h4>
                            <div class="space-y-4">
                                @if(isset($quarterlyPerformance) && count($quarterlyPerformance) > 0)
                                    @foreach($quarterlyPerformance as $quarter => $data)
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-700">{{ $quarter }}</span>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm font-bold text-gray-900">{{ number_format($data['rate'], 1) }}%</span>
                                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">({{ $data['count'] }})</span>
                                            </div>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-500" style="width: {{ min($data['rate'], 100) }}%"></div>
                                        </div>
                                    </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-8">
                                        <p class="text-sm text-gray-500">No quarterly data available yet.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-6 border border-green-100">
                            <h4 class="text-base font-semibold text-gray-900 mb-4">
                                Top Performing Templates
                            </h4>
                            <div class="space-y-3">
                                @if(isset($topTemplates) && $topTemplates->count() > 0)
                                    @foreach($topTemplates as $index => $template)
                                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-green-100 hover:border-green-300 transition-colors">
                                        <div class="flex items-center flex-1 min-w-0">
                                            <span class="flex-shrink-0 w-6 h-6 bg-green-100 text-green-700 rounded-full flex items-center justify-center text-xs font-bold mr-3">
                                                {{ $index + 1 }}
                                            </span>
                                            <span class="text-sm text-gray-700 truncate font-medium">{{ $template->kpi_title ?? $template->template_code }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2 ml-3">
                                            <span class="text-sm font-bold text-green-600">{{ number_format($template->average_rate, 1) }}%</span>
                                        </div>
                                    </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-8">
                                        <p class="text-sm text-gray-500">No performance data available yet.</p>
                                    </div>
                                @endif
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
