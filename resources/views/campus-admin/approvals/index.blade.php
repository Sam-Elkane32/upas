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
                            APPROVAL MANAGEMENT
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Review and approve submissions from Planning Coordinators
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('campus-admin.dashboard') }}" 
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200 shadow-sm hover:shadow-md">
                            ← Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-yellow-500">
                    <div class="p-6 text-center">
                        <p class="text-lg font-semibold text-gray-700 mb-3">Pending Review</p>
                        <p class="text-4xl font-bold text-gray-900">{{ $stats['pending_review'] ?? 0 }}</p>
                        @if(($stats['pending_review'] ?? 0) > 0)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 animate-pulse mt-2">
                            Action Required
                        </span>
                        @endif
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-green-500">
                    <div class="p-6 text-center">
                        <p class="text-lg font-semibold text-gray-700 mb-3">Approved</p>
                        <p class="text-4xl font-bold text-gray-900">{{ $stats['approved'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-red-500">
                    <div class="p-6 text-center">
                        <p class="text-lg font-semibold text-gray-700 mb-3">Returned</p>
                        <p class="text-4xl font-bold text-gray-900">{{ $stats['returned'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Pending Submissions Table -->
            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Pending Submissions</h3>
                    <p class="text-sm text-gray-600 mt-1">Click on a submission to review and approve</p>
                </div>
                
                @if($submissions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Form Title
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        KPI Title
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Submitted By
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Quarter
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Submitted Date
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($submissions as $submission)
                                    <tr class="hover:bg-blue-50 transition-colors duration-150">
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 font-medium">{{ $submission->form_title ?? ($submission->template ? $submission->template->sg_code . ' - ' . $submission->template_code : 'N/A') }}</div>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            @php
                                                $kpiTitle = $submission->template->kpi_title ?? $submission->kpi_title ?? 'N/A';
                                            @endphp
                                            <div class="text-sm text-gray-900 leading-relaxed whitespace-pre-wrap break-words max-w-xl">{{ $kpiTitle }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $submission->submitter->name ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $submission->quarter }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y') : $submission->created_at->format('M d, Y') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('campus-admin.approvals.show', $submission) }}" 
                                                   class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors duration-150 text-xs font-medium">
                                                    View
                                                </a>
                                                <a href="{{ route('campus-admin.approvals.review', $submission) }}" 
                                                   class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-150 text-xs font-medium shadow-sm hover:shadow-md">
                                                    Review
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        {{ $submissions->links() }}
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="text-center py-16">
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">No pending submissions</h3>
                        <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">All submissions have been reviewed or there are no submissions yet. Check back later for new submissions.</p>
                        <div class="mt-6">
                            <a href="{{ route('campus-admin.dashboard') }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-all duration-200 shadow-md hover:shadow-lg">
                                ← Back to Dashboard
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
