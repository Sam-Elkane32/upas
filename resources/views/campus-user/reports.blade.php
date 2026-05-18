<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Reports & Analytics</h2>
                    <p class="text-sm text-gray-600 mt-1">Your personal reports and submissions</p>
                </div>
            </div>

            <!-- Filter Reports Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Filter Reports</h3>
                </div>
                <form method="GET" action="{{ route('campus-user.reports') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Strategic Goal (SG) -->
                        <div>
                            <label for="sg_code" class="block text-sm font-medium text-gray-700 mb-2">Strategic Goal (SG)</label>
                            <select name="sg_code" id="sg_code"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 px-3">
                                <option value="">All SGs</option>
                                @foreach($availableSGs ?? [] as $sg)
                                    <option value="{{ $sg }}" {{ ($filters['sg_code'] ?? '') === $sg ? 'selected' : '' }}>{{ $sg }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Key Result Area (KRA) -->
                        <div>
                            <label for="kra_title" class="block text-sm font-medium text-gray-700 mb-2">Key Result Area (KRA)</label>
                            <select name="kra_title" id="kra_title"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 px-3">
                                <option value="">All KRAs</option>
                                @foreach($availableKRAs ?? [] as $kra)
                                    <option value="{{ $kra }}" {{ ($filters['kra_title'] ?? '') === $kra ? 'selected' : '' }}>{{ $kra }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Template Code -->
                        <div>
                            <label for="template_code" class="block text-sm font-medium text-gray-700 mb-2">Template Code</label>
                            <select name="template_code" id="template_code"
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

            <!-- Performance Overview Section -->
            <div class="mb-8">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Performance Overview</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                                    <p class="text-3xl font-bold text-gray-900">{{ $stats['pending_review'] }}</p>
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
                                    <p class="text-3xl font-bold text-gray-900">{{ $stats['approved'] }}</p>
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
                                    <p class="text-3xl font-bold text-gray-900">{{ $stats['returned'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Performance Metrics</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Approval Rate -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Approval Rate</span>
                            <span class="text-lg font-bold text-indigo-600">
                                {{ $stats['total_submissions'] > 0 ? round(($stats['approved'] / $stats['total_submissions']) * 100, 1) : 0 }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-indigo-600 h-3 rounded-full transition-all duration-500" 
                                 style="width: {{ $stats['total_submissions'] > 0 ? min(($stats['approved'] / $stats['total_submissions']) * 100, 100) : 0 }}%"></div>
                        </div>
                    </div>
                    
                    <!-- Success Rate -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Success Rate</span>
                            <span class="text-lg font-bold text-green-600">
                                {{ $stats['total_submissions'] > 0 ? round((($stats['approved'] + $stats['pending_review']) / $stats['total_submissions']) * 100, 1) : 0 }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-green-600 h-3 rounded-full transition-all duration-500" 
                                 style="width: {{ $stats['total_submissions'] > 0 ? min((($stats['approved'] + $stats['pending_review']) / $stats['total_submissions']) * 100, 100) : 0 }}%"></div>
                        </div>
                    </div>
                    
                    <!-- Return Rate -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Return Rate</span>
                            <span class="text-lg font-bold text-red-600">
                                {{ $stats['total_submissions'] > 0 ? round(($stats['returned'] / $stats['total_submissions']) * 100, 1) : 0 }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-red-600 h-3 rounded-full transition-all duration-500" 
                                 style="width: {{ $stats['total_submissions'] > 0 ? min(($stats['returned'] / $stats['total_submissions']) * 100, 100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Submissions by Quarter -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Submissions by Quarter</h3>
                    </div>
                    <div class="p-6">
                        @if($quarterlyStats->count() > 0)
                            <div class="space-y-4">
                                @foreach($quarterlyStats as $quarter => $count)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700">{{ $quarter }}</span>
                                        <div class="flex items-center flex-1 mx-4">
                                            <div class="flex-1 bg-gray-200 rounded-full h-3 mr-3">
                                                <div class="bg-gradient-to-r from-purple-500 to-pink-600 h-3 rounded-full transition-all duration-500" 
                                                     style="width: {{ $stats['total_submissions'] > 0 ? min(($count / $stats['total_submissions']) * 100, 100) : 0 }}%"></div>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-900 w-12 text-right">{{ $count }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">No submissions data available</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Submissions by Status -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Submissions by Status</h3>
                    </div>
                    <div class="p-6">
                        @if($statusStats->count() > 0)
                            <div class="space-y-4">
                                @foreach($statusStats as $status => $count)
                                    @php
                                        $statusColor = match($status) {
                                            'Approved' => 'from-green-500 to-emerald-600',
                                            'Pending Review' => 'from-yellow-500 to-amber-600',
                                            'Returned' => 'from-red-500 to-rose-600',
                                            default => 'from-gray-500 to-gray-600',
                                        };
                                    @endphp
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700">{{ $status }}</span>
                                        <div class="flex items-center flex-1 mx-4">
                                            <div class="flex-1 bg-gray-200 rounded-full h-3 mr-3">
                                                <div class="bg-gradient-to-r {{ $statusColor }} h-3 rounded-full transition-all duration-500" 
                                                     style="width: {{ $stats['total_submissions'] > 0 ? min(($count / $stats['total_submissions']) * 100, 100) : 0 }}%"></div>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-900 w-12 text-right">{{ $count }}</span>
                                        </div>
                                    </div>
                                @endforeach
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

            <!-- Quick Actions Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Create New Submission Card -->
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 border-2 border-blue-200 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="mb-4">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900">Create New Submission</h4>
                            <p class="text-sm text-gray-600 mt-1">Submit new accomplishment data</p>
                        </div>
                        <a href="{{ route('campus-user.create-submission') }}" 
                           class="block w-full mt-4 text-center px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-600 text-white font-semibold rounded-lg hover:from-blue-600 hover:to-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            Get Started →
                        </a>
                    </div>
                </div>

                <!-- View All Submissions Card -->
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="mb-4">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900">View All Submissions</h4>
                            <p class="text-sm text-gray-600 mt-1">Track your submission status</p>
                        </div>
                        <a href="{{ route('campus-user.create-submission') }}" 
                           class="block w-full mt-4 text-center px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-lg hover:from-green-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            View All →
                        </a>
                    </div>
                </div>

                <!-- Dashboard Card -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 border-2 border-purple-200 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-6">
                        <div class="mb-4">
                            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-gray-900">Dashboard</h4>
                            <p class="text-sm text-gray-600 mt-1">Return to main dashboard</p>
                        </div>
                        <a href="{{ route('campus-user.dashboard') }}" 
                           class="block w-full mt-4 text-center px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white font-semibold rounded-lg hover:from-purple-600 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            Go to Dashboard →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
