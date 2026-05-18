<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-blue-500">
                        <div class="p-6 text-center">
                            <p class="text-lg font-semibold text-gray-700 mb-3">Planning Coordinators</p>
                            <p class="text-4xl font-bold text-gray-900">{{ $stats['total_campus_users'] }}</p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-green-500">
                        <div class="p-6 text-center">
                            <p class="text-lg font-semibold text-gray-700 mb-3">Creator/Editors</p>
                            <p class="text-4xl font-bold text-gray-900">{{ $stats['creator_editors_count'] }}</p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-yellow-500">
                        <div class="p-6 text-center">
                            <p class="text-lg font-semibold text-gray-700 mb-3">Pending Approvals</p>
                            <p class="text-4xl font-bold text-gray-900">{{ $stats['pending_approvals'] }}</p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-purple-500">
                        <div class="p-6 text-center">
                            <p class="text-lg font-semibold text-gray-700 mb-3">Forms Submitted</p>
                            <p class="text-4xl font-bold text-gray-900">{{ $stats['total_forms_submitted'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Planning Coordinators -->
                <div class="bg-white rounded-lg shadow-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Planning Coordinators</h3>
                    </div>
                    <div class="p-6">
                        @if($campusUsersList->count() > 0)
                            <div class="space-y-4">
                                @foreach($campusUsersList as $user)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900">{{ $user->name }}</h4>
                                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                        <p class="text-xs text-gray-400">{{ $user->position }} • {{ ucfirst(str_replace('_', ' ', $user->role)) }}</p>
                                    </div>
                                    <div class="ml-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            @if($user->is_approved) bg-green-100 text-green-800
                                            @else bg-yellow-100 text-yellow-800 @endif">
                                            @if($user->is_approved) Approved @else Pending @endif
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No campus users</h3>
                                <p class="mt-1 text-sm text-gray-500">Users will appear here when they register.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Approvals -->
                <div class="bg-white rounded-lg shadow-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Approvals</h3>
                    </div>
                    <div class="p-6">
                        @if($recentApprovals->count() > 0)
                            <div class="space-y-4">
                                @foreach($recentApprovals as $user)
                                <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900">{{ $user->name }}</h4>
                                        <p class="text-sm text-gray-500">{{ $user->position }}</p>
                                        <p class="text-xs text-gray-400">Approved {{ $user->approved_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="ml-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Approved
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No recent approvals</h3>
                                <p class="mt-1 text-sm text-gray-500">Approval activity will appear here.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8 bg-white rounded-lg shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                        <a href="{{ route('campus-admin.approvals.index') }}" class="flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-gray-900">Review & Approve Submissions</h4>
                                <p class="text-sm text-gray-500">{{ $stats['pending_approvals'] }} pending submissions</p>
                            </div>
                        </a>
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
