<x-app-layout>
    <x-slot name="header"><div></div></x-slot>

    @php
        $authUser = Auth::user();
        $isDeveloperSupportMode = true;
    @endphp

    <style>
        body.messaging-page {
            overflow: hidden;
        }
        body.messaging-page.developer-support-fixed {
            overflow: hidden;
        }
        body.messaging-page.developer-support-fixed #messaging-shell {
            height: calc(100vh - 3rem) !important;
            overflow: hidden;
        }
    </style>

    <div id="messaging-shell" class="min-h-0 flex flex-col overflow-hidden" style="height: calc(100vh - 3rem);">
        <div class="flex shrink-0 flex-wrap items-start justify-between mb-4 px-1 gap-3">
            <div>
                @if($authUser->isDeveloper())
                    <h1 class="text-xl font-bold text-gray-800">{{ __('Reports & Concerns') }}</h1>
                    <p class="text-sm text-gray-500">{{ __('All repair tickets submitted by campus and division staff. Open a row to review, assign, and update status.') }}</p>
                @else
                    <h1 class="text-xl font-bold text-gray-800">{{ __('Developers') }}</h1>
                    <p class="text-sm text-gray-500">{{ __('Your repair tickets from Developer Support.') }}</p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                @if($authUser->isDeveloper())
                    <a href="{{ route('messaging.developer-notifications.index', ['audience' => 'developers']) }}"
                       class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        {{ __('Ticket alerts') }}
                    </a>
                @else
                    <a href="{{ route('messaging.index', ['audience' => 'developers']) }}"
                       class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        {{ __('Report Form') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain p-5 bg-gray-50 rounded-2xl border border-gray-200">
            <div class="max-w-4xl mx-auto space-y-4 pb-8">
                <div class="flex items-baseline justify-between gap-3 border-b border-gray-200 pb-2">
                    <h2 class="text-base font-semibold text-gray-900">{{ $authUser->isDeveloper() ? __('Repair tickets') : __('Tickets') }}</h2>
                    @if($authUser->isDeveloper())
                        <span class="text-xs text-gray-500 hidden sm:inline">{{ __('Submitted by Planning, QA, CEDs, Division View, and other staff') }}</span>
                    @else
                        <span class="text-xs text-gray-500 hidden sm:inline">{{ __('Your submitted tickets') }}</span>
                    @endif
                </div>

                @if($authUser->isDeveloper())
                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50 px-5 py-3">
                        <p class="text-sm text-indigo-800">Open a ticket to update status, internal notes, and assignment.</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
                        @forelse($supportReportsInbox as $report)
                            @php
                                $reporter = $report->user;
                                $ticket = $report->repairTicket;
                            @endphp
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">{{ $report->title }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ $reporter->name ?? 'Unknown User' }}
                                            @if(!empty($reporter->campus)) · {{ $reporter->campus }} @endif
                                            · {{ $report->created_at?->format('M j, Y g:i A') ?? 'No date' }}
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-indigo-100 text-indigo-700 px-2.5 py-1 text-xs font-medium">{{ $report->report_type }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-2">{{ Str::limit($report->description, 170) }}</p>
                                @if($ticket)
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-700 px-2 py-0.5 text-xs font-medium capitalize">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                        <span class="inline-flex items-center rounded-full bg-amber-50 text-amber-800 px-2 py-0.5 text-xs font-medium capitalize">{{ $ticket->priority }} priority</span>
                                        <a href="{{ route('messaging.repair-tickets.show', ['repairTicket' => $ticket, 'audience' => 'developers']) }}" class="inline-flex items-center rounded-lg border border-indigo-300 bg-white px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
                                            View Ticket
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="p-10 text-center">
                                <p class="text-sm text-gray-500">No user reports or concerns yet.</p>
                            </div>
                        @endforelse
                    </div>
                @else
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
                        @forelse($supportReportsMine as $report)
                            @php $ticket = $report->repairTicket; @endphp
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">{{ $report->title }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $report->created_at?->format('M j, Y g:i A') ?? '' }}</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-indigo-100 text-indigo-700 px-2.5 py-1 text-xs font-medium">{{ $report->report_type }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-2">{{ Str::limit($report->description, 120) }}</p>
                                @if($ticket)
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-700 px-2 py-0.5 text-xs font-medium capitalize">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                        <a href="{{ route('messaging.repair-tickets.show', ['repairTicket' => $ticket, 'audience' => 'developers']) }}" class="inline-flex items-center rounded-lg border border-indigo-300 bg-white px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
                                            View Ticket
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="p-10 text-center space-y-3">
                                <p class="text-sm text-gray-500">You have not submitted any tickets yet.</p>
                                <a href="{{ route('messaging.index', ['audience' => 'developers']) }}" class="inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-800">Go to Report Form</a>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
    (function () {
        document.body.classList.add('messaging-page', 'developer-support-fixed');
        window.addEventListener('beforeunload', () => document.body.classList.remove('messaging-page', 'developer-support-fixed'));
    })();
    </script>
</x-app-layout>
