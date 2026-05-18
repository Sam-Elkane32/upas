                <!-- Filter & Export -->
                <div class="bg-white shadow-xl rounded-xl border border-gray-100 mb-8 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-5 border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                <h3 class="text-lg font-bold text-gray-900">QA Coordinator Reports</h3>
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ route('super-admin.campus-admin.vpass.preview') }}?format=pdf&campus_admin_filter={{ request('campus_admin_filter') }}" 
                                    target="_blank"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-md text-sm font-semibold rounded-lg text-white bg-red-600 hover:bg-red-700 transition-all duration-200">
                                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Export VPASS PDF
                                </a>
                                <a href="{{ route('super-admin.campus-admin.vpass.preview') }}?format=excel&campus_admin_filter={{ request('campus_admin_filter') }}" 
                                    target="_blank"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-md text-sm font-semibold rounded-lg text-white bg-green-600 hover:bg-green-700 transition-all duration-200">
                                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Export VPASS Excel
                                </a>
                            </div>
                        </div>
                        <form method="GET" action="{{ route('super-admin.reports.qa-coordinator') }}" class="flex items-end space-x-4">
                            <div class="flex-1">
                                <label for="campus_admin_filter" class="block text-sm font-medium text-gray-700">Filter by Campus</label>
                                <select name="campus_admin_filter" id="campus_admin_filter" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">All Campuses</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->id }}" {{ $campusAdminReports['selectedCampus'] == $campus->id ? 'selected' : '' }}>
                                            {{ $campus->name }} ({{ $campus->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="inline-flex items-center px-6 py-2.5 border border-transparent shadow-lg text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Apply Filter
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Summary Statistics Cards (neutral / professional) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white overflow-hidden rounded-2xl border border-slate-200 shadow-md hover:shadow-lg transition-shadow">
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-slate-300" aria-hidden="true"></span>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Total</span>
                                </div>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-3 text-3xl font-semibold text-slate-900 tabular-nums">{{ number_format($campusAdminReports['stats']['total_submissions']) }}</p>
                            <p class="mt-1 text-sm text-slate-600">Total submissions</p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden rounded-2xl border border-slate-200 shadow-md hover:shadow-lg transition-shadow">
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-slate-300" aria-hidden="true"></span>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Approved</span>
                                </div>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-3 text-3xl font-semibold text-slate-900 tabular-nums">{{ number_format($campusAdminReports['stats']['approved']) }}</p>
                            <p class="mt-1 text-sm text-slate-600">Approved</p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden rounded-2xl border border-slate-200 shadow-md hover:shadow-lg transition-shadow">
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-slate-300" aria-hidden="true"></span>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Pending</span>
                                </div>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-3 text-3xl font-semibold text-slate-900 tabular-nums">{{ number_format($campusAdminReports['stats']['pending_review']) }}</p>
                            <p class="mt-1 text-sm text-slate-600">Pending review</p>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden rounded-2xl border border-slate-200 shadow-md hover:shadow-lg transition-shadow">
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-slate-300" aria-hidden="true"></span>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Returned</span>
                                </div>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-3 text-3xl font-semibold text-slate-900 tabular-nums">{{ number_format($campusAdminReports['stats']['returned']) }}</p>
                            <p class="mt-1 text-sm text-slate-600">Returned</p>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Submissions Table -->
                <div class="bg-white shadow-xl rounded-xl border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-5 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 flex items-center">
                                    <svg class="w-6 h-6 text-gray-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Submissions from QA Coordinators
                                </h3>
                                <p class="mt-1 text-sm text-gray-600">All submissions across selected campuses</p>
                            </div>
                        </div>
                    </div>
                    
                    @if($campusAdminReports['submissions']->count() > 0)
                        <div class="divide-y divide-gray-200">
                            @foreach($campusAdminReports['submissions'] as $submission)
                            <div class="px-6 py-5 hover:bg-indigo-50 transition-colors duration-150">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start space-x-4">
                                            <div class="flex-shrink-0">
                                                <div class="h-12 w-12 rounded-lg bg-indigo-500 flex items-center justify-center">
                                                    <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="text-base font-bold text-gray-900 mb-4">
                                                    {{ $submission->kpi_title ?? 'N/A' }}
                                                </h4>
                                                <div class="flex flex-wrap items-center mb-4 pb-4 border-b border-gray-200 gap-x-0">
                                                    <div class="flex items-center text-sm pr-8">
                                                        <span class="text-gray-500 font-medium mr-2">SG:</span>
                                                        <span class="text-gray-900 font-semibold">{{ $submission->sg_code ?? 'N/A' }}</span>
                                                    </div>
                                                    <div class="h-5 w-px bg-gray-300 mx-5"></div>
                                                    <div class="flex items-center text-sm pr-8">
                                                        <span class="text-gray-500 font-medium mr-2">KRA:</span>
                                                        <span class="text-gray-900 font-semibold">{{ Str::limit($submission->kra_title ?? 'N/A', 30) }}</span>
                                                    </div>
                                                    <div class="h-5 w-px bg-gray-300 mx-5"></div>
                                                    <div class="flex items-center text-sm pr-8">
                                                        <span class="text-gray-500 font-medium mr-2">Campus:</span>
                                                        <span class="text-gray-900 font-semibold">{{ Str::limit($submission->campus ?? 'N/A', 25) }}</span>
                                                    </div>
                                                    <div class="h-5 w-px bg-gray-300 mx-5"></div>
                                                    <div class="flex items-center text-sm">
                                                        <span class="text-gray-500 font-medium mr-2">Quarter:</span>
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-purple-100 text-purple-800">
                                                            {{ $submission->quarter ?? 'N/A' }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-x-0 text-sm text-gray-600">
                                                    <div class="flex items-center pr-8">
                                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                        </svg>
                                                        <span class="font-medium text-gray-700">{{ $submission->submitter->name ?? 'N/A' }}</span>
                                                    </div>
                                                    <div class="h-5 w-px bg-gray-300 mx-5"></div>
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                        <span class="text-gray-700">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y') : 'N/A' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3 ml-4">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold 
                                            {{ $submission->status == 'Approved' ? 'bg-green-100 text-green-800 border border-green-200' : 
                                               ($submission->status == 'Pending Review' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 
                                               ($submission->status == 'Returned' ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-gray-100 text-gray-800 border border-gray-200')) }}">
                                            {{ $submission->status }}
                                        </span>
                                        @if($submission->is_draft)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">Draft</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Enhanced Pagination -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Showing <span class="font-semibold">{{ $campusAdminReports['submissions']->firstItem() ?? 0 }}</span> to 
                                    <span class="font-semibold">{{ $campusAdminReports['submissions']->lastItem() ?? 0 }}</span> of 
                                    <span class="font-semibold">{{ $campusAdminReports['submissions']->total() }}</span> results
                                </div>
                                <div class="flex items-center space-x-2">
                                    {{ $campusAdminReports['submissions']->appends(['campus_admin_filter' => request('campus_admin_filter')])->links() }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-16 px-6">
                            <div class="mx-auto h-24 w-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">No submissions found</h3>
                            <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">No submissions match your current filters. Try adjusting your filter criteria to see more results.</p>
                        </div>
                    @endif
                </div>
            </div>
