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
                            Edit User
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Update user information and settings
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ auth()->user()->isSuperAdmin() ? route('super-admin.users.show', $user) : route('campus-admin.users.show', $user) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            View User
                        </a>
                        <a href="{{ auth()->user()->isSuperAdmin() ? route('super-admin.users') : route('campus-admin.users') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Back to Users
                        </a>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ auth()->user()->isSuperAdmin() ? route('super-admin.users.update', $user) : route('campus-admin.users.update', $user) }}" id="edit-user-form">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- 1) Campus (required) -->
                            <div>
                                <label for="campus_code" class="block text-sm font-medium text-gray-700">Campus <span class="text-red-500">*</span></label>
                                <select name="campus_code" id="campus_code" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->code }}" data-code="{{ $campusCodeMap[$campus->code] ?? $campus->code }}" {{ old('campus_code', $user->campus_code) === $campus->code ? 'selected' : '' }}>
                                            {{ $campus->name }} ({{ $campus->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">User Name options update based on the selected campus.</p>
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
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Choose from generated options (e.g. LINGAYEN Planning Coordinator, LINGAYEN QA Coordinator).</p>
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 3) Email Address (required) -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="e.g. user@psu.edu.ph">
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- 4) Employee ID (read-only, edit only) -->
                            <div>
                                <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee ID</label>
                                <input type="text" id="employee_id" value="{{ $user->employee_id }}" readonly
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-600 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">Employee ID cannot be changed.</p>
                            </div>

                            <input type="hidden" name="role" id="role" value="{{ old('role', $user->role) }}">
                            @error('role')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Active User (edit only) -->
                        <div class="mt-6">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                    Active User
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Update User
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

            var currentName = @json(old('name', $user->name));
            var currentRole = @json(old('role', $user->role));
            var currentCampusCode = @json(old('campus_code', $user->campus_code));

            function sanitizeCampusCode(code) {
                if (!code) return '';
                return String(code).replace(/[^a-zA-Z0-9]/g, '_').toUpperCase().replace(/^_+|_+$/g, '') || String(code).toUpperCase();
            }

            function updateUserNameOptions() {
                var selected = campusSelect.options[campusSelect.selectedIndex];
                nameSelect.innerHTML = '<option value="">Select User Name</option>';
                roleInput.value = '';
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

                var currentOption = (currentCampusCode === selected.value && currentName) ? currentName : null;
                if (currentOption && currentOption !== opt1.value && currentOption !== opt2.value) {
                    var optCurrent = document.createElement('option');
                    optCurrent.value = currentOption;
                    optCurrent.textContent = currentOption + ' (current)';
                    optCurrent.setAttribute('data-role', currentRole || '');
                    nameSelect.insertBefore(optCurrent, nameSelect.options[1]);
                }

                if (currentCampusCode === selected.value && currentName) {
                    var opts = nameSelect.querySelectorAll('option');
                    for (var i = 0; i < opts.length; i++) {
                        if (opts[i].value === currentName) {
                            nameSelect.selectedIndex = i;
                            roleInput.value = opts[i].getAttribute('data-role') || currentRole;
                            break;
                        }
                    }
                }
            }

            function updateRoleFromName() {
                var selected = nameSelect.options[nameSelect.selectedIndex];
                if (!selected || !selected.value) {
                    roleInput.value = '';
                    return;
                }
                var role = selected.getAttribute('data-role');
                roleInput.value = role || '';
            }

            campusSelect.addEventListener('change', function() {
                nameSelect.value = '';
                updateUserNameOptions();
            });

            nameSelect.addEventListener('change', updateRoleFromName);

            document.getElementById('edit-user-form').addEventListener('submit', function() {
                var sel = nameSelect.options[nameSelect.selectedIndex];
                if (sel && sel.value && !roleInput.value && sel.getAttribute('data-role')) {
                    roleInput.value = sel.getAttribute('data-role');
                }
            });

            updateUserNameOptions();
        });
    </script>
</x-app-layout>
