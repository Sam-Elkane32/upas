<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="w-full min-w-0 max-w-full">
            
            <!-- Floating Header Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('View Submission') }}
                </h2>
            </div>
            <!-- Submission Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ $submission->form->form_title ?? 'N/A' }}</h3>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $submission->status_badge_class }}">
                                {{ $submission->status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submission Details -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Form Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Form Information</h4>
                        <dl class="grid grid-cols-1 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Form Title</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->form->form_title ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Strategic Goal</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->form->sg_code ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">KRA Title</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->form->kra_title ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">KPI Title</dt>
                                <dd class="mt-1 text-sm text-gray-900 leading-relaxed whitespace-pre-wrap break-words">{{ $submission->template->kpi_title ?? $submission->kpi_title ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Responsible Unit</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->form->responsible_unit ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Template Code</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->template_code }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Submission Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Submission Information</h4>
                        <dl class="grid grid-cols-1 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Campus</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->campus }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Quarter</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->quarter }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $submission->status_badge_class }}">
                                        {{ $submission->status }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Submitted By</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->submitter->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Submitted At</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y H:i') : 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->last_updated ? $submission->last_updated->format('M d, Y H:i') : 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Submitted Data -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Submitted Data</h4>
                    @include('partials.submission-review-table', [
                        'submission' => $submission,
                        'evidenceEditable' => false,
                        'showEvidenceHint' => false,
                    ])
                </div>
            </div>

            <!-- Approval Information (if exists) -->
            @if($submission->approval)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Approval Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 mb-3">Performance Metrics</h5>
                                <dl class="grid grid-cols-1 gap-2">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Target Total:</dt>
                                        <dd class="text-sm text-gray-900">{{ number_format($submission->approval->target_total, 2) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Accomplishment Total:</dt>
                                        <dd class="text-sm text-gray-900">{{ number_format($submission->approval->accomp_total, 2) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Variance:</dt>
                                        <dd class="text-sm text-gray-900">{{ number_format($submission->approval->variance, 2) }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Rate of Accomplishment:</dt>
                                        <dd class="text-sm text-gray-900">{{ number_format($submission->approval->rate, 2) }}%</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Rating:</dt>
                                        <dd class="text-sm">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $submission->approval->rating_badge_class }}">
                                                {{ $submission->approval->rating }}
                                            </span>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 mb-3">Approval Details</h5>
                                <dl class="grid grid-cols-1 gap-2">
                                    <div>
                                        <dt class="text-sm text-gray-500">Accomplishment Term:</dt>
                                        <dd class="text-sm text-gray-900">{{ $submission->approval->accomp_term }}</dd>
                                    </div>
                                    @if($submission->approval->sdp_ref)
                                        <div>
                                            <dt class="text-sm text-gray-500">SDP Ref:</dt>
                                            <dd class="text-sm text-gray-900">{{ $submission->approval->sdp_ref }}</dd>
                                        </div>
                                    @endif
                                    <div>
                                        <dt class="text-sm text-gray-500">Validated By:</dt>
                                        <dd class="text-sm text-gray-900">{{ $submission->approval->validator->name ?? 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-gray-500">Validated At:</dt>
                                        <dd class="text-sm text-gray-900">{{ $submission->approval->validated_at ? $submission->approval->validated_at->format('M d, Y H:i') : 'N/A' }}</dd>
                                    </div>
                                    @if($submission->approval->remarks)
                                        <div>
                                            <dt class="text-sm text-gray-500">Remarks:</dt>
                                            <dd class="text-sm text-gray-900">{{ $submission->approval->remarks }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="flex justify-between">
                <a href="{{ route('campus-admin.approvals.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:bg-gray-50 active:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Approvals
                </a>
                
                @if($submission->status === 'Pending Review')
                    <a href="{{ route('campus-admin.approvals.review', $submission) }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Review & Approve
                    </a>
                @elseif($submission->approval)
                    <a href="{{ route('campus-admin.approvals.edit', $submission) }}" 
                       class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit Approval
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
