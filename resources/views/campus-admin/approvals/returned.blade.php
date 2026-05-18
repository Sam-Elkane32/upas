<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white border border-gray-200 rounded-xl shadow-md px-6 py-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-start items-start">
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-semibold text-gray-900">Returned submissions</h2>
                        <p class="text-sm text-gray-600 mt-1">Your campus · sent back for revision · open a row to view details</p>
                    </div>
                    <a href="{{ route('campus-admin.approvals.index') }}"
                       class="inline-flex items-center gap-2 self-start shrink-0 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 bg-gray-50/80 hover:bg-gray-100 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                        <span aria-hidden="true">←</span>
                        Back to pending
                    </a>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
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
                                    <tr class="hover:bg-red-50 transition-colors duration-150">
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 font-medium">{{ $submission->form_title ?? ($submission->template ? $submission->template->sg_code . ' - ' . $submission->template_code : 'N/A') }}</div>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            @php
                                                $kpiTitle = $submission->kpi_title ?? ($submission->template ? $submission->template->kpi_title : null) ?? 'N/A';
                                            @endphp
                                            <p class="text-sm text-gray-900 leading-snug break-words line-clamp-3" title="{{ Str::limit($kpiTitle, 500) }}">{{ $kpiTitle }}</p>
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
                    <!-- Empty State (same style as index) -->
                    <div class="text-center py-16">
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">No returned submissions</h3>
                        <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">Returned submissions that need revision will appear here.</p>
                        <div class="mt-6">
                            <a href="{{ route('campus-admin.approvals.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-all duration-200 shadow-md hover:shadow-lg">
                                ← Back to Pending
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
