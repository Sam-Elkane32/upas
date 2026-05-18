<x-app-layout>
    <x-slot name="header"><div></div></x-slot>

    @php
        $attachments = is_array($supportReport->attachments) ? $supportReport->attachments : [];
    @endphp

    <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('messaging.repair-tickets.show', ['repairTicket' => $repairTicket, 'audience' => 'developers']) }}"
               class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                ← {{ __('Back to ticket') }}
            </a>
        </div>

        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ __('Edit report') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('Ticket #:id — you can update this while it is open or in progress.', ['id' => $repairTicket->id]) }}</p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('messaging.support-reports.update', $supportReport) }}?audience=developers" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:items-start">
                <div>
                    <label for="edit-report-type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Report type') }}</label>
                    <select id="edit-report-type" name="report_type" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        @foreach(\App\Models\SupportReport::REPORT_TYPES as $type)
                            <option value="{{ $type }}" @selected(old('report_type', $supportReport->report_type) === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="edit-priority" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Priority') }}</label>
                    <select id="edit-priority" name="priority" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        @foreach(\App\Models\RepairTicket::PRIORITIES as $pr)
                            <option value="{{ $pr }}" @selected(old('priority', $repairTicket->priority) === $pr)>{{ ucfirst($pr) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="edit-title" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Subject') }}</label>
                <input id="edit-title" name="title" type="text" maxlength="150" value="{{ old('title', $supportReport->title) }}"
                       class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300" />
            </div>

            <div>
                <label for="edit-description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Details') }}</label>
                <textarea id="edit-description" name="description" rows="5" maxlength="5000"
                          class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none">{{ old('description', $supportReport->description) }}</textarea>
            </div>

            @if(count($attachments) > 0)
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">{{ __('Current files') }} ({{ count($attachments) }}/5)</p>
                    <ul class="text-sm text-gray-600 space-y-1 list-disc list-inside">
                        @foreach($attachments as $att)
                            <li>{{ is_array($att) ? ($att['name'] ?? 'File') : 'File' }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label for="edit-files" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Add attachments') }}</label>
                <input id="edit-files" name="attachments[]" type="file" multiple
                       class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:file:bg-indigo-700"
                       accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,image/*">
                <p class="mt-1 text-xs text-gray-500">{{ __('Optional. Max 5 files total including existing.') }}</p>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('messaging.repair-tickets.show', ['repairTicket' => $repairTicket, 'audience' => 'developers']) }}"
                   class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    {{ __('Save changes') }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
