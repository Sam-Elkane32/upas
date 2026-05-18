<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome Pop-up Message (only shown after login) -->
            @if(session('show_welcome_popup'))
            <div id="welcomePopup" class="fixed top-4 right-4 z-[10001] transform translate-x-full transition-transform duration-500 ease-in-out" style="display: none;">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-700 rounded-lg shadow-2xl border-2 border-white max-w-md">
                    <div class="px-6 py-6 text-white">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h1 class="text-2xl font-bold mb-2">Welcome back, {{ Auth::user()->name }}!</h1>
                                <p class="text-indigo-100 text-base">Planning Coordinator Dashboard</p>
                                <p class="text-indigo-200 mt-2 text-sm">Track your accomplishment submissions and stay updated with your progress</p>
                            </div>
                            <button onclick="closeWelcomePopup()" class="ml-4 text-white hover:text-indigo-200 transition-colors">
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
                        Data Analytics
                    </p>
                </div>
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
                    <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-indigo-500">
                        <div class="p-6 text-center">
                            <p class="text-lg font-semibold text-gray-700 mb-3">Assigned Templates</p>
                            <p class="text-4xl font-bold text-gray-900">{{ $submissionStats['assigned_templates'] ?? 0 }}</p>
                        </div>
                    </div>
                    
                    <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-orange-500">
                        <div class="p-6 text-center">
                            <p class="text-lg font-semibold text-gray-700 mb-3">Returned Templates</p>
                            <p class="text-4xl font-bold text-gray-900">{{ $submissionStats['returned_templates'] ?? 0 }}</p>
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
