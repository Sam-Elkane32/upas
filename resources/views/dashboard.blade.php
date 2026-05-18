<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    UPAS Dashboard - {{ auth()->user()->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ auth()->user()->position }} • {{ auth()->user()->departmentInfo->name ?? auth()->user()->department }}
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">
                    PSU Employee ID: {{ auth()->user()->employee_id }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-lg mb-8">
                <div class="px-6 py-8 text-white">
                    <h1 class="text-3xl font-bold mb-2">Welcome to UPAS</h1>
                    <p class="text-blue-100 text-lg">University Planning Accomplishment System - Pangasinan State University</p>
                    <p class="text-blue-200 mt-2">Automating accomplishment tracking and planning for academic excellence</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <!-- Total Plans -->
                <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Plans</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_plans'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completed Plans -->
                <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Completed</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $stats['completed_plans'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- In Progress Plans -->
                <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">In Progress</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $stats['in_progress_plans'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Plans -->
                <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-gray-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $stats['pending_plans'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overdue Plans -->
                <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Overdue</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $stats['overdue_plans'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QAR System Statistics -->
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Quarterly Accomplishment Reports (QAR)
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Current Quarter Report Status -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-blue-700">Current Quarter</p>
                                    <p class="text-lg font-bold text-blue-900">Q{{ ceil(now()->month / 3) }} {{ now()->year }}</p>
                                    @if($qarStats['current_quarter_report'])
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $qarStats['current_quarter_report']->status_color }}">
                                            {{ $qarStats['current_quarter_report']->status }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Not Started
                                        </span>
                                    @endif
                                </div>
                                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="mt-3">
                                @if($qarStats['current_quarter_report'])
                                    @if($qarStats['current_quarter_report']->isEditable())
                                        <a href="#" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Continue Report →</a>
                                    @else
                                        <span class="text-xs text-gray-500">Report {{ strtolower($qarStats['current_quarter_report']->status) }}</span>
                                    @endif
                                @else
                                    <a href="#" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Start Report →</a>
                                @endif
                            </div>
                        </div>

                        <!-- Total Reports -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gray-500 rounded-md flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total Reports</p>
                                    <p class="text-xl font-semibold text-gray-900">{{ $qarStats['total_reports'] }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Reports -->
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-green-700">Approved</p>
                                    <p class="text-xl font-semibold text-green-900">{{ $qarStats['approved_reports'] }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Reports -->
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-yellow-700">Pending Review</p>
                                    <p class="text-xl font-semibold text-yellow-900">{{ $qarStats['pending_reports'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Strategic Goals & KPIs Overview -->
            @if($departmentGoals->count() > 0)
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                        Strategic Goals & KPIs - {{ auth()->user()->departmentInfo->name ?? 'Your Department' }}
                    </h3>
                </div>
                <div class="p-6">
                    <!-- KPI Summary Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-blue-700">Total KPIs</p>
                                    <p class="text-xl font-semibold text-blue-900">{{ $kpiStats['total_kpis'] }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-green-700">Achieved</p>
                                    <p class="text-xl font-semibold text-green-900">{{ $kpiStats['achieved_kpis'] }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-red-700">Overdue</p>
                                    <p class="text-xl font-semibold text-red-900">{{ $kpiStats['overdue_kpis'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Strategic Goals Cards -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @foreach($departmentGoals->take(4) as $goal)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 mb-1">{{ $goal->title }}</h4>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $goal->category === 'Academic Excellence' ? 'blue' : ($goal->category === 'Research & Innovation' ? 'purple' : ($goal->category === 'Community Extension' ? 'green' : 'gray')) }}-100 text-{{ $goal->category === 'Academic Excellence' ? 'blue' : ($goal->category === 'Research & Innovation' ? 'purple' : ($goal->category === 'Community Extension' ? 'green' : 'gray')) }}-800">
                                        {{ $goal->category }}
                                    </span>
                                </div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $goal->status_color }}">
                                    {{ $goal->status }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-3">{{ Str::limit($goal->description, 120) }}</p>
                            
                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="flex justify-between text-xs text-gray-600 mb-1">
                                    <span>Progress</span>
                                    <span>{{ number_format($goal->progress_percentage, 1) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($goal->progress_percentage, 100) }}%"></div>
                                </div>
                            </div>
                            
                            <!-- KPIs count -->
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>{{ $goal->keyPerformanceIndicators->count() }} KPIs</span>
                                <span>Target: {{ $goal->target_year }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    @if($departmentGoals->count() > 4)
                    <div class="mt-4 text-center">
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all {{ $departmentGoals->count() }} strategic goals →</a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Admin Statistics (if applicable) -->
            @if($adminStats)
            <div class="bg-white rounded-lg shadow-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ auth()->user()->isAdmin() ? 'University Overview' : 'Department Overview' }}
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-600">{{ $adminStats['total_university_plans'] }}</p>
                            <p class="text-sm text-gray-500">Total University Plans</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600">{{ $adminStats['completed_university_plans'] }}</p>
                            <p class="text-sm text-gray-500">Completed Plans</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-600">{{ $adminStats['pending_approvals'] }}</p>
                            <p class="text-sm text-gray-500">Pending Approvals</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-purple-600">{{ $adminStats['departments_count'] }}</p>
                            <p class="text-sm text-gray-500">Active Departments</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-indigo-600">{{ $adminStats['active_users'] }}</p>
                            <p class="text-sm text-gray-500">Active Users</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-emerald-600">{{ $adminStats['total_strategic_goals'] }}</p>
                            <p class="text-sm text-gray-500">Strategic Goals</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-orange-600">{{ $adminStats['quarterly_reports_pending'] }}</p>
                            <p class="text-sm text-gray-500">QARs Pending Review</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Recent Accomplishments -->
                <div class="bg-white rounded-lg shadow-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Accomplishments</h3>
                    </div>
                    <div class="p-6">
                        @if($recentPlans->count() > 0)
                            <div class="space-y-4">
                                @foreach($recentPlans as $plan)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900">{{ $plan->title }}</h4>
                                        <p class="text-sm text-gray-500">{{ Str::limit($plan->description, 60) }}</p>
                                        <div class="flex items-center mt-2 space-x-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($plan->status === 'completed') bg-green-100 text-green-800
                                                @elseif($plan->status === 'in_progress') bg-yellow-100 text-yellow-800
                                                @elseif($plan->status === 'pending') bg-gray-100 text-gray-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ ucfirst(str_replace('_', ' ', $plan->status)) }}
                                            </span>
                                            <span class="text-xs text-gray-500">{{ $plan->target_date->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                                            <span class="text-blue-600 font-semibold">{{ $plan->progress_percentage }}%</span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No accomplishment plans</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by creating your first accomplishment plan.</p>
                                <div class="mt-6">
                                    @if(auth()->user()->canCreateForms())
                            <a href="{{ route('campus-submissions.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create New Submission
                            </a>
                        @else
                            <a href="{{ route('accomplishments.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create New Plan
                            </a>
                        @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Upcoming Deadlines - Enhanced with KPIs -->
                <div class="bg-white rounded-lg shadow-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Upcoming Deadlines</h3>
                        <div class="flex space-x-4 mt-2">
                            <button class="text-sm text-blue-600 border-b-2 border-blue-600 pb-1" id="accomplishments-tab">Accomplishments</button>
                            <button class="text-sm text-gray-500 hover:text-gray-700 pb-1" id="kpis-tab">KPIs</button>
                        </div>
                    </div>
                    <div class="p-6">
                        <!-- Accomplishments Tab -->
                        <div id="accomplishments-content">
                            @if($upcomingDeadlines->count() > 0)
                                <div class="space-y-4">
                                    @foreach($upcomingDeadlines as $plan)
                                    <div class="flex items-center justify-between p-4 border-l-4 
                                        @if($plan->target_date->diffInDays() <= 7) border-red-400 bg-red-50
                                        @elseif($plan->target_date->diffInDays() <= 14) border-yellow-400 bg-yellow-50
                                        @else border-green-400 bg-green-50 @endif">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900">{{ $plan->title }}</h4>
                                            <p class="text-sm text-gray-600">Due: {{ $plan->target_date->format('M d, Y') }}</p>
                                            <p class="text-xs 
                                                @if($plan->target_date->diffInDays() <= 7) text-red-600
                                                @elseif($plan->target_date->diffInDays() <= 14) text-yellow-600
                                                @else text-green-600 @endif">
                                                {{ $plan->target_date->diffForHumans() }}
                                            </p>
                                        </div>
                                        <div class="ml-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($plan->priority === 'urgent') bg-red-100 text-red-800
                                                @elseif($plan->priority === 'high') bg-orange-100 text-orange-800
                                                @elseif($plan->priority === 'medium') bg-yellow-100 text-yellow-800
                                                @else bg-green-100 text-green-800 @endif">
                                                {{ ucfirst($plan->priority) }}
                                            </span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming deadlines</h3>
                                    <p class="mt-1 text-sm text-gray-500">All your accomplishment plans are on track!</p>
                                </div>
                            @endif
                        </div>

                        <!-- KPIs Tab -->
                        <div id="kpis-content" class="hidden">
                            @if($upcomingKpiDeadlines->count() > 0)
                                <div class="space-y-4">
                                    @foreach($upcomingKpiDeadlines as $kpi)
                                    <div class="flex items-center justify-between p-4 border-l-4 
                                        @if($kpi->deadline->diffInDays() <= 7) border-red-400 bg-red-50
                                        @elseif($kpi->deadline->diffInDays() <= 14) border-yellow-400 bg-yellow-50
                                        @else border-green-400 bg-green-50 @endif">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900">{{ $kpi->name }}</h4>
                                            <p class="text-sm text-gray-600">{{ $kpi->strategicGoal->title }}</p>
                                            <p class="text-sm text-gray-600">Due: {{ $kpi->deadline->format('M d, Y') }}</p>
                                            <p class="text-xs 
                                                @if($kpi->deadline->diffInDays() <= 7) text-red-600
                                                @elseif($kpi->deadline->diffInDays() <= 14) text-yellow-600
                                                @else text-green-600 @endif">
                                                {{ $kpi->deadline->diffForHumans() }}
                                            </p>
                                        </div>
                                        <div class="ml-4 text-right">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $kpi->status_color }}">
                                                {{ $kpi->status }}
                                            </span>
                                            <p class="text-xs text-gray-500 mt-1">{{ number_format($kpi->achievement_percentage, 1) }}% Complete</p>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming KPI deadlines</h3>
                                    <p class="mt-1 text-sm text-gray-500">All your KPIs are on track!</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8 bg-white rounded-lg shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        @if(auth()->user()->canCreateForms())
                            <a href="{{ route('campus-submissions.create') }}" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">New Submission</h4>
                                    <p class="text-sm text-gray-500">Create SG, KRA, KPI submission</p>
                                </div>
                            </a>
                        @else
                            <a href="{{ route('accomplishments.create') }}" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">New Accomplishment</h4>
                                    <p class="text-sm text-gray-500">Create a new plan</p>
                                </div>
                            </a>
                        @endif

                        @if(auth()->user()->canCreateForms())
                            <a href="{{ route('campus-submissions.my-submissions') }}" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">My Submissions</h4>
                                    <p class="text-sm text-gray-500">View submission status</p>
                                </div>
                            </a>
                        @else
                            <a href="{{ route('accomplishments.index') }}" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-900">View All Plans</h4>
                                    <p class="text-sm text-gray-500">Manage accomplishments</p>
                                </div>
                            </a>
                        @endif

                        <a href="{{ route('reports.index') }}" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-gray-900">Generate Reports</h4>
                                <p class="text-sm text-gray-500">Export data</p>
                            </div>
                        </a>

                        <a href="{{ auth()->user()->isSuperAdmin() ? route('super-admin.profile.edit') : (auth()->user()->isAdmin() ? route('campus-admin.profile.edit') : (auth()->user()->hasRole('creator_editor') ? route('campus-user.profile.edit') : route('profile.edit'))) }}" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gray-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-gray-900">Profile Settings</h4>
                                <p class="text-sm text-gray-500">Update information</p>
                            </div>
                        </a>
                    </div>
                    
                    <!-- QAR Quick Actions -->
                    <div class="border-t border-gray-200 pt-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-4">Quarterly Accomplishment Reports (QAR)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @if($qarStats['current_quarter_report'])
                                @if($qarStats['current_quarter_report']->isEditable())
                                <a href="#" class="flex items-center p-3 bg-amber-50 rounded-lg hover:bg-amber-100 transition-colors border border-amber-200">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <h5 class="text-sm font-medium text-amber-900">Continue Q{{ ceil(now()->month / 3) }} Report</h5>
                                        <p class="text-xs text-amber-700">{{ $qarStats['current_quarter_report']->status }}</p>
                                    </div>
                                </a>
                                @else
                                <div class="flex items-center p-3 bg-green-50 rounded-lg border border-green-200">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <h5 class="text-sm font-medium text-green-900">Q{{ ceil(now()->month / 3) }} Report</h5>
                                        <p class="text-xs text-green-700">{{ $qarStats['current_quarter_report']->status }}</p>
                                    </div>
                                </div>
                                @endif
                            @else
                            <a href="#" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors border border-blue-200">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <h5 class="text-sm font-medium text-blue-900">Start Q{{ ceil(now()->month / 3) }} Report</h5>
                                    <p class="text-xs text-blue-700">Create quarterly report</p>
                                </div>
                            </a>
                            @endif

                            <a href="#" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors border border-purple-200">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <h5 class="text-sm font-medium text-purple-900">View KPIs</h5>
                                    <p class="text-xs text-purple-700">Track performance indicators</p>
                                </div>
                            </a>

                            <a href="#" class="flex items-center p-3 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition-colors border border-emerald-200">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <h5 class="text-sm font-medium text-emerald-900">Strategic Goals</h5>
                                    <p class="text-xs text-emerald-700">Department objectives</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Tab Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const accomplishmentsTab = document.getElementById('accomplishments-tab');
            const kpisTab = document.getElementById('kpis-tab');
            const accomplishmentsContent = document.getElementById('accomplishments-content');
            const kpisContent = document.getElementById('kpis-content');

            accomplishmentsTab.addEventListener('click', function() {
                // Switch to accomplishments tab
                accomplishmentsTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                accomplishmentsTab.classList.remove('text-gray-500');
                kpisTab.classList.add('text-gray-500');
                kpisTab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                
                accomplishmentsContent.classList.remove('hidden');
                kpisContent.classList.add('hidden');
            });

            kpisTab.addEventListener('click', function() {
                // Switch to KPIs tab
                kpisTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                kpisTab.classList.remove('text-gray-500');
                accomplishmentsTab.classList.add('text-gray-500');
                accomplishmentsTab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                
                kpisContent.classList.remove('hidden');
                accomplishmentsContent.classList.add('hidden');
            });
        });
    </script>
</x-app-layout>
