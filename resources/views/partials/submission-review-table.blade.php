@php
    $qaTableRows = \App\Support\SubmissionTableData::asArray($submission->table_data);
    $tableHeaders = \App\Support\SubmissionTableData::dataColumnKeys($qaTableRows);
    $evidenceColKey = null;
    foreach ($tableHeaders as $h) {
        $n = strtolower(str_replace(['-', ' '], '_', $h));
        if (str_contains($n, 'evidence') && str_contains($n, 'verified') && str_contains($n, 'qa')) {
            $evidenceColKey = $h;
            break;
        }
    }
    $headerColumnClass = function (string $header) use ($evidenceColKey): string {
        $n = strtolower(str_replace(['-', ' '], '_', $header));
        if ($evidenceColKey !== null && $header === $evidenceColKey) {
            return 'w-[14%] min-w-[10rem] max-w-[16rem]';
        }
        if (str_contains($n, 'link') || str_contains($n, 'drive') || str_contains($n, 'url')) {
            return 'w-[38%] min-w-[14rem]';
        }
        return 'min-w-[12rem]';
    };
@endphp

@if($submission->table_data && count($submission->table_data) > 0)
    <div class="submission-review-table-wrap w-full min-w-0 overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full min-w-full table-auto divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                <tr>
                    @foreach($tableHeaders as $header)
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide align-top whitespace-normal break-words {{ $headerColumnClass($header) }}">
                            {{ ucwords(str_replace(['_', '-'], ' ', $header)) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($submission->table_data as $index => $row)
                    @php
                        $isSummaryRow = ($row['_meta']['row_type'] ?? 'data') === 'summary';
                    @endphp
                    <tr class="transition-colors duration-150 {{ $isSummaryRow ? 'bg-blue-100 hover:bg-blue-100' : ($index % 2 === 0 ? 'bg-white hover:bg-blue-50' : 'bg-gray-50 hover:bg-blue-50') }}">
                        @foreach($tableHeaders as $header)
                            @php
                                $value = $row[$header] ?? '';
                                if (is_array($value) || is_object($value)) {
                                    $value = json_encode($value);
                                }
                                $value = (string) ($value ?? '');
                                $isEvidenceCol = ($evidenceColKey !== null && $header === $evidenceColKey);
                                $displayValue = (trim(strtolower($value)) === 'summary') ? '-' : ($value ?: '-');
                                $cellBlue = $isSummaryRow ? 'text-blue-700 font-medium' : 'text-gray-900';
                                $evidenceSelection = old('evidence_qa.'.$index, $value);
                            @endphp
                            <td class="px-4 py-4 text-sm align-top whitespace-normal break-words {{ $cellBlue }} {{ $headerColumnClass($header) }}">
                                @if($isEvidenceCol && !$isSummaryRow && ($evidenceEditable ?? true))
                                    <select name="evidence_qa[{{ $index }}]" class="block w-full min-w-[8rem] max-w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Select...</option>
                                        <option value="Yes" {{ $evidenceSelection === 'Yes' || $evidenceSelection === 'YES' ? 'selected' : '' }}>Yes</option>
                                        <option value="No" {{ $evidenceSelection === 'No' || $evidenceSelection === 'NO' ? 'selected' : '' }}>No</option>
                                    </select>
                                    @if($showEvidenceHint ?? true)
                                        <span class="text-xs text-gray-500 mt-0.5 block">QA sets Yes/No</span>
                                    @endif
                                @elseif($value !== '' && filter_var($value, FILTER_VALIDATE_URL))
                                    <a href="{{ $value }}" target="_blank" rel="noopener noreferrer" class="{{ $isSummaryRow ? 'text-blue-700' : 'text-indigo-600 hover:text-indigo-900' }} underline break-all">
                                        <svg class="w-4 h-4 inline mr-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        Open Link
                                    </a>
                                @else
                                    <span class="break-words">{{ $displayValue }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-gray-500 mt-3">
        Showing {{ count($submission->table_data) }} {{ count($submission->table_data) === 1 ? 'row' : 'rows' }}
    </p>
@else
    <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <p class="mt-2 text-sm font-medium text-gray-900">No data available</p>
        <p class="mt-1 text-xs text-gray-500">This submission does not contain any table data.</p>
    </div>
@endif
