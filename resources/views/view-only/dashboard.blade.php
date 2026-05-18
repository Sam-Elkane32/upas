<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:justify-between lg:items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    @if(auth()->user()->isDivisionLevelViewOnly())
                        Division Dashboard
                    @else
                        CED Dashboard
                    @endif
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    @if(auth()->user()->restrictsViewOnlyToSingleCampus())
                        Campus accomplishment snapshot (read-only)
                    @else
                        University-wide approved accomplishments (read-only)
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('view-only.submissions.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Submissions
                </a>
                <a href="{{ route('view-only.templates.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Templates
                </a>
                <a href="{{ route('view-only.forms.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-700 focus:bg-teal-700 active:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Forms
                </a>
                <a href="{{ route('view-only.summary.index') }}#exports" 
                   class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:bg-amber-700 active:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Export
                </a>
                <a href="{{ route('view-only.summary.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Summary
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Read-Only Notice -->
            <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="font-medium">Read-only mode: you can review approved submissions, published templates and forms, and download reports. Editing and approvals are disabled.</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Approved Submissions</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_approved_submissions'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Published Templates</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_templates'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('view-only.forms.index') }}" class="block bg-white overflow-hidden shadow rounded-lg ring-1 ring-transparent hover:ring-blue-200 hover:shadow-md transition">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Published Forms <span class="text-blue-600 font-normal">→ browse</span></p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_forms'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                </a>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Campuses</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_campuses'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($campusDetails))
            <div class="bg-white shadow-sm rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        @if(!empty($campusDetails['scope_is_university_wide']))
                            Accomplishment overview
                        @else
                            Campus accomplishment details
                        @endif
                    </h3>
                    <p class="text-sm text-gray-600 mb-6">
                        <span class="font-medium text-gray-800">{{ $campusDetails['campus_name'] ?? 'N/A' }}</span>
                        —
                        {{ $campusDetails['total_strategic_goals'] ?? 0 }} strategic goals and
                        {{ $campusDetails['total_kra_areas'] ?? 0 }} KRA areas in approved submissions.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Active Quarters</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $campusDetails['active_quarters'] ?? 0 }}</p>
                        </div>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Latest Approved Submission</p>
                            <p class="text-sm font-semibold text-gray-900 mt-1">
                                {{ !empty($campusDetails['latest_submission_at']) ? \Carbon\Carbon::parse($campusDetails['latest_submission_at'])->format('M d, Y') : 'No approved submissions yet' }}
                            </p>
                        </div>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ !empty($campusDetails['scope_is_university_wide']) ? 'Data scope' : 'Campus' }}</p>
                            <p class="text-sm font-semibold text-gray-900 mt-1">{{ $campusDetails['campus_name'] ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Quarterly Accomplishments</h4>
                            <div class="space-y-2">
                                @foreach(($campusDetails['quarter_breakdown'] ?? []) as $quarter => $count)
                                <div class="flex items-center justify-between border border-gray-100 rounded px-3 py-2">
                                    <span class="text-sm text-gray-700">{{ $quarter }}</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $count }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Top Strategic Goals</h4>
                            <div class="space-y-2 mb-4">
                                @forelse(($campusDetails['top_strategic_goals'] ?? []) as $goal)
                                <div class="flex items-center justify-between border border-gray-100 rounded px-3 py-2">
                                    <span class="text-sm text-gray-700">{{ $goal['sg_code'] }}</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $goal['total'] }}</span>
                                </div>
                                @empty
                                <p class="text-sm text-gray-500">No strategic goal data yet.</p>
                                @endforelse
                            </div>

                            <h4 class="text-sm font-semibold text-gray-800 mb-3">Top KRA Areas</h4>
                            <div class="space-y-2">
                                @forelse(($campusDetails['top_kra_areas'] ?? []) as $kra)
                                <div class="flex items-center justify-between border border-gray-100 rounded px-3 py-2">
                                    <span class="text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($kra['kra_title'], 55) }}</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $kra['total'] }}</span>
                                </div>
                                @empty
                                <p class="text-sm text-gray-500">No KRA data yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Campus Performance -->
            @if(!empty($campusPerformance))
            <div class="bg-white shadow-sm rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Campus Performance Overview</h3>
                    <div class="space-y-4">
                        @foreach($campusPerformance as $campus)
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">{{ $campus['name'] }}</span>
                                <span class="text-sm text-gray-600">{{ $campus['submissions_count'] }} approved submissions</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, ($campus['submissions_count'] / max(1, $stats['total_approved_submissions'])) * 100) }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Approved Submissions -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Approved Submissions</h3>
                    @if($recentSubmissions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quarter</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted At</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($recentSubmissions as $submission)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $submission->campus }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $submission->template_code }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $submission->quarter }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $submission->submitter->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y') : 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="{{ route('view-only.submissions.show', $submission->id) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-gray-500 text-center py-8">No approved submissions found.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

