<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- success / error / info: layouts.flash-popup (toast, auto-dismiss ~3s) --}}
            @auth
                @if(auth()->user()->isPlanningCoordinator())
                    <!-- Floating Header Section -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                                    TEMPLATES
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    View and manage your assigned and returned templates
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs for Assigned and Returned -->
                    <div class="mb-6 bg-white rounded-xl shadow-lg border border-gray-200">
                        <div class="border-b border-gray-200">
                            <nav class="flex -mb-px" aria-label="Tabs">
                                <button onclick="showSection('assigned')" id="tab-assigned" class="tab-button active flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors duration-150 border-indigo-500 text-indigo-600">
                                    Assigned Templates
                                </button>
                                <button onclick="showSection('returned')" id="tab-returned" class="tab-button flex-1 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors duration-150 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                    Returned Templates
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Assigned Templates Section -->
                    <div id="section-assigned" class="template-section">
                        <!-- Info Notice -->
                        <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="font-medium">Click "Open Template" to create a new submission. Use the Form / Source column to pick the correct form when multiple rows share the same template code.</p>
                            </div>
                        </div>

                        <!-- Assigned Templates Table -->
                        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                            @if(isset($assignedTemplates) && $assignedTemplates->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template Code</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Form / Source</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SG Code</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KRA Title</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KPI Title</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($assignedTemplates as $template)
                                            @php
                                                $submission = $assignedSubmissionsByTemplate[$template->id] ?? null;
                                                $submissionStatus = strtolower(trim((string) ($submission->status ?? '')));
                                                $isApproved = $submissionStatus === 'approved';
                                                $isLocked = (bool) $template->is_locked;
                                                $canEdit = !$isLocked && $submission && $submissionStatus === 'unpublished';
                                                $canView = $submission && !$canEdit && $submissionStatus !== 'returned';
                                                $canCreate = !$isLocked && !$submission && $template->status === 'Published';
                                            @endphp
                                            <tr class="{{ $isLocked ? 'bg-red-50 opacity-75' : 'hover:bg-gray-50' }} transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {{ $template->template_code }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                                                    <span title="{{ $template->form ? $template->form->form_title : 'Standalone template' }}">
                                                        {{ $template->form ? Str::limit($template->form->form_title, 35) : '—' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $template->campus_code ?? 'All Campuses' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $template->sg_code }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900">
                                                    {{ Str::limit($template->kra_title, 50) }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900">
                                                    {{ Str::limit($template->kpi_title, 50) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($isLocked)
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4"/>
                                                            </svg>
                                                            Locked
                                                        </span>
                                                    @elseif($template->status === 'Published')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            Published
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            Unpublished
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if($isLocked)
                                                        <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 border border-red-200 text-red-600 text-xs font-medium rounded-md cursor-not-allowed" title="This template has been locked by the administrator.">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4"/>
                                                            </svg>
                                                            Access Locked
                                                        </span>
                                                    @elseif($canCreate)
                                                        <form action="{{ route('campus-user.open-template') }}" method="POST" class="inline">
                                                            @csrf
                                                            <input type="hidden" name="template_id" value="{{ $template->id }}">
                                                            <input type="hidden" name="template_code" value="{{ $template->template_code }}">
                                                            <button type="submit" 
                                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700">
                                                                Open Template
                                                            </button>
                                                        </form>
                                                    @elseif($canEdit)
                                                        <a href="{{ route('campus-user.edit-submission', $submission) }}" 
                                                           class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-700">
                                                            Edit Submission
                                                        </a>
                                                    @elseif($canView)
                                                        <a href="{{ route('campus-user.show-submission', $submission) }}" 
                                                           class="inline-flex items-center px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-md hover:bg-gray-700">
                                                            {{ $isApproved ? 'View Approved' : 'View Submission' }}
                                                        </a>
                                                    @elseif($template->status !== 'Published')
                                                        <span class="text-xs text-gray-500">Template not published</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-16">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <h3 class="mt-4 text-lg font-semibold text-gray-900">No assigned templates</h3>
                                    <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">You don't have any assigned templates available.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Returned Templates Section -->
                    <div id="section-returned" class="template-section hidden">
                        <!-- Info Notice -->
                        <div class="mb-6 bg-orange-50 border border-orange-200 text-orange-800 px-4 py-3 rounded">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="font-medium">These templates have submissions that were returned by QA Coordinator. Please review the feedback and resubmit.</p>
                            </div>
                        </div>

                        <!-- Returned Templates Table -->
                        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                            @if(isset($returnedTemplates) && $returnedTemplates->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template Code</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Form / Source</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SG Code</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KRA Title</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KPI Title</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Returned Date</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($returnedTemplates as $template)
                                            @php
                                                $submission = $returnedSubmissionsByTemplate[$template->id] ?? null;
                                                $canEdit = $submission && $submission->status === 'Returned';
                                                $remarks = $submission && $submission->approval ? $submission->approval->remarks : null;
                                            @endphp
                                            <tr class="hover:bg-orange-50 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                        {{ $template->template_code }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                                                    <span title="{{ $template->form ? $template->form->form_title : 'Standalone template' }}">
                                                        {{ $template->form ? Str::limit($template->form->form_title, 35) : '—' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $template->campus_code ?? 'All Campuses' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $template->sg_code }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900">
                                                    {{ Str::limit($template->kra_title, 50) }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900">
                                                    {{ Str::limit($template->kpi_title, 50) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    @if($submission && $submission->updated_at)
                                                        {{ $submission->updated_at->format('M d, Y') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if($canEdit)
                                                        <div class="flex items-center gap-2">
                                                            <a href="{{ route('campus-user.edit-submission', $submission) }}" 
                                                               class="inline-flex items-center px-3 py-1.5 bg-orange-600 text-white text-xs font-medium rounded-md hover:bg-orange-700">
                                                                Edit & Resubmit
                                                            </a>
                                                            @if($remarks)
                                                            <button type="button" 
                                                                    onclick="showRemarks('{{ addslashes($remarks) }}')"
                                                                    class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200"
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
                                <div class="text-center py-16">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <h3 class="mt-4 text-lg font-semibold text-gray-900">No returned templates</h3>
                                    <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">You don't have any templates with returned submissions.</p>
                                </div>
                            @endif
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
                        function showSection(section) {
                            // Hide all sections
                            document.querySelectorAll('.template-section').forEach(el => el.classList.add('hidden'));
                            
                            // Remove active class from all tabs
                            document.querySelectorAll('.tab-button').forEach(btn => {
                                btn.classList.remove('active', 'border-indigo-500', 'text-indigo-600');
                                btn.classList.add('border-transparent', 'text-gray-500');
                            });
                            
                            // Show selected section
                            document.getElementById('section-' + section).classList.remove('hidden');
                            
                            // Activate selected tab
                            const activeTab = document.getElementById('tab-' + section);
                            activeTab.classList.add('active', 'border-indigo-500', 'text-indigo-600');
                            activeTab.classList.remove('border-transparent', 'text-gray-500');
                        }

                        // Handle hash navigation (e.g., #returned)
                        document.addEventListener('DOMContentLoaded', function() {
                            if (window.location.hash === '#returned') {
                                showSection('returned');
                            }
                        });

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
                @else
                    <!-- For non-Planning Coordinators, show original view -->
                    <!-- Info Notice -->
                    <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="font-medium">These are the templates assigned to you. Click "View Template" to see details.</p>
                        </div>
                    </div>

                    <!-- Templates Table -->
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        @if($templates->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template Code</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SG Code</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KRA Title</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KPI Title</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($templates as $template)
                                        @php
                                            $submission = $submissionsByTemplate[$template->template_code] ?? null;
                                            $canEdit = $submission && ($submission->status === 'Unpublished' || $submission->status === 'Returned');
                                            $canView = $submission && !$canEdit;
                                            $canCreate = !$submission && $template->status === 'Published';
                                        @endphp
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $template->template_code }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $template->campus_code ?? 'All Campuses' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $template->sg_code }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ Str::limit($template->kra_title, 50) }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                {{ Str::limit($template->kpi_title, 50) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($template->status === 'Published')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Published
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Draft
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if($canCreate)
                                                    <form action="{{ route('campus-user.open-template') }}" method="POST" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="template_code" value="{{ $template->template_code }}">
                                                        <button type="submit" 
                                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700">
                                                            Open Template
                                                        </button>
                                                    </form>
                                                @elseif($canEdit)
                                                    <a href="{{ route('campus-user.edit-submission', $submission) }}" 
                                                       class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-700">
                                                        Edit Submission
                                                    </a>
                                                @elseif($canView)
                                                    <a href="{{ route('campus-user.show-submission', $submission) }}" 
                                                       class="inline-flex items-center px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-md hover:bg-gray-700">
                                                        View Submission
                                                    </a>
                                                @elseif($template->status !== 'Published')
                                                    <span class="text-xs text-gray-500">Template not published</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-16">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="mt-4 text-lg font-semibold text-gray-900">No templates assigned</h3>
                                <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">You don't have any templates assigned to you yet. Please contact your administrator.</p>
                            </div>
                        @endif
                    </div>
                @endif
            @endauth
        </div>
    </div>
</x-app-layout>
