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
                            System Settings
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Configure system-wide settings and preferences
                        </p>
                    </div>
                </div>
            </div>
            
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- System Settings Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">General Settings</h3>
                    
                    <form method="POST" action="{{ auth()->user()->isSuperAdmin() ? route('super-admin.settings.update') : route('settings.update') }}">
                        @csrf
                        
                        <div class="space-y-6">
                            <!-- System Name -->
                            <div>
                                <label for="system_name" class="block text-sm font-medium text-gray-700">System Name</label>
                                <input type="text" name="system_name" id="system_name" value="{{ $errors->any() ? old('system_name') : ($systemName ?? 'UPAS - University Planning Accomplishment System') }}" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @error('system_name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- System Email -->
                            <div>
                                <label for="system_email" class="block text-sm font-medium text-gray-700">System Email</label>
                                <input type="email" name="system_email" id="system_email" value="{{ $errors->any() ? old('system_email') : ($systemEmail ?? 'admin@psu.edu.ph') }}" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @error('system_email')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Default Password -->
                            <div>
                                <label for="default_password" class="block text-sm font-medium text-gray-700">Default Password for New Users</label>
                                <input type="text" name="default_password" id="default_password" value="{{ $errors->any() ? old('default_password') : ($defaultPassword ?? 'UPAS@2025!') }}" required minlength="8"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">This password will be used for all newly created users. Minimum 8 characters.</p>
                                @error('default_password')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Approval Settings -->
                            <div class="space-y-4">
                                <h4 class="text-md font-medium text-gray-900">Approval Settings</h4>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" name="approval_required" id="approval_required" value="1" checked
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="approval_required" class="ml-2 block text-sm text-gray-900">
                                        Require admin approval for new users
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" name="auto_approve_creator_editors" id="auto_approve_creator_editors" value="1"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="auto_approve_creator_editors" class="ml-2 block text-sm text-gray-900">
                                        Auto-approve Creator/Editor accounts
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Save Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Campus Management -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Campus Management</h3>
                        <a href="{{ auth()->user()->isSuperAdmin() ? route('super-admin.settings.campus') : route('settings.campus') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Manage Campuses →</a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($campuses as $campus)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium text-gray-900">{{ $campus->name }}</h4>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    @if($campus->is_active) bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800 @endif">
                                    {{ $campus->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500">{{ $campus->code }} • {{ $campus->location }}</p>
                            @if($campus->description)
                                <p class="text-xs text-gray-400 mt-1">{{ $campus->description }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">System Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Application Details</h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Laravel Version:</dt>
                                    <dd class="text-sm text-gray-900">{{ app()->version() }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">PHP Version:</dt>
                                    <dd class="text-sm text-gray-900">{{ PHP_VERSION }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Environment:</dt>
                                    <dd class="text-sm text-gray-900">{{ app()->environment() }}</dd>
                                </div>
                            </dl>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Database Information</h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Database:</dt>
                                    <dd class="text-sm text-gray-900">SQLite</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Total Users:</dt>
                                    <dd class="text-sm text-gray-900">{{ \App\Models\User::count() }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Active Users:</dt>
                                    <dd class="text-sm text-gray-900">{{ \App\Models\User::where('is_active', true)->count() }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
