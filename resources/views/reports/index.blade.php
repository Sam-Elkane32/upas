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
                            Reports & Analytics
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            @if(auth()->user()->isSuperAdmin())
                                University-wide reports and analytics
                            @elseif(auth()->user()->isAdmin())
                                Campus reports for {{ auth()->user()->campus }}
                            @else
                                Your personal reports and submissions
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Filters Section -->
            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Reports</h3>
                    <form method="GET" action="{{ auth()->user()->isAdmin() ? route('campus-admin.reports') : (auth()->user()->isCreatorEditor() ? route('campus-user.reports') : route('reports.index')) }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @if(auth()->user()->isSuperAdmin())
                        <!-- Date From -->
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700">From Date</label>
                            <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        
                        <!-- Date To -->
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700">To Date</label>
                            <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        
                        <!-- Quarter -->
                        <div>
                            <label for="quarter" class="block text-sm font-medium text-gray-700">Quarter</label>
                            <select name="quarter" id="quarter"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">All Quarters</option>
                                @foreach($availableQuarters ?? [] as $quarter)
                                    <option value="{{ $quarter }}" {{ ($filters['quarter'] ?? '') === $quarter ? 'selected' : '' }}>{{ $quarter }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">All Statuses</option>
                                @foreach($availableStatuses ?? [] as $status)
                                    <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        
                        @if(auth()->user()->isAdmin())
                        <!-- Form Title (Office/Unit Name) -->
                        <div>
                            <label for="campus_admin_form_title" class="block text-sm font-medium text-gray-700">
                                Form Title (Office/Unit Name) <span class="text-red-500">*</span>
                            </label>
                            <select name="form_title" id="campus_admin_form_title" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Select Form Title...</option>
                                @foreach($availableFormTitles ?? [] as $formTitle)
                                    <option value="{{ $formTitle }}" {{ ($filters['form_title'] ?? '') === $formTitle ? 'selected' : '' }}>{{ $formTitle }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Strategic Goal -->
                        <div>
                            <label for="campus_admin_sg_code" class="block text-sm font-medium text-gray-700">
                                Strategic Goal <span class="text-red-500">*</span>
                            </label>
                            <select name="sg_code" id="campus_admin_sg_code" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Select Strategic Goal...</option>
                                @foreach($availableSGs ?? [] as $sg)
                                    <option value="{{ $sg }}" {{ ($filters['sg_code'] ?? '') === $sg ? 'selected' : '' }}>{{ $sg }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- KRA Title -->
                        <div>
                            <label for="campus_admin_kra_title" class="block text-sm font-medium text-gray-700">
                                KRA Title <span class="text-red-500">*</span>
                            </label>
                            <select name="kra_title" id="campus_admin_kra_title" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                {{ empty($filters['sg_code']) ? 'disabled' : '' }}>
                                <option value="">Select KRA Title...</option>
                                @if(!empty($filters['sg_code']))
                                    @foreach($availableKRAs ?? [] as $kra)
                                        <option value="{{ $kra }}" {{ ($filters['kra_title'] ?? '') === $kra ? 'selected' : '' }}>{{ $kra }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        @endif
                        
                        <!-- Action Buttons -->
                        <div class="flex items-end space-x-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Apply Filters
                            </button>
                            <a href="{{ auth()->user()->isAdmin() ? route('campus-admin.reports') : (auth()->user()->isCreatorEditor() ? route('campus-user.reports') : route('reports.index')) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            <!-- Filters Section for Planning Coordinators -->
            @if(auth()->user()->isCreatorEditor())
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Filter Reports</h3>
                </div>
                <form method="GET" action="{{ route('campus-user.reports') }}" class="space-y-4" id="campusUserFilterForm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Strategic Goal (SG) -->
                        <div>
                            <label for="campus_user_sg_code" class="block text-sm font-medium text-gray-700 mb-2">Strategic Goal (SG)</label>
                            <select name="sg_code" id="campus_user_sg_code"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 px-3">
                                <option value="">All SGs</option>
                                @foreach($availableSGs ?? [] as $sg)
                                    <option value="{{ $sg }}" {{ ($filters['sg_code'] ?? '') === $sg ? 'selected' : '' }}>{{ $sg }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- KRA Title -->
                        <div>
                            <label for="campus_user_kra_title" class="block text-sm font-medium text-gray-700 mb-2">Key Result Area (KRA)</label>
                            <select name="kra_title" id="campus_user_kra_title"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 px-3">
                                <option value="">All KRAs</option>
                                @foreach($availableKRAs ?? [] as $kra)
                                    <option value="{{ $kra }}" {{ ($filters['kra_title'] ?? '') === $kra ? 'selected' : '' }}>{{ $kra }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Template Code -->
                        <div>
                            <label for="campus_user_template_code" class="block text-sm font-medium text-gray-700 mb-2">Template Code</label>
                            <select name="template_code" id="campus_user_template_code"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 px-3">
                                <option value="">All Template Codes</option>
                                @foreach($availableTemplateCodes ?? [] as $templateCode)
                                    <option value="{{ $templateCode }}" {{ ($filters['template_code'] ?? '') === $templateCode ? 'selected' : '' }}>{{ $templateCode }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 pt-2">
                        <button type="submit" 
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-sm font-semibold rounded-lg hover:from-blue-600 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 shadow-md hover:shadow-lg">
                            APPLY FILTERS
                        </button>
                        <a href="{{ route('campus-user.reports') }}" 
                            class="px-6 py-2.5 bg-gray-600 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200 shadow-md hover:shadow-lg">
                            CLEAR
                        </a>
                    </div>
                </form>
            </div>
            @endif

            <!-- Performance Overview Section -->
            <div class="mb-8">
                @if(auth()->user()->isCreatorEditor())
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Performance Overview</h3>
                </div>
                @else
                <h3 class="text-lg font-medium text-gray-900 mb-6">Performance Overview</h3>
                @endif
                
                <!-- Statistics Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    @if(auth()->user()->isCreatorEditor())
                    <!-- Pending Review Card -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-yellow-500">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-600">Pending Review</p>
                                    <p class="text-3xl font-bold text-gray-900">{{ $stats['pending_review'] ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Approved Card -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-green-500">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-600">Approved</p>
                                    <p class="text-3xl font-bold text-gray-900">{{ $stats['approved'] ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Returned Card -->
                    <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-red-500">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-600">Returned</p>
                                    <p class="text-3xl font-bold text-gray-900">{{ $stats['returned'] ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Pending Review</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ $stats['pending_review'] ?? 0 }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Approved</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ $stats['approved'] ?? 0 }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Returned</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ $stats['returned'] ?? 0 }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Performance Metrics -->
                @if(isset($performanceMetrics))
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Performance Metrics</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Approval Rate</span>
                                <span class="text-lg font-bold text-indigo-600">
                                    {{ $performanceMetrics['approval_rate'] ?? 0 }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-indigo-600 h-3 rounded-full transition-all duration-500" style="width: {{ min($performanceMetrics['approval_rate'] ?? 0, 100) }}%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Success Rate</span>
                                <span class="text-lg font-bold text-green-600">
                                    {{ $performanceMetrics['success_rate'] ?? 0 }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-green-600 h-3 rounded-full transition-all duration-500" style="width: {{ min($performanceMetrics['success_rate'] ?? 0, 100) }}%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Return Rate</span>
                                <span class="text-lg font-bold text-red-600">
                                    {{ $performanceMetrics['return_rate'] ?? 0 }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-red-600 h-3 rounded-full transition-all duration-500" style="width: {{ min($performanceMetrics['return_rate'] ?? 0, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @elseif(auth()->user()->isCreatorEditor())
                <!-- Performance Metrics for Planning Coordinator (calculated from stats) -->
                @php
                    $totalSubs = $stats['total_submissions'] ?? 0;
                    $approvalRate = $totalSubs > 0 ? round(($stats['approved'] ?? 0) / $totalSubs * 100, 1) : 0;
                    $successRate = $totalSubs > 0 ? round((($stats['approved'] ?? 0) + ($stats['pending_review'] ?? 0)) / $totalSubs * 100, 1) : 0;
                    $returnRate = $totalSubs > 0 ? round(($stats['returned'] ?? 0) / $totalSubs * 100, 1) : 0;
                @endphp
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Performance Metrics</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Approval Rate</span>
                                <span class="text-lg font-bold text-indigo-600">{{ $approvalRate }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-indigo-600 h-3 rounded-full transition-all duration-500" style="width: {{ min($approvalRate, 100) }}%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Success Rate</span>
                                <span class="text-lg font-bold text-green-600">{{ $successRate }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-green-600 h-3 rounded-full transition-all duration-500" style="width: {{ min($successRate, 100) }}%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Return Rate</span>
                                <span class="text-lg font-bold text-red-600">{{ $returnRate }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-red-600 h-3 rounded-full transition-all duration-500" style="width: {{ min($returnRate, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Submissions by Quarter - Bar Chart -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Submissions by Quarter</h3>
                        </div>
                        <div class="{{ auth()->user()->isCreatorEditor() || auth()->user()->isAdmin() ? 'p-14' : 'p-6' }}">
                            @if(isset($quarterlyStats) && $quarterlyStats->count() > 0)
                                <div style="position: relative; height: {{ auth()->user()->isCreatorEditor() || auth()->user()->isAdmin() ? '180px' : '300px' }}; width: 100%;">
                                    <canvas id="quarterChart"></canvas>
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No quarterly data available</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Submissions by Status - Pie Chart -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Submissions by Status</h3>
                        </div>
                        <div class="{{ auth()->user()->isCreatorEditor() || auth()->user()->isAdmin() ? 'p-10' : 'p-6' }}">
                            @if(isset($statusStats) && $statusStats->count() > 0)
                                <div style="position: relative; height: {{ auth()->user()->isCreatorEditor() || auth()->user()->isAdmin() ? '180px' : '300px' }}; width: 100%;">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No status data available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- KPI by SG (QA Coordinator only) -->
                @if(isset($kpiBySG) && count($kpiBySG) > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">KPI Accomplishment by Strategic Goal</h3>
                        <div class="space-y-4">
                            @foreach($kpiBySG as $sg => $data)
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-900">{{ $sg }}</span>
                                    <span class="text-sm text-gray-600">{{ $data['average_rate'] }}% ({{ $data['total'] }} submissions)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-500" style="width: {{ min($data['average_rate'], 100) }}%"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Export Options -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Export Reports</h3>
                    
                    @if(auth()->user()->isAdmin())
                        <!-- QA Coordinator Export Section -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6 mb-4">
                            <!-- Export Buttons Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <a href="{{ route('campus-admin.reports.export.vpass.preview', array_merge(['format' => 'pdf'], request()->only(['form_title', 'sg_code', 'kra_title']))) }}" 
                                    class="w-full inline-flex items-center justify-center px-6 py-3 bg-red-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wide hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 shadow-md transition ease-in-out duration-150">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Export PDF
                                </a>
                                
                                <a href="{{ route('campus-admin.reports.export.vpass.preview', array_merge(['format' => 'excel'], request()->only(['form_title', 'sg_code', 'kra_title']))) }}" 
                                    class="w-full inline-flex items-center justify-center px-6 py-3 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wide hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 shadow-md transition ease-in-out duration-150">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Export Excel
                                </a>
                            </div>
                        </div>
                    @endif
                    
                    @if(auth()->user()->isCreatorEditor())
                        <!-- Planning Coordinator Export Section -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6 mb-4">
                            <!-- Export Buttons Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <a href="{{ route('campus-user.reports.export.preview') }}?format=pdf&{{ http_build_query($filters) }}" 
                                    class="w-full inline-flex items-center justify-center px-6 py-3 bg-red-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wide hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 shadow-md transition ease-in-out duration-150">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Export PDF
                                </a>
                                
                                <a href="{{ route('campus-user.reports.tsheet.preview') }}?{{ http_build_query($filters) }}" 
                                    class="w-full inline-flex items-center justify-center px-6 py-3 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wide hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 shadow-md transition ease-in-out duration-150">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    EXPORT EXCEL
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Quarter Chart (Bar Chart)
        @if(isset($quarterlyStats) && $quarterlyStats->count() > 0)
        const quarterCtx = document.getElementById('quarterChart');
        if (quarterCtx) {
            const quarterData = @json($quarterlyStats);
            const quarterLabels = Object.keys(quarterData);
            const quarterValues = Object.values(quarterData);
            
            new Chart(quarterCtx, {
                type: 'bar',
                data: {
                    labels: quarterLabels,
                    datasets: [{
                        label: 'Submissions',
                        data: quarterValues,
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: {{ auth()->user()->isCreatorEditor() || auth()->user()->isAdmin() ? '2.2' : '1.5' }},
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Submissions: ' + context.parsed.y;
                                }
                            }
                        }
                    }
                }
            });
        }
        @endif

        // Status Chart (Pie Chart)
        @if(isset($statusStats) && $statusStats->count() > 0)
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            const statusData = @json($statusStats);
            const statusLabels = Object.keys(statusData);
            const statusValues = Object.values(statusData);
            
            // Color mapping for statuses
            const statusColors = {
                'Pending Review': 'rgba(234, 179, 8, 0.8)',
                'Approved': 'rgba(34, 197, 94, 0.8)',
                'Returned': 'rgba(239, 68, 68, 0.8)',
                'Unpublished': 'rgba(107, 114, 128, 0.8)'
            };
            
            const backgroundColors = statusLabels.map(label => statusColors[label] || 'rgba(156, 163, 175, 0.8)');
            
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusValues,
                        backgroundColor: backgroundColors,
                        borderWidth: 1,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: {{ auth()->user()->isCreatorEditor() || auth()->user()->isAdmin() ? '2.2' : '1.5' }},
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
        @endif

        // Planning Coordinator Filter - Simple (no cascading needed since all options are shown)
        @if(auth()->user()->isCreatorEditor())
        (function() {
            // All dropdowns are now independent and show all available options
            // No cascading logic needed
        })();
        @endif
        
        @if(auth()->user()->isAdmin())
        // QA Coordinator Filter Script
        (function() {
            const sgSelect = document.getElementById('campus_admin_sg_code');
            const kraSelect = document.getElementById('campus_admin_kra_title');
            
            if (!sgSelect || !kraSelect) return;
            
            // Get available KRAs by SG from server
            @php
                $kraBySG = [];
                if (isset($availableKRAs) && isset($availableSGs)) {
                    $campusForms = \App\Models\Form::where('campus_code', auth()->user()->campus_code)->get();
                    foreach ($availableSGs as $sg) {
                        $kraBySG[$sg] = $campusForms->where('sg_code', $sg)
                            ->whereNotNull('kra_title')
                            ->pluck('kra_title')
                            ->unique()
                            ->sort()
                            ->values()
                            ->toArray();
                    }
                }
            @endphp
            const kraBySG = @json($kraBySG ?? []);
            
            // Handle SG selection
            sgSelect.addEventListener('change', function() {
                const selectedSG = this.value;
                
                // Reset KRA
                kraSelect.innerHTML = '<option value="">Select KRA Title...</option>';
                kraSelect.disabled = !selectedSG;
                
                if (selectedSG && kraBySG[selectedSG]) {
                    // Get KRAs for selected SG
                    kraBySG[selectedSG].forEach(kra => {
                        const option = document.createElement('option');
                        option.value = kra;
                        option.textContent = kra;
                        kraSelect.appendChild(option);
                    });
                    
                    kraSelect.disabled = false;
                }
            });
            
            // Trigger change on page load if SG is preselected
            if (sgSelect.value) {
                sgSelect.dispatchEvent(new Event('change'));
            }
        })();
        @endif
    </script>
</x-app-layout>
