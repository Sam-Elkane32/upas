<x-app-layout>
    <x-slot name="header"><div></div></x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('super-admin.partials.page-header', [
                'title' => 'All Approvals',
                'subtitle' => 'Review and manage approvals across all campuses',
            ])

            <!-- Statistics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden border-l-4 border-blue-500">
                    <div class="p-5 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden border-l-4 border-amber-500">
                    <div class="p-5 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Pending</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['pending'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden border-l-4 border-green-500">
                    <div class="p-5 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Approved</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['approved'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden border-l-4 border-red-500">
                    <div class="p-5 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Returned</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['returned'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-5 mb-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Filters</h3>
                <form method="GET" action="{{ route('super-admin.approvals.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                        <select name="campus" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus->name }}" {{ request('campus') == $campus->name ? 'selected' : '' }}>{{ $campus->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="flex flex-col justify-end">
                        <button type="submit" class="inline-flex justify-center items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Apply filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Submissions Table -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                @if($submissions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Submission ID</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Campus</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Template</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Submitted By</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($submissions as $submission)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-5 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $submission->submission_id ?? 'N/A' }}</td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">{{ $submission->campus ?? 'N/A' }}</td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">{{ $submission->template_code ?? 'N/A' }}</td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600">{{ $submission->submitter->name ?? 'N/A' }}</td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            @if($submission->status === 'Approved')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>
                                            @elseif($submission->status === 'Pending Review')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Pending</span>
                                            @elseif($submission->status === 'Returned')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Returned</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('super-admin.approvals.show', $submission) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg transition-colors">View</a>
                                                @if($submission->status === 'Pending Review')
                                                    <a href="{{ route('super-admin.approvals.review', $submission) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-green-600 hover:text-green-800 hover:bg-green-50 rounded-lg transition-colors">Review</a>
                                                @elseif($submission->approval)
                                                    <a href="{{ route('super-admin.approvals.edit', $submission) }}" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">Edit</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-4 border-t border-gray-200 bg-gray-50/50">
                        {{ $submissions->links() }}
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <p class="text-gray-500">No submissions found.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

