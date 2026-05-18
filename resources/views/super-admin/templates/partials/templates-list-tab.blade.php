<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Templates</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $templateStats['total'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Published</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $templateStats['published'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Unpublished</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $templateStats['draft'] ?? 0 }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white shadow-sm rounded-lg mb-6 p-4">
    <form method="GET" action="{{ route('super-admin.templates.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="hidden" name="tab" value="templates">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
            <select name="campus_code" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Campuses</option>
                <option value="ALL" {{ request('campus_code') == 'ALL' ? 'selected' : '' }}>All Campuses (University-wide)</option>
                @foreach($campuses as $campus)
                    <option value="{{ $campus->code }}" {{ request('campus_code') == $campus->code ? 'selected' : '' }}>
                        {{ $campus->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                        {{ $status }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SG Code</label>
            <select name="sg_code" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All SG Codes</option>
                @foreach($sgCodes as $sg)
                    <option value="{{ $sg }}" {{ request('sg_code') == $sg ? 'selected' : '' }}>
                        {{ $sg }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" 
                    class="w-full px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-indigo-700">
                Filter
            </button>
        </div>
    </form>
</div>

<!-- Create Template Button -->
<div class="mb-6 flex justify-end">
    <a href="{{ route('super-admin.templates.create') }}" 
       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Create New Template
    </a>
</div>

<!-- Templates Table -->
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    @if($templates->count() > 0)
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($templates as $template)
                        <tr id="template-row-{{ $template->id }}" 
                            class="@if((int) request('highlight') === (int) $template->id) bg-blue-50 border-l-4 border-blue-500 @endif">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $template->template_code }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 max-w-xs" title="{{ $template->form ? $template->form->form_title : 'Standalone template' }}">
                                {{ $template->form ? \Str::limit($template->form->form_title, 35) : '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $template->campus_code ?? 'All Campuses' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $template->sg_code }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $template->kra_title }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 max-w-xs whitespace-pre-wrap break-words" title="{{ $template->kpi_title }}">
                                {{ $template->kpi_title }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <form method="POST" action="{{ route('super-admin.templates.assign', $template) }}" class="inline-block" data-confirm="Are you sure you want to change the assignment?">
                                    @csrf
                                    <select name="assigned_user_ids[]"
                                            onchange="this.form.submit()" 
                                            class="text-xs border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">-- Unassigned --</option>
                                        @foreach($planningCoordinators ?? [] as $coordinator)
                                            <option value="{{ $coordinator->id }}" 
                                                    {{ ($template->assignedUsers->contains('id', $coordinator->id) || $template->assigned_user_id == $coordinator->id) ? 'selected' : '' }}>
                                                {{ $coordinator->name }} ({{ $coordinator->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                                @if($template->assignedUsers && $template->assignedUsers->isNotEmpty())
                                    <div class="mt-1 text-xs text-gray-500">
                                        {{ $template->assignedUsers->pluck('name')->join(', ') }}
                                    </div>
                                @elseif($template->assignedUser)
                                    <div class="mt-1 text-xs text-gray-500">
                                        {{ $template->assignedUser->name }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    @if($template->status === 'Published')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Published
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Unpublished
                                        </span>
                                    @endif
                                    @if($template->is_locked)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4"/>
                                            </svg>
                                            Locked
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex flex-col">
                                    <span>{{ $template->created_at->format('M d, Y') }}</span>
                                    <span class="text-xs text-gray-400">{{ $template->created_at->format('h:i A') }}</span>
                                    @if($template->created_at->isToday())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                            New
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex flex-wrap gap-x-2 gap-y-1">
                                    <a href="{{ route('super-admin.templates.show', $template) }}" 
                                       class="text-indigo-600 hover:text-indigo-900">View</a>
                                    <a href="{{ route('super-admin.templates.edit', $template) }}" 
                                       class="text-blue-600 hover:text-blue-900">Edit</a>
                                    @php
                                        $copyUrl = route('super-admin.templates.create', array_filter([
                                            'copy_from' => $template->id,
                                            'form_id'   => $template->form_id,
                                        ]));
                                    @endphp
                                    <a href="{{ $copyUrl }}"
                                       class="text-purple-600 hover:text-purple-900"
                                       title="Copy this template — fields, columns, coordinators, and targets are pre-filled automatically">
                                        Copy
                                    </a>
                                    <form method="POST" action="{{ route('super-admin.templates.toggle-status', $template) }}" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="text-yellow-600 hover:text-yellow-900">
                                            {{ $template->status === 'Published' ? 'Unpublish' : 'Publish' }}
                                        </button>
                                    </form>

                                    {{-- Lock / Unlock --}}
                                    @if($template->is_locked)
                                        <form method="POST" action="{{ route('super-admin.templates.unlock', $template) }}" class="inline"
                                              onsubmit="return confirm('Unlock template {{ $template->template_code }}?')">
                                            @csrf
                                            <button type="submit" class="text-amber-600 hover:text-amber-800 font-semibold">
                                                Unlock
                                            </button>
                                        </form>
                                    @else
                                        <button type="button"
                                                onclick="openLockModal({{ $template->id }}, '{{ $template->template_code }}')"
                                                class="text-red-600 hover:text-red-800">
                                            Lock
                                        </button>
                                    @endif

                                    {{-- Notify --}}
                                    <a href="{{ route('super-admin.templates.notify-form', $template) }}"
                                       class="text-teal-600 hover:text-teal-800">
                                        Notify
                                    </a>

                                    <button type="button" 
                                            onclick="deleteTemplate({{ $template->id }}, '{{ $template->template_code }}')"
                                            class="text-red-600 hover:text-red-900">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $templates->appends(['tab' => 'templates'])->links() }}
        </div>
    @else
        <div class="p-6 text-center">
            <p class="text-gray-500">No templates found.</p>
        </div>
    @endif
</div>

{{-- ── Lock Template Modal (shared across all rows in the list) ──────────── --}}
<div id="lock-modal-list" class="fixed inset-0 z-[9999] hidden flex items-center justify-center p-4" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-gray-800/60" onclick="closeLockModal()"></div>
    <div class="relative z-10 bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Lock Template</h3>
                <p id="lock-modal-subtitle" class="text-sm text-gray-500">This will block all planning coordinator access.</p>
            </div>
        </div>
        <form id="lock-modal-form" method="POST" action="">
            @csrf
            <div class="mb-4">
                <label for="lock_reason_list" class="block text-sm font-medium text-gray-700 mb-1">
                    Reason <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <textarea id="lock_reason_list" name="lock_reason" rows="3"
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-400 focus:ring-red-400 text-sm"
                          placeholder="e.g. Deadline has passed, no further submissions allowed."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeLockModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Confirm Lock
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openLockModal(templateId, templateCode) {
    const modal = document.getElementById('lock-modal-list');
    const form  = document.getElementById('lock-modal-form');
    const sub   = document.getElementById('lock-modal-subtitle');
    form.action = '/super-admin/templates/' + templateId + '/lock';
    sub.textContent = 'Template: ' + templateCode + ' — this will block all planning coordinator access.';
    document.getElementById('lock_reason_list').value = '';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeLockModal() {
    const modal = document.getElementById('lock-modal-list');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

