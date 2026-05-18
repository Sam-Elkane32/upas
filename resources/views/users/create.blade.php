<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Floating Header Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            Create New User
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Add a new user to the system
                        </p>
                    </div>
                    <div>
                        <a href="{{ auth()->user()->isSuperAdmin() ? route('super-admin.users') : route('campus-admin.users') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Users
                        </a>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ auth()->user()->isSuperAdmin() ? route('super-admin.users.store') : route('campus-admin.users.store') }}" id="create-user-form">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- 1) Campus (required) -->
                            <div>
                                <label for="campus_code" class="block text-sm font-medium text-gray-700">Campus <span class="text-red-500">*</span></label>
                                <select name="campus_code" id="campus_code" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->code }}" data-code="{{ $campusCodeMap[$campus->code] ?? $campus->code }}" {{ old('campus_code') === $campus->code ? 'selected' : '' }}>
                                            {{ $campus->name }} ({{ $campus->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Select the campus first. User Name options will appear based on this selection.</p>
                                @error('campus_code')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 2) User Name (select from options derived from campus) -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">User Name <span class="text-red-500">*</span></label>
                                <select name="name" id="name" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select User Name</option>
                                    {{-- Options populated by JS when campus is selected --}}
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Choose from generated options (e.g. LINGAYEN Planning Coordinator, LINGAYEN QA Coordinator, LINGAYEN CED).</p>
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 3) Email Address (required) -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="e.g. user@psu.edu.ph">
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 4) Role (auto-set, read-only) -->
                            <div>
                                <label for="role_display" class="block text-sm font-medium text-gray-700">Role</label>
                                <input type="text" id="role_display" readonly disabled
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-700 sm:text-sm"
                                    placeholder="Set automatically by User Name">
                                <input type="hidden" name="role" id="role" value="{{ old('role') }}">
                                <p class="mt-1 text-xs text-gray-500">Role is set automatically from the selected User Name.</p>
                                @error('role')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Password Notice -->
                        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Default Password</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>The new user will be created with the default password: <strong id="current-default-password">{{ \App\Models\Setting::get('default_password', 'UPAS@2025!') }}</strong></p>
                                        <p class="mt-1">They should change this password after their first login.</p>
                                        @if(auth()->user()->isSuperAdmin())
                                        <p class="mt-1 text-xs text-gray-600">You can change the default password in <a href="{{ route('super-admin.settings.index') }}" class="text-blue-600 hover:text-blue-800 underline">System Settings</a>.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var campusSelect = document.getElementById('campus_code');
            var nameSelect = document.getElementById('name');
            var roleInput = document.getElementById('role');
            var roleDisplay = document.getElementById('role_display');

            var roleLabels = {
                'creator_editor': 'Planning Coordinator',
                'planning_coordinator': 'Planning Coordinator',
                'admin': 'QA Coordinator',
                'view_only': 'Campus CED (View Only)'
            };

            function sanitizeCampusCode(code) {
                if (!code) return '';
                return code.replace(/[^a-zA-Z0-9]/g, '_').toUpperCase().replace(/^_+|_+$/g, '') || code.toUpperCase();
            }

            function updateUserNameOptions() {
                var selected = campusSelect.options[campusSelect.selectedIndex];
                nameSelect.innerHTML = '<option value="">Select User Name</option>';
                roleInput.value = '';
                roleDisplay.value = '';
                if (!selected || !selected.value) return;
                var code = selected.getAttribute('data-code') || selected.value;
                code = sanitizeCampusCode(code);
                if (!code) return;
                var opt1 = document.createElement('option');
                opt1.value = code + ' Planning Coordinator';
                opt1.textContent = code + ' Planning Coordinator';
                opt1.setAttribute('data-role', 'creator_editor');
                nameSelect.appendChild(opt1);
                var opt2 = document.createElement('option');
                opt2.value = code + ' QA Coordinator';
                opt2.textContent = code + ' QA Coordinator';
                opt2.setAttribute('data-role', 'admin');
                nameSelect.appendChild(opt2);
                var opt3 = document.createElement('option');
                opt3.value = code + ' CED';
                opt3.textContent = code + ' CED';
                opt3.setAttribute('data-role', 'view_only');
                nameSelect.appendChild(opt3);
                var oldName = '{{ old("name") }}';
                if (oldName && (oldName === opt1.value || oldName === opt2.value)) {
                    nameSelect.value = oldName;
                    updateRoleFromName();
                }
            }

            function updateRoleFromName() {
                var selected = nameSelect.options[nameSelect.selectedIndex];
                if (!selected || !selected.value) {
                    roleInput.value = '';
                    roleDisplay.value = '';
                    return;
                }
                var role = selected.getAttribute('data-role');
                roleInput.value = role || '';
                roleDisplay.value = roleLabels[role] || role || '';
            }

            campusSelect.addEventListener('change', function() {
                nameSelect.value = '';
                updateUserNameOptions();
            });

            nameSelect.addEventListener('change', updateRoleFromName);

            document.getElementById('create-user-form').addEventListener('submit', function() {
                var sel = nameSelect.options[nameSelect.selectedIndex];
                if (sel && sel.value && !roleInput.value && sel.getAttribute('data-role')) {
                    roleInput.value = sel.getAttribute('data-role');
                    roleDisplay.value = roleLabels[sel.getAttribute('data-role')] || '';
                }
            });

            // Initialize: if old campus selected, build options and restore name/role
            updateUserNameOptions();
        });
    </script>
</x-app-layout>
