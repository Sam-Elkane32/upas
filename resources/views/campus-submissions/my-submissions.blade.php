<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    My Submissions
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    View and manage your submitted forms
                </p>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('campus-submissions.create') }}" 
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    New Submission
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $submissions->where('status', 'pending')->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Approved</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $submissions->where('status', 'approved')->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Returned</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $submissions->where('status', 'returned')->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
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
                                <p class="text-sm font-medium text-gray-500">Total</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $submissions->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submissions Table -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Your Submissions</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">All your submitted forms and their current status</p>
                </div>
                
                @if($submissions->count() > 0)
                    <ul class="divide-y divide-gray-200">
                        @foreach($submissions as $submission)
                        <li class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h4 class="text-sm font-medium text-gray-900 truncate">
                                                {{ $submission->strategic_goal }}
                                            </h4>
                                            <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                                <span><strong>KRA:</strong> {{ $submission->kra }}</span>
                                                <span><strong>KPI:</strong> {{ $submission->kpi }}</span>
                                            </div>
                                            <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                                <span><strong>Target:</strong> {{ number_format($submission->target_value, 2) }}</span>
                                                <span><strong>Actual:</strong> {{ number_format($submission->actual_value, 2) }}</span>
                                                <span><strong>Achievement:</strong> {{ number_format($submission->achievement_percentage, 1) }}%</span>
                                            </div>
                                            @if($submission->admin_remarks)
                                                <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded">
                                                    <p class="text-sm text-yellow-800">
                                                        <strong>Admin Remarks:</strong> {{ $submission->admin_remarks }}
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $submission->status_color }}">
                                                {{ $submission->status_text }}
                                            </span>
                                            <div class="flex items-center space-x-2">
                                                @if($submission->is_editable)
                                                    <a href="{{ route('campus-submissions.edit', $submission) }}" 
                                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                        Edit
                                                    </a>
                                                    <form method="POST" action="{{ route('campus-submissions.destroy', $submission) }}" 
                                                        class="inline" 
                                                        data-confirm="Are you sure you want to delete this submission?">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($submission->file_path)
                                                    <a href="{{ route('campus-submissions.download-file', $submission) }}" 
                                                        class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                        Download File
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                Submitted: {{ $submission->created_at->format('M d, Y H:i') }}
                                @if($submission->approved_at)
                                    | Approved: {{ $submission->approved_at->format('M d, Y H:i') }}
                                @endif
                                @if($submission->returned_at)
                                    | Returned: {{ $submission->returned_at->format('M d, Y H:i') }}
                                @endif
                            </div>
                        </li>
                        @endforeach
                    </ul>

                    <!-- Pagination -->
                    <div class="px-4 py-3 border-t border-gray-200">
                        {{ $submissions->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No submissions</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating your first submission.</p>
                        <div class="mt-6">
                            <a href="{{ route('campus-submissions.create') }}" 
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create New Submission
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
