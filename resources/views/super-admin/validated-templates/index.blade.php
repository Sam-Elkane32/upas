<x-app-layout>
    <x-slot name="header"><div></div></x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('super-admin.partials.page-header', [
                'title' => 'Validated Templates',
                'subtitle' => 'Manage validated templates and edit accomplishment values. Only Super Admin can edit these templates.',
                'backUrl' => route('super-admin.dashboard'),
            ])

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 border-l-4 border-indigo-500">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Validated</p>
                        <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $stats['total_validated'] ?? 0 }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 border-l-4 border-green-500">
                    <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Campuses</p>
                        <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $submissions->pluck('campus')->unique()->count() }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4 border-l-4 border-purple-500">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Showing</p>
                        <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $submissions->count() }}</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <button type="button" id="toggle-filters-btn"
                    class="w-full flex items-center justify-between px-6 py-4 text-left focus:outline-none">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                        <span class="text-sm font-semibold text-gray-900">Filters</span>
                        @php
                            $activeFilters = collect($filters)->filter(fn($v) => $v !== '' && $v !== null)->count();
                        @endphp
                        @if($activeFilters > 0)
                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{{ $activeFilters }} active</span>
                        @endif
                    </div>
                    <svg id="filter-chevron" class="w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="filters-panel" class="{{ $activeFilters > 0 ? '' : 'hidden' }} border-t border-gray-100 px-6 py-5">
                    <form method="GET" action="{{ route('super-admin.validated-templates.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="campus" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Campus</label>
                            <select name="campus" id="campus" class="block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Campuses</option>
                                @foreach($campuses as $campus)
                                    <option value="{{ $campus->name }}" {{ $filters['campus'] == $campus->name ? 'selected' : '' }}>{{ $campus->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="sg_code" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Strategic Goal</label>
                            <select name="sg_code" id="sg_code" class="block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Strategic Goals</option>
                                @foreach($strategicGoals as $sg)
                                    <option value="{{ $sg }}" {{ $filters['sg_code'] == $sg ? 'selected' : '' }}>{{ $sg }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="template_code" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Template Code</label>
                            <select name="template_code" id="template_code" class="block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Templates</option>
                                @foreach($templateCodes as $code)
                                    <option value="{{ $code }}" {{ $filters['template_code'] == $code ? 'selected' : '' }}>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="kra_title" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">KRA Title</label>
                            <select name="kra_title" id="kra_title" class="block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All KRAs</option>
                                @foreach($kraTitles as $kra)
                                    <option value="{{ $kra }}" {{ $filters['kra_title'] == $kra ? 'selected' : '' }}>{{ $kra }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="kpi_title" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">KPI Title</label>
                            <select name="kpi_title" id="kpi_title" class="block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All KPIs</option>
                                @foreach($kpiTitles as $kpi)
                                    <option value="{{ $kpi }}" {{ $filters['kpi_title'] == $kpi ? 'selected' : '' }}>{{ $kpi }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="quarter" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Quarter</label>
                            <select name="quarter" id="quarter" class="block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Quarters</option>
                                @foreach($quarters as $q)
                                    <option value="{{ $q }}" {{ $filters['quarter'] == $q ? 'selected' : '' }}>{{ $q }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3 flex items-center gap-2 pt-1">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                Apply Filters
                            </button>
                            <a href="{{ route('super-admin.validated-templates.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Clear All
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Validated Templates Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/80 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Validated Templates</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Click any row to view details and edit accomplishment values</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {{ $submissions->total() }} {{ Str::plural('record', $submissions->total()) }}
                    </span>
                </div>

                @if($submissions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-10">#</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Template</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">KRA / KPI</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Campus</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Quarter</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Submitted By</th>
                                    <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Validated</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($submissions as $index => $submission)
                                    <tr class="hover:bg-indigo-50/30 transition-colors cursor-pointer group"
                                        onclick="window.location='{{ route('super-admin.validated-templates.show', $submission) }}'">
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-400 font-medium">
                                            {{ $submissions->firstItem() + $loop->index }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-100 text-blue-800">
                                                {{ $submission->template_code }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-sm max-w-xs">
                                            <div class="font-medium text-gray-900 truncate max-w-[220px]" title="{{ $submission->kra_title ?? 'N/A' }}">
                                                {{ $submission->kra_title ?? 'N/A' }}
                                            </div>
                                            <div class="text-gray-500 text-xs mt-0.5 truncate max-w-[220px]" title="{{ $submission->kpi_title ?? 'N/A' }}">
                                                {{ $submission->kpi_title ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                            {{ $submission->campus }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                                {{ $submission->quarter }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 shrink-0">
                                                    {{ strtoupper(substr($submission->submitter->name ?? 'N', 0, 1)) }}
                                                </div>
                                                {{ $submission->submitter->name ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                            @if($submission->approval && $submission->approval->validated_at)
                                                <div class="flex items-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    {{ $submission->approval->validated_at->format('M d, Y') }}
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-xs">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-4 border-t border-gray-200 bg-gray-50/50 flex items-center justify-between">
                        <p class="text-xs text-gray-500">
                            Showing {{ $submissions->firstItem() }}–{{ $submissions->lastItem() }} of {{ $submissions->total() }} records
                        </p>
                        {{ $submissions->links() }}
                    </div>
                @else
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900">No validated templates found</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            @if(collect($filters)->filter(fn($v) => $v !== '' && $v !== null)->count() > 0)
                                No results match your current filters. <a href="{{ route('super-admin.validated-templates.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Clear filters</a>
                            @else
                                No templates have been validated by QA Coordinators yet.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            const btn = document.getElementById('toggle-filters-btn');
            const panel = document.getElementById('filters-panel');
            const chevron = document.getElementById('filter-chevron');
            if (btn && panel) {
                btn.addEventListener('click', function () {
                    const hidden = panel.classList.toggle('hidden');
                    chevron.style.transform = hidden ? '' : 'rotate(180deg)';
                });
                // Init chevron state
                if (!panel.classList.contains('hidden')) {
                    chevron.style.transform = 'rotate(180deg)';
                }
            }
        })();
    </script>
</x-app-layout>

