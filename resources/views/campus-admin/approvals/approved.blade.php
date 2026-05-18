<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Section card: title + back action (separate from table card below) --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-md px-6 py-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-start items-start">
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-semibold text-gray-900">Approved submissions</h2>
                        <p class="text-sm text-gray-600 mt-1">Your campus · view details, ratings, and update scores if needed</p>
                    </div>
                    <a href="{{ route('campus-admin.approvals.index') }}"
                       class="inline-flex items-center gap-2 self-start shrink-0 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 bg-gray-50/80 hover:bg-gray-100 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                        <span aria-hidden="true">←</span>
                        Back to pending
                    </a>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100">
                @if($submissions->count() > 0)
                    <div class="overflow-x-auto">
                        {{-- No Submission ID column: form, KPI, and actions are enough for QA workflow --}}
                        <table class="min-w-[720px] w-full table-fixed divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="w-[14%] px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Form title
                                    </th>
                                    <th scope="col" class="w-[32%] px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        KPI title
                                    </th>
                                    <th scope="col" class="w-[12%] px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Submitted by
                                    </th>
                                    <th scope="col" class="w-[8%] px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Quarter
                                    </th>
                                    <th scope="col" class="w-[12%] px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Performance
                                    </th>
                                    <th scope="col" class="w-[10%] px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Submitted
                                    </th>
                                    <th scope="col" class="w-[12%] px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($submissions as $submission)
                                    <tr class="hover:bg-slate-50/80 transition-colors duration-150">
                                        <td class="px-4 py-4 align-top">
                                            <div class="text-sm text-gray-900 font-medium leading-snug break-words">
                                                {{ $submission->form_title ?? ($submission->template ? $submission->template->sg_code . ' - ' . $submission->template_code : 'N/A') }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 align-top min-w-0">
                                            @php
                                                $kpiTitle = $submission->kpi_title ?? ($submission->template ? $submission->template->kpi_title : 'N/A');
                                            @endphp
                                            <p class="text-sm text-gray-900 leading-snug break-words line-clamp-3" title="{{ Str::limit($kpiTitle, 500) }}">{{ $kpiTitle }}</p>
                                        </td>
                                        <td class="px-4 py-4 align-top text-sm text-gray-900 break-words">
                                            {{ $submission->submitter->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-4 align-top">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-800 border border-indigo-100">
                                                {{ $submission->quarter ?: '—' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 align-top text-sm text-gray-900">
                                            @if($submission->approval)
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-xs font-medium text-gray-700">{{ number_format($submission->approval->rate, 1) }}%</span>
                                                    <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-xs font-medium {{ $submission->approval->rating_badge_class }}">
                                                        {{ $submission->approval->rating }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">Not rated</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 align-top text-sm text-gray-500 whitespace-nowrap">
                                            {{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y') : $submission->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-4 align-top text-sm font-medium">
                                            <div class="flex flex-col gap-2">
                                                <a href="{{ route('campus-admin.approvals.show', $submission) }}"
                                                   class="inline-flex items-center justify-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors text-xs font-medium border border-blue-100">
                                                    View
                                                </a>
                                                @if($submission->approval)
                                                    <a href="{{ route('campus-admin.approvals.edit', $submission) }}"
                                                       class="inline-flex items-center justify-center px-3 py-1.5 bg-amber-50 text-amber-900 rounded-lg hover:bg-amber-100 transition-colors text-xs font-medium border border-amber-100">
                                                        Edit rating
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        {{ $submissions->links() }}
                    </div>
                @else
                    <div class="text-center py-16 px-4">
                        <div class="mx-auto w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">No approved submissions yet</h3>
                        <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">When you approve items from Pending, they will appear in this list.</p>
                        <div class="mt-6">
                            <a href="{{ route('campus-admin.approvals.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium text-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm">
                                Go to pending submissions
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
