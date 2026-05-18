<x-app-layout>
    <x-slot name="header"><div></div></x-slot>

    @php
        $authUser = Auth::user();
        $report = $repairTicket->report;
        $canEditReport = $authUser->id === $report->user_id
            && ! $authUser->isDeveloper()
            && in_array($repairTicket->status, [\App\Models\RepairTicket::STATUS_OPEN, \App\Models\RepairTicket::STATUS_IN_PROGRESS], true);
        $submitter = $report->user;
        $attachments = is_array($report->attachments) ? $report->attachments : [];
        $statusLabels = [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
        $priorityLabels = [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
        ];
    @endphp

    <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
        <div>
            <a href="{{ route('messaging.developer-tickets.index', ['audience' => 'developers']) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                @if($authUser->isDeveloper())
                    ← {{ __('Back to Reports & Concerns') }}
                @else
                    ← {{ __('Back to Tickets') }}
                @endif
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 px-5 py-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-lg font-semibold text-indigo-900">Repair Ticket #{{ $repairTicket->id }}</h1>
                    <p class="mt-1 text-sm text-indigo-800">{{ $report->title }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-indigo-900 ring-1 ring-indigo-200">
                        {{ $statusLabels[$repairTicket->status] ?? $repairTicket->status }}
                    </span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Report</h2>
                @if($canEditReport)
                    <a href="{{ route('messaging.support-reports.edit', ['supportReport' => $report, 'audience' => 'developers']) }}"
                       class="inline-flex shrink-0 items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50">
                        {{ __('Edit') }}
                    </a>
                @endif
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-gray-500">Report type</dt>
                    <dd class="font-medium text-gray-900">{{ $report->report_type }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Priority</dt>
                    <dd class="font-medium text-gray-900">{{ $priorityLabels[$repairTicket->priority] ?? ucfirst($repairTicket->priority) }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500">Submitted</dt>
                    <dd class="font-medium text-gray-900">{{ $report->created_at?->format('M j, Y g:i A') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500">Submitted by</dt>
                    <dd class="font-medium text-gray-900">
                        {{ $submitter->name ?? 'Unknown' }}
                        @if(!empty($submitter->email)) · {{ $submitter->email }} @endif
                        @if(!empty($submitter->campus)) · {{ $submitter->campus }} @endif
                    </dd>
                </div>
            </dl>
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-1">Description</h3>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-3 text-sm text-gray-800 whitespace-pre-wrap">{{ $report->description }}</div>
            </div>
            @if(count($attachments) > 0)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Attachments</h3>
                    <ul class="space-y-2">
                        @foreach($attachments as $idx => $att)
                            @php
                                $name = is_array($att) ? ($att['name'] ?? 'File') : 'File';
                                $size = is_array($att) ? (int) ($att['size'] ?? 0) : 0;
                                $sizeLabel = $size > 0
                                    ? ($size < 1048576 ? number_format($size / 1024, 1).' KB' : number_format($size / 1048576, 2).' MB')
                                    : '';
                            @endphp
                            <li class="flex items-center justify-between gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                <span class="truncate text-gray-800">{{ $name }} @if($sizeLabel)<span class="text-gray-400">({{ $sizeLabel }})</span>@endif</span>
                                <a href="{{ route('messaging.support-reports.attachments.show', ['supportReport' => $report->id, 'index' => $idx, 'audience' => 'developers']) }}"
                                   target="_blank" rel="noopener"
                                   class="shrink-0 rounded-lg bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700">
                                    Open
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @if($authUser->isDeveloper())
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Ticket (developers)</h2>

                <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
                    @if($repairTicket->assignedTo)
                        <p class="font-medium">{{ __('This ticket is under repair by :name.', ['name' => $repairTicket->assignedTo->name]) }}</p>
                    @else
                        <p class="font-medium">{{ __('Not claimed yet — saving updates will record you as the developer handling this ticket.') }}</p>
                    @endif
                </div>

                <form method="post" action="{{ route('messaging.repair-tickets.update', ['repairTicket' => $repairTicket, 'audience' => 'developers']) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            @foreach(\App\Models\RepairTicket::STATUSES as $st)
                                <option value="{{ $st }}" @selected(old('status', $repairTicket->status) === $st)>{{ $statusLabels[$st] ?? $st }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="internal_notes" class="block text-sm font-medium text-gray-700 mb-1">Internal notes</label>
                        <textarea name="internal_notes" id="internal_notes" rows="4" maxlength="10000"
                                  placeholder="Notes visible to developers only."
                                  class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none">{{ old('internal_notes', $repairTicket->internal_notes) }}</textarea>
                    </div>

                    @if ($errors->any())
                        <div class="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-600">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Save changes
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 text-sm text-gray-600 space-y-2">
                <p class="font-medium text-gray-800">{{ __('Ticket status') }}</p>
                @if($repairTicket->assignedTo)
                    <p class="text-gray-700">{{ __('Under repair by :name.', ['name' => $repairTicket->assignedTo->name]) }}</p>
                @else
                    <p class="text-gray-500">{{ __('A developer has not claimed this ticket yet.') }}</p>
                @endif
                <p class="mt-1">{{ __('Developers update this ticket. To add more information, submit a new report from Developer Support.') }}</p>
            </div>
        @endif
    </div>
</x-app-layout>
