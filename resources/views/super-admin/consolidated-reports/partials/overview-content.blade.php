            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6 overflow-hidden" x-data="{ filtersOpen: true }">
                <div class="bg-gradient-to-r from-slate-50 to-gray-50/80 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Advanced Filters</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Refine overview by campus, date, status, and more</p>
                            </div>
                        </div>
                        <button type="button" @click="filtersOpen = !filtersOpen"
                                class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-white/80 transition-all duration-200"
                                :aria-expanded="filtersOpen">
                            <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': filtersOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-6 transition-all duration-200" id="filterContent" x-show="filtersOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <form method="GET" action="{{ route('super-admin.reports.overview') }}" id="filterForm">
                        {{-- Location & Date --}}
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-1 h-4 bg-indigo-500 rounded-full"></span>
                                Location &amp; Date
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                                <!-- Campuses (dropdown) -->
                                <div class="lg:col-span-1" x-data="{ campusDropdownOpen: false }" @click.outside="campusDropdownOpen = false">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Campuses</label>
                                    <div class="relative">
                                        <button type="button"
                                                @click="campusDropdownOpen = !campusDropdownOpen"
                                                class="w-full flex items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-left text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 hover:bg-gray-50 transition-colors">
                                            <span id="campus_dropdown_label" class="text-gray-700 truncate">All campuses</span>
                                            <svg class="w-4 h-4 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': campusDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <div x-show="campusDropdownOpen"
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             class="absolute z-20 mt-1 w-full min-w-[16rem] rounded-lg border border-gray-200 bg-white py-2 shadow-lg">
                                            <div class="px-3 pb-2 border-b border-gray-100">
                                                <input type="text" id="campus_search" placeholder="Search campuses..." 
                                                    class="w-full rounded-md border border-gray-200 px-2.5 py-2 text-sm placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                    @click.stop>
                                            </div>
                                            <div class="px-3 py-2">
                                                <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 rounded px-2 py-1.5 -mx-2">
                                                    <input type="checkbox" id="select_all_campuses" onclick="toggleAllCampuses(this.checked)"
                                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                                    <span class="text-sm font-medium text-gray-700">Select all</span>
                                                </label>
                                            </div>
                                            <div class="max-h-56 overflow-y-auto px-3 pb-2" id="campus_checkbox_list">
                                                @foreach($campuses as $campus)
                                                    <label class="flex items-center gap-2 py-2 px-2 rounded hover:bg-gray-50 cursor-pointer campus-item -mx-2" data-name="{{ strtolower($campus->name . ' ' . $campus->code) }}">
                                                        <input type="checkbox" name="campuses[]" id="campus_{{ $campus->id }}"
                                                            value="{{ $campus->id }}"
                                                            class="campus-checkbox h-4 w-4 shrink-0 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                            {{ in_array($campus->id, (array)($filters['campuses'] ?? [])) ? 'checked' : '' }}>
                                                        <span class="text-sm text-gray-700 break-words min-w-0">{{ $campus->name }} ({{ $campus->code }})</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="px-3 py-2 border-t border-gray-100 bg-gray-50 rounded-b-lg">
                                                <p class="text-xs text-gray-500"><span id="campus_selected_count">0</span> selected</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Date Preset -->
                                <div>
                                    <label for="date_preset" class="block text-sm font-medium text-gray-700 mb-2">Date Preset</label>
                                    <select name="date_preset" id="date_preset"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2.5">
                                        <option value="">Custom range</option>
                                        <option value="this_month" {{ ($filters['date_preset'] ?? '') == 'this_month' ? 'selected' : '' }}>This month</option>
                                        <option value="last_month" {{ ($filters['date_preset'] ?? '') == 'last_month' ? 'selected' : '' }}>Last month</option>
                                        <option value="this_quarter" {{ ($filters['date_preset'] ?? '') == 'this_quarter' ? 'selected' : '' }}>This quarter</option>
                                        <option value="last_quarter" {{ ($filters['date_preset'] ?? '') == 'last_quarter' ? 'selected' : '' }}>Last quarter</option>
                                        <option value="this_year" {{ ($filters['date_preset'] ?? '') == 'this_year' ? 'selected' : '' }}>This year</option>
                                        <option value="last_year" {{ ($filters['date_preset'] ?? '') == 'last_year' ? 'selected' : '' }}>Last year</option>
                                    </select>
                                </div>

                                <!-- Date From / To -->
                                <div>
                                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">Date from</label>
                                    <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2.5">
                                </div>
                                <div>
                                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">Date to</label>
                                    <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2.5">
                                </div>
                            </div>
                        </div>

                        {{-- Status & Role --}}
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-1 h-4 bg-indigo-500 rounded-full"></span>
                                Status &amp; Role
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5">
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select name="status" id="status"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2.5">
                                        <option value="">All statuses</option>
                                        <option value="Pending Review" {{ ($filters['status'] ?? '') == 'Pending Review' ? 'selected' : '' }}>Pending Review</option>
                                        <option value="Approved" {{ ($filters['status'] ?? '') == 'Approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="Returned" {{ ($filters['status'] ?? '') == 'Returned' ? 'selected' : '' }}>Returned</option>
                                        <option value="Unpublished" {{ ($filters['status'] ?? '') == 'Unpublished' ? 'selected' : '' }}>Unpublished</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="user_role" class="block text-sm font-medium text-gray-700 mb-2">User role</label>
                                    <select name="user_role" id="user_role"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2.5">
                                        <option value="">All roles</option>
                                        <option value="campus_admin" {{ ($filters['user_role'] ?? '') == 'campus_admin' ? 'selected' : '' }}>QA Coordinator</option>
                                        <option value="campus_user" {{ ($filters['user_role'] ?? '') == 'campus_user' ? 'selected' : '' }}>Planning Coordinator</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Content (SG, KRA, KPI, Template, Quarter) --}}
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-1 h-4 bg-indigo-500 rounded-full"></span>
                                Content
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5">
                                <div>
                                    <label for="sg_code" class="block text-sm font-medium text-gray-700 mb-2">Strategic goal</label>
                                    <select name="sg_code" id="sg_code"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2.5">
                                        <option value="">All SGs</option>
                                        @foreach($strategicGoals as $sg)
                                            <option value="{{ $sg }}" {{ ($filters['sg_code'] ?? '') == $sg ? 'selected' : '' }}>{{ $sg }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="kra_title" class="block text-sm font-medium text-gray-700 mb-2">KRA title</label>
                                    <input type="text" name="kra_title" id="kra_title" value="{{ $filters['kra_title'] ?? '' }}"
                                        placeholder="Search KRA…"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2.5 placeholder-gray-400">
                                </div>
                                <div>
                                    <label for="kpi_title" class="block text-sm font-medium text-gray-700 mb-2">KPI title</label>
                                    <input type="text" name="kpi_title" id="kpi_title" value="{{ $filters['kpi_title'] ?? '' }}"
                                        placeholder="Search KPI…"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2.5 placeholder-gray-400">
                                </div>
                                <div>
                                    <label for="template_code" class="block text-sm font-medium text-gray-700 mb-2">Template code</label>
                                    <select name="template_code" id="template_code"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2.5">
                                        <option value="">All templates</option>
                                        @foreach($templateCodes as $code)
                                            <option value="{{ $code }}" {{ ($filters['template_code'] ?? '') == $code ? 'selected' : '' }}>{{ $code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="quarter" class="block text-sm font-medium text-gray-700 mb-2">Quarter</label>
                                    <select name="quarter" id="quarter"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm py-2.5">
                                        <option value="">All quarters</option>
                                        <option value="1st Q" {{ ($filters['quarter'] ?? '') == '1st Q' ? 'selected' : '' }}>1st Quarter</option>
                                        <option value="2nd Q" {{ ($filters['quarter'] ?? '') == '2nd Q' ? 'selected' : '' }}>2nd Quarter</option>
                                        <option value="3rd Q" {{ ($filters['quarter'] ?? '') == '3rd Q' ? 'selected' : '' }}>3rd Quarter</option>
                                        <option value="4th Q" {{ ($filters['quarter'] ?? '') == '4th Q' ? 'selected' : '' }}>4th Quarter</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-200">
                            <button type="submit"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Apply filters
                            </button>
                            <a href="{{ route('super-admin.reports.overview') }}"
                                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition-colors">
                                Clear filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var campusSearch = document.getElementById('campus_search');
                var campusList = document.getElementById('campus_checkbox_list');
                var campusItems = campusList ? campusList.querySelectorAll('.campus-item') : [];
                var campusCountEl = document.getElementById('campus_selected_count');
                var campusLabelEl = document.getElementById('campus_dropdown_label');

                function updateCampusCount() {
                    var cbs = document.querySelectorAll('.campus-checkbox:checked');
                    var n = cbs.length;
                    if (campusCountEl) campusCountEl.textContent = n;
                    if (campusLabelEl) campusLabelEl.textContent = n === 0 ? 'All campuses' : (n === 1 ? '1 campus selected' : n + ' campuses selected');
                }
                function filterCampuses() {
                    var q = (campusSearch && campusSearch.value) ? campusSearch.value.toLowerCase().trim() : '';
                    campusItems.forEach(function(el) {
                        var show = !q || (el.getAttribute('data-name') || '').indexOf(q) !== -1;
                        el.style.display = show ? '' : 'none';
                    });
                }
                if (campusSearch) campusSearch.addEventListener('input', filterCampuses);
                document.querySelectorAll('.campus-checkbox').forEach(function(cb) {
                    cb.addEventListener('change', updateCampusCount);
                });
                updateCampusCount();
            });
            </script>

            <!-- Summary Statistics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden border-l-4 border-blue-500 hover:shadow-lg transition-shadow">
                    <div class="p-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_submissions'] ?? 0) }}</p>
                        <p class="text-sm text-gray-600 mt-0.5">Submissions</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden border-l-4 border-amber-500 hover:shadow-lg transition-shadow">
                    <div class="p-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pending</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['pending_submissions'] ?? 0) }}</p>
                        <p class="text-sm text-gray-600 mt-0.5">Pending Review</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden border-l-4 border-green-500 hover:shadow-lg transition-shadow">
                    <div class="p-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Approved</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['approved_submissions'] ?? 0) }}</p>
                        <p class="text-sm text-gray-600 mt-0.5">Approved</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden border-l-4 border-red-500 hover:shadow-lg transition-shadow">
                    <div class="p-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Returned</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['returned_submissions'] ?? 0) }}</p>
                        <p class="text-sm text-gray-600 mt-0.5">Returned</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden border-l-4 border-indigo-500 hover:shadow-lg transition-shadow">
                    <div class="p-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Average</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['overall_achievement'] ?? 0, 1) }}%</p>
                        <p class="text-sm text-gray-600 mt-0.5">Avg. Achievement</p>
                    </div>
                </div>
            </div>

            <!-- Enhanced Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Monthly Trend Chart -->
                <div class="bg-white overflow-hidden shadow-xl rounded-xl border border-gray-100">
                    <div class="bg-blue-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                            </svg>
                            Monthly Submission Trends
                        </h3>
                    </div>
                    <div class="p-6">
                        @if(isset($trends['monthly']) && count($trends['monthly']) > 0)
                            <div style="position: relative; height: 250px; width: 100%;">
                                <canvas id="monthlyTrendChart"></canvas>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="mx-auto h-16 w-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-gray-900">No monthly trend data available</p>
                                <p class="text-xs text-gray-500 mt-1">Data will appear here once submissions are available</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Quarterly Trend Chart -->
                <div class="bg-white overflow-hidden shadow-xl rounded-xl border border-gray-100">
                    <div class="bg-green-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Quarterly Performance
                        </h3>
                    </div>
                    <div class="p-6">
                        @if(isset($trends['quarterly']) && count($trends['quarterly']) > 0)
                            <div style="position: relative; height: 250px; width: 100%;">
                                <canvas id="quarterlyTrendChart"></canvas>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="mx-auto h-16 w-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-gray-900">No quarterly trend data available</p>
                                <p class="text-xs text-gray-500 mt-1">Data will appear here once submissions are available</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Status Distribution Pie Chart -->
                <div class="bg-white overflow-hidden shadow-xl rounded-xl border border-gray-100">
                    <div class="bg-purple-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                            </svg>
                            Status Distribution
                        </h3>
                    </div>
                    <div class="p-6">
                        @if(isset($stats) && $stats['total_submissions'] > 0)
                            <div class="flex justify-center items-center">
                                <div style="position: relative; height: 250px; width: 250px; max-width: 100%;">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="mx-auto h-16 w-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-gray-900">No status data available</p>
                                <p class="text-xs text-gray-500 mt-1">Data will appear here once submissions are available</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Campus Performance Heatmap -->
                <div class="bg-white overflow-hidden shadow-xl rounded-xl border border-gray-100">
                    <div class="bg-orange-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Campus Performance Heatmap
                        </h3>
                    </div>
                    <div class="p-6">
                        @if(isset($stats['campus_stats']) && $stats['campus_stats']->count() > 0)
                            <div class="space-y-2">
                                @foreach($stats['campus_stats'] as $campusStat)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-900">{{ $campusStat->campus_name }}</span>
                                    <div class="flex-1 mx-4">
                                        <div class="bg-gray-200 rounded-full h-4">
                                            <div class="h-4 rounded-full {{ $campusStat->avg_achievement >= 100 ? 'bg-green-500' : ($campusStat->avg_achievement >= 80 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                                 style="width: {{ min($campusStat->avg_achievement, 100) }}%"></div>
                                        </div>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ number_format($campusStat->avg_achievement, 1) }}%</span>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="mx-auto h-16 w-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-gray-900">No campus performance data available</p>
                                <p class="text-xs text-gray-500 mt-1">Data will appear here once submissions are available</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Enhanced KPI Analytics Section -->
            @if(isset($kpiAnalytics) && count($kpiAnalytics['best_performing']) > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Best Performing KPIs -->
                <div class="bg-white overflow-hidden shadow-xl rounded-xl border border-gray-100">
                    <div class="bg-green-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                            </svg>
                            Top 10 Best Performing KPIs
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-green-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">KPI Title</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Achievement</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Submissions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach(array_slice($kpiAnalytics['best_performing'], 0, 10) as $kpi)
                                    <tr class="hover:bg-green-50 transition-colors duration-150">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ Str::limit($kpi['kpi_title'], 50) }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
                                                {{ number_format($kpi['avg_achievement'], 1) }}%
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-700">{{ $kpi['approved_submissions'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Worst Performing KPIs -->
                <div class="bg-white overflow-hidden shadow-xl rounded-xl border border-gray-100">
                    <div class="bg-red-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            Top 10 KPIs Needing Improvement
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-red-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">KPI Title</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Achievement</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Submissions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach(array_slice($kpiAnalytics['worst_performing'], 0, 10) as $kpi)
                                    <tr class="hover:bg-red-50 transition-colors duration-150">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ Str::limit($kpi['kpi_title'], 50) }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                                                {{ number_format($kpi['avg_achievement'], 1) }}%
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-700">{{ $kpi['approved_submissions'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Enhanced Campus Performance Table -->
            <div class="bg-white shadow-xl rounded-xl border border-gray-100 mb-8 overflow-hidden">
                <div class="bg-indigo-50 px-6 py-5 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center">
                        <svg class="w-6 h-6 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Campus Performance
                    </h3>
                </div>
                <div class="px-6 py-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Campus</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Pending</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Approved</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Returned</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Achievement %</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @if(isset($stats['campus_stats']) && $stats['campus_stats']->count() > 0)
                                    @foreach($stats['campus_stats'] as $campusStat)
                                    <tr class="hover:bg-indigo-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-semibold text-gray-900">{{ $campusStat->campus_name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-semibold text-gray-900">{{ number_format($campusStat->total_submissions) }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                                {{ number_format($campusStat->pending_submissions) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                {{ number_format($campusStat->approved_submissions) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                                {{ number_format($campusStat->returned_submissions) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-1 bg-gray-200 rounded-full h-2 mr-3 max-w-xs">
                                                    <div class="h-2 rounded-full {{ $campusStat->avg_achievement >= 100 ? 'bg-green-500' : ($campusStat->avg_achievement >= 80 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                                         style="width: {{ min($campusStat->avg_achievement, 100) }}%"></div>
                                                </div>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold 
                                                    {{ $campusStat->avg_achievement >= 100 ? 'bg-green-100 text-green-800' : 
                                                       ($campusStat->avg_achievement >= 80 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                    {{ number_format($campusStat->avg_achievement, 1) }}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center">
                                            <div class="mx-auto h-16 w-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 inline-flex">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-gray-900">No campus data available</p>
                                            <p class="text-xs text-gray-500 mt-1">Data will appear here once submissions are available</p>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
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
                                Submissions
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">All submissions matching your filters</p>
                        </div>
                    </div>
                </div>
                
                @if($submissions->count() > 0)
                    <div class="divide-y divide-gray-200">
                        @foreach($submissions as $submission)
                        <div class="px-6 py-5 hover:bg-indigo-50 transition-colors duration-150" data-submission-id="{{ $submission->id }}">
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
                                                @if($submission->approval && $submission->approval->rate)
                                                    <div class="h-5 w-px bg-gray-300 mx-5"></div>
                                                    <div class="flex items-center">
                                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                                        </svg>
                                                        <span class="font-semibold text-indigo-600">{{ number_format($submission->approval->rate, 1) }}%</span>
                                                    </div>
                                                @endif
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
                                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200" title="Planning Coordinator work in progress">Draft</span>
                                    @endif
                                    @if(!$submission->template)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-orange-100 text-orange-800 border border-orange-200" title="Template no longer exists">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                            Orphaned
                                        </span>
                                    @endif
                                    <button type="button" 
                                            onclick="deleteSubmission({{ $submission->id }}, '{{ $submission->template_code ?? 'N/A' }}')"
                                            class="inline-flex items-center px-3 py-2 border border-red-300 text-xs font-semibold rounded-lg text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Enhanced Pagination -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <span class="font-semibold">{{ $submissions->firstItem() ?? 0 }}</span> to 
                                <span class="font-semibold">{{ $submissions->lastItem() ?? 0 }}</span> of 
                                <span class="font-semibold">{{ $submissions->total() }}</span> results
                            </div>
                            <div class="flex items-center space-x-2">
                                {{ $submissions->links() }}
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
                        <button onclick="document.getElementById('filterContent').scrollIntoView({ behavior: 'smooth' })" 
                                class="mt-6 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Adjust Filters
                        </button>
                    </div>
                @endif
            </div>
