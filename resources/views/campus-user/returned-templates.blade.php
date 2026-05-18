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
                            RETURNED TEMPLATES
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Review and resubmit templates that were returned by QA Coordinator
                        </p>
                    </div>
                </div>
            </div>
            <!-- Page Header with Quick Actions -->
            <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">Your Returned Templates</h3>
                    <p class="text-sm text-gray-600 mt-1">Review feedback and resubmit returned submissions</p>
                </div>
                <div>
                    <a href="{{ route('campus-user.create-submission') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200 shadow-sm hover:shadow-md">
                        View All Templates
                    </a>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-orange-500">
                    <div class="p-6 text-center">
                        <p class="text-lg font-semibold text-gray-700 mb-3">Total Returned</p>
                        <p class="text-4xl font-bold text-gray-900">{{ $templates->count() }}</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-red-500">
                    <div class="p-6 text-center">
                        <p class="text-lg font-semibold text-gray-700 mb-3">Needs Action</p>
                        <p class="text-4xl font-bold text-gray-900">{{ $templates->count() }}</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-lg rounded-xl hover:shadow-xl transition-all duration-300 border-l-4 border-yellow-500">
                    <div class="p-6 text-center">
                        <p class="text-lg font-semibold text-gray-700 mb-3">Pending Resubmission</p>
                        <p class="text-4xl font-bold text-gray-900">{{ $templates->count() }}</p>
                    </div>
                </div>
            </div>

            <!-- Templates Table -->
            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Returned Templates</h3>
                    <p class="text-sm text-gray-600 mt-1">Click "Edit & Resubmit" to review feedback and update your submission</p>
                </div>
                
                @if($templates->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Template Code</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">SG Code</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">KRA Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">KPI Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Returned Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($templates as $template)
                                @php
                                    $submission = $submissionsByTemplate[$template->template_code] ?? null;
                                    $canEdit = $submission && $submission->status === 'Returned';
                                    $remarks = $submission && $submission->approval ? $submission->approval->remarks : null;
                                @endphp
                                    <tr class="hover:bg-orange-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">
                                                {{ $template->template_code }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $template->sg_code }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-xs">{{ Str::limit($template->kra_title, 50) }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $template->kpi_title }}">
                                                {{ Str::limit($template->kpi_title, 50) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($submission && $submission->updated_at)
                                                <div class="text-sm font-medium text-gray-900">{{ $submission->updated_at->format('M d, Y') }}</div>
                                            @else
                                                <div class="text-sm text-gray-400">N/A</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if($canEdit)
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('campus-user.edit-submission', $submission) }}" 
                                                       class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg hover:from-orange-600 hover:to-orange-700 transition-all duration-150 text-xs font-medium shadow-sm hover:shadow-md">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                        Edit & Resubmit
                                                    </a>
                                                    @if($remarks)
                                                    <button type="button" 
                                                            onclick="showRemarks('{{ addslashes($remarks) }}')"
                                                            class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-150 text-xs font-medium shadow-sm hover:shadow-md"
                                                            title="View Return Comments">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    </button>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400 italic">No action available</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="text-center py-16">
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">No returned templates</h3>
                        <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">You don't have any templates with returned submissions. All your submissions are either pending review or approved.</p>
                        <div class="mt-6">
                            <a href="{{ route('campus-user.create-submission') }}" 
                               class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg font-medium text-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-600 transition-all duration-200 shadow-md hover:shadow-lg">
                                View All Templates
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Remarks Modal -->
    <div id="remarksModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Return Comments</h3>
                    <button onclick="closeRemarks()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded">
                    <p id="remarksContent" class="text-sm text-gray-700 whitespace-pre-wrap"></p>
                </div>
                <div class="mt-4 flex justify-end">
                    <button onclick="closeRemarks()" class="px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showRemarks(remarks) {
            document.getElementById('remarksContent').textContent = remarks;
            document.getElementById('remarksModal').classList.remove('hidden');
        }

        function closeRemarks() {
            document.getElementById('remarksModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('remarksModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRemarks();
            }
        });
    </script>
</x-app-layout>
