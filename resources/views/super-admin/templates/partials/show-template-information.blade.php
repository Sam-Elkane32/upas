            <!-- Template Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 mb-6">
                <div class="p-6 md:p-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6 pb-3 border-b border-gray-200">Template Information</h3>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-10 gap-y-6">
                        <!-- Left Column -->
                        <div class="space-y-5">
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Template Code</dt>
                                <dd class="mt-0.5">
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-blue-100 text-blue-800">
                                        {{ $template->template_code }}
                                    </span>
                                </dd>
                            </div>
                            
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Strategic Goal</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $template->sg_code }}</dd>
                            </div>
                            
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">KPI Title</dt>
                                <dd class="mt-0.5 text-sm text-gray-900 leading-relaxed whitespace-pre-wrap break-words">{{ $template->kpi_title }}</dd>
                            </div>
                            
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Created By</dt>
                                <dd class="mt-0.5 text-sm text-gray-900">{{ $template->creator->name ?? 'N/A' }}</dd>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-5">
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">KRA Title</dt>
                                <dd class="mt-0.5 text-sm text-gray-900">{{ $template->kra_title }}</dd>
                            </div>
                            
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</dt>
                                <dd class="mt-0.5 flex flex-wrap gap-2 items-center">
                                    @if($template->status === 'Published')
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-green-100 text-green-800">
                                            Published
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-amber-100 text-amber-800">
                                            Unpublished
                                        </span>
                                    @endif
                                    @if($template->is_locked)
                                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-semibold bg-red-100 text-red-700 border border-red-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4"/>
                                            </svg>
                                            Locked
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            @if($template->is_locked)
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Lock Info</dt>
                                <dd class="mt-0.5 text-sm text-gray-700 space-y-1">
                                    <p>Locked by: <span class="font-medium">{{ $template->locker->name ?? 'Super Admin' }}</span></p>
                                    <p>Locked on: <span class="font-medium">{{ $template->locked_at ? $template->locked_at->format('M d, Y h:i A') : '—' }}</span></p>
                                    @if($template->lock_reason)
                                        <p>Reason: <span class="font-medium italic">{{ $template->lock_reason }}</span></p>
                                    @endif
                                </dd>
                            </div>
                            @endif
                            
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Assigned Planning Coordinator(s)</dt>
                                <dd class="mt-0.5">
                                    @if($template->assignedUsers && $template->assignedUsers->count() > 0)
                                        @php $coordinators = $template->assignedUsers; @endphp
                                        <div class="flex flex-col gap-2">
                                            <p class="text-xs text-gray-500">{{ $coordinators->count() }} coordinator(s) assigned</p>
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($coordinators as $c)
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200" title="{{ $c->name }}{{ $c->email ? ' — ' . $c->email : '' }}">
                                                        {{ ($c->campus_code ?? '') ?: $c->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif($template->assignedUser)
                                        <div class="flex flex-col gap-0.5 py-1.5 px-2 rounded-lg bg-gray-50 border border-gray-200">
                                            <span class="text-sm font-medium text-gray-900">{{ $template->assignedUser->name }}</span>
                                            @if($template->assignedUser->email)
                                                <span class="text-xs text-gray-500">{{ $template->assignedUser->email }}</span>
                                            @endif
                                            @if($template->assignedUser->campus_code)
                                                <span class="text-xs text-gray-400 uppercase">{{ $template->assignedUser->campus_code }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-500 italic">Not assigned</span>
                                    @endif
                                </dd>
                            </div>
                            
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Created At</dt>
                                <dd class="mt-0.5 text-sm text-gray-900">{{ $template->created_at->format('M d, Y') }}</dd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
