            <!-- Header Section with actions on the right -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            @if($readOnly)
                                Template Details (view only)
                            @else
                                Template Details (Super Admin)
                            @endif
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            @if($readOnly)
                                Read-only: coordinator submission data and field structure.
                            @else
                                View template information and field structure
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 flex-shrink-0 flex-wrap">
                        @if(!$readOnly)
                        {{-- Notify button --}}
                        <a href="{{ route('super-admin.templates.notify-form', $template) }}"
                           class="inline-flex items-center justify-center px-4 py-2 bg-teal-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            Notify Users
                        </a>

                        <a href="{{ route('super-admin.templates.edit', $template) }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Edit Template
                        </a>
                        @php
                            $copyTemplateUrl = route('super-admin.templates.create', array_filter([
                                'copy_from' => $template->id,
                                'form_id'   => $template->form_id,
                            ]));
                        @endphp
                        <a href="{{ $copyTemplateUrl }}"
                           class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700"
                           title="Copy this template — all fields, columns, formulas, coordinators and campus targets are pre-filled automatically">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Copy Template
                        </a>
                        <button type="button" id="audit-trail-show-btn" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Audit Trailing
                        </button>
                        @endif
                        @php
                            $backHref = $readOnly
                                ? ($viewOnlyBackUrl ?? ($template->form ? route('forms.show', $template->form->id) : route('view-only.templates.index')))
                                : ($template->form ? route('forms.show', $template->form->id) : route('super-admin.templates.index') . '?tab=forms');
                        @endphp
                        <a href="{{ $backHref }}" id="back-to-form-link" data-href="{{ $backHref }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            @if($readOnly)
                                Back
                            @else
                                {{ $template->form ? 'Back to Form' : 'Back to Templates' }}
                            @endif
                        </a>
                    </div>
                </div>
            </div>
