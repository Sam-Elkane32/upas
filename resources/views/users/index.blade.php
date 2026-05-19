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
                            User Management
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            @if(auth()->user()->isSuperAdmin())
                                Manage all users across all campuses
                            @else
                                Manage users for {{ auth()->user()->campus }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <a href="{{ auth()->user()->isSuperAdmin() ? route('super-admin.users.create') : route('campus-admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add New User
                        </a>
                    </div>
                </div>
            </div>

            <!-- Users grouped by campus -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($superAdmins->isNotEmpty())
                        <!-- Super Administrators (Super Admin view only) -->
                        <div class="mb-6">
                            <details class="group border border-gray-200 rounded-lg overflow-hidden">
                                <summary class="flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 cursor-pointer list-none">
                                    <span class="font-medium text-gray-900">Super Administrators</span>
                                    <span class="text-sm text-gray-500">{{ $superAdmins->count() }} user(s)</span>
                                </summary>
                                <div class="border-t border-gray-200 bg-white">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($superAdmins as $u)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2">
                                                    <div class="text-sm font-medium text-gray-900">{{ $u->name }}</div>
                                                    <div class="text-xs text-gray-500">{{ $u->email }} · {{ $u->employee_id }}</div>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $u->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $u->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <a href="{{ route('super-admin.users.show', $u) }}" class="text-blue-600 hover:text-blue-900 text-sm">View</a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        </div>
                    @endif

                    @if(auth()->user()->isSuperAdmin() && isset($divisionUsers) && $divisionUsers->isNotEmpty())
                        <!-- Division Accounts (Super Admin view only) -->
                        <div class="mb-6">
                            <details class="group border border-gray-200 rounded-lg overflow-hidden">
                                <summary class="flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 cursor-pointer list-none">
                                    <span class="font-medium text-gray-900">Division Accounts (View Only)</span>
                                    <span class="text-sm text-gray-500">{{ $divisionUsers->count() }} user(s)</span>
                                </summary>
                                <div class="border-t border-gray-200 bg-white">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Division</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($divisionUsers as $u)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2">
                                                    <div class="text-sm font-medium text-gray-900">{{ $u->name }}</div>
                                                    <div class="text-xs text-gray-500">{{ $u->email }} · {{ $u->employee_id }}</div>
                                                </td>
                                                <td class="px-4 py-2 text-sm text-gray-700">
                                                    {{ optional($u->departmentInfo)->name ?? 'Division Office' }}
                                                </td>
                                                <td class="px-4 py-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $u->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $u->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <a href="{{ route('super-admin.users.show', $u) }}" class="text-blue-600 hover:text-blue-900 text-sm">View</a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        </div>
                    @endif

                    @if(auth()->user()->isSuperAdmin() && isset($developerUsers) && $developerUsers->isNotEmpty())
                        <!-- Developer accounts (Messages-only beta testers) -->
                        <div class="mb-6">
                            <details class="group border border-purple-200 rounded-lg overflow-hidden">
                                <summary class="flex items-center justify-between px-4 py-3 bg-purple-50 hover:bg-purple-100 cursor-pointer list-none">
                                    <span class="font-medium text-gray-900">Developer Accounts</span>
                                    <span class="text-sm text-gray-500">{{ $developerUsers->count() }} user(s)</span>
                                </summary>
                                <div class="border-t border-purple-200 bg-white">
                                    <p class="px-4 py-2 text-xs text-gray-500 border-b border-gray-100">
                                        Beta / support accounts with Messages-only access. Not campus staff.
                                    </p>
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($developerUsers as $u)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2">
                                                    <div class="text-sm font-medium text-gray-900">{{ $u->name }}</div>
                                                    <div class="text-xs text-gray-500">{{ $u->email }} · {{ $u->employee_id }}</div>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                        Developer
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $u->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $u->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2">
                                                    <div class="flex items-center gap-3">
                                                        <a href="{{ route('super-admin.users.show', $u) }}" class="text-blue-600 hover:text-blue-900 text-sm">View</a>
                                                        <form method="POST" action="{{ route('super-admin.users.destroy', $u) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this developer account?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        </div>
                    @endif

                    @if(!$campuses || count($campuses) === 0)
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No campuses found</h3>
                            <p class="mt-1 text-sm text-gray-500">No active campuses to display.</p>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($campuses as $campus)
                                @php
                                    $campusUsers = collect($usersByCampus->get($campus->code, []));
                                @endphp
                                <details class="group border border-gray-200 rounded-lg overflow-hidden">
                                    <summary class="flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 cursor-pointer list-none">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-5 h-5 text-gray-400 transition group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                            <div>
                                                <span class="font-medium text-gray-900">{{ $campus->name }}</span>
                                                <span class="ml-2 text-sm text-gray-500">{{ $campus->code }}</span>
                                            </div>
                                        </div>
                                        <span class="text-sm text-gray-500">{{ $campusUsers->count() }} user(s)</span>
                                    </summary>
                                    <div class="border-t border-gray-200 bg-white">
                                        @if($campusUsers->isEmpty())
                                            <div class="px-4 py-6 text-center text-sm text-gray-500">
                                                No users for this campus.
                                                <a href="{{ auth()->user()->isSuperAdmin() ? route('super-admin.users.create') : route('campus-admin.users.create') }}?campus={{ $campus->code }}" class="text-blue-600 hover:text-blue-800 ml-1">Add user</a>
                                            </div>
                                        @else
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    @foreach($campusUsers as $u)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-4 py-2">
                                                            <div class="text-sm font-medium text-gray-900">{{ $u->name }}</div>
                                                            <div class="text-xs text-gray-500">{{ $u->email }} · {{ $u->employee_id }}</div>
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                                @if($u->role === 'admin')
                                                                    bg-blue-100 text-blue-800
                                                                @elseif($u->role === 'view_only')
                                                                    bg-yellow-100 text-yellow-800
                                                                @else
                                                                    bg-green-100 text-green-800
                                                                @endif">
                                                                @if($u->role === 'admin')
                                                                    QA Coordinator
                                                                @elseif($u->role === 'view_only')
                                                                    Campus CED (View Only)
                                                                @else
                                                                    Planning Coordinator
                                                                @endif
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $u->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                                {{ $u->is_active ? 'Active' : 'Inactive' }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            <div class="flex items-center gap-3">
                                                                <a href="{{ auth()->user()->isSuperAdmin() ? route('super-admin.users.show', $u) : route('campus-admin.users.show', $u) }}" class="text-blue-600 hover:text-blue-900 text-sm">View</a>
                                                                <form method="POST" action="{{ auth()->user()->isSuperAdmin() ? route('super-admin.users.destroy', $u) : route('campus-admin.users.destroy', $u) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        details summary::-webkit-details-marker { display: none; }
    </style>
</x-app-layout>
