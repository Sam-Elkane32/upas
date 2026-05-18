@php
    $ext = $extended_overall ?? [];
    $extTotal = (int) ($ext['total_kpis'] ?? 0);
    $extPct = function ($n) use ($extTotal) {
        if ($extTotal === 0) {
            return '0.00';
        }
        return number_format(((int) $n / $extTotal) * 100, 2);
    };
    $bucketLabels = [
        'above_target' => 'Above target',
        'met_target' => 'Met target',
        'below_target' => 'Below target',
        'no_accomplishment_with_target' => 'No accomplishment (had target)',
        'accomplishment_no_target' => 'Accomplishment (no annual target)',
        'no_target_q12' => 'No target H1 only',
        'no_target_annual' => 'No target / no plan',
    ];
    $bucketKeys = array_keys($bucketLabels);
@endphp

@if(!empty($ext) && $extTotal > 0)
    <div class="bg-white border border-gray-200 rounded-lg p-6 space-y-8">
        @if(!empty($contributing_form_titles))
            <div>
                <p class="text-lg text-gray-900 leading-snug">
                    <span class="font-semibold text-gray-800">Form title:</span>
                    <span class="font-semibold">{{ implode(' · ', $contributing_form_titles) }}</span>
                </p>
            </div>
        @endif

        <div>
            <h4 class="text-sm font-semibold text-gray-800 mb-3">Status distribution</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs md:text-sm border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700">
                            <th class="px-3 py-2 text-left border-b">Category</th>
                            <th class="px-3 py-2 text-center border-b w-24">Count</th>
                            <th class="px-3 py-2 text-center border-b w-24">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bucketKeys as $bk)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 border-b">{{ $bucketLabels[$bk] }}</td>
                                <td class="px-3 py-2 text-center border-b font-semibold">{{ (int) ($ext[$bk] ?? 0) }}</td>
                                <td class="px-3 py-2 text-center border-b">{{ $extPct((int) ($ext[$bk] ?? 0)) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if(!empty($office_summary_by_sg))
            <div>
                <h4 class="text-sm font-semibold text-gray-800 mb-3">By strategic goal and responsible work unit</h4>
                <div class="overflow-x-auto max-h-[26rem] overflow-y-auto">
                    <table class="min-w-full text-xs border border-gray-200">
                        <thead class="sticky top-0 bg-gray-100 z-10">
                            <tr class="text-gray-700">
                                <th class="px-2 py-2 text-left border-b">SG</th>
                                <th class="px-2 py-2 text-left border-b">Responsible work unit</th>
                                <th class="px-2 py-2 text-center border-b">Rows</th>
                                <th class="px-2 py-2 text-center border-b">W/ target</th>
                                @foreach($bucketKeys as $bk)
                                    <th class="px-2 py-2 text-center border-b whitespace-nowrap" title="{{ $bucketLabels[$bk] }}">{{ \Illuminate\Support\Str::limit($bucketLabels[$bk], 12) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $prevSg = null; @endphp
                            @foreach($office_summary_by_sg as $row)
                                @if($prevSg !== ($row['sg_code'] ?? ''))
                                    @php $prevSg = $row['sg_code'] ?? ''; @endphp
                                    <tr class="bg-indigo-50">
                                        <td colspan="{{ 4 + count($bucketKeys) }}" class="px-2 py-1 text-xs font-bold text-indigo-900 border-b">
                                            {{ $row['sg_code'] ?? '' }}
                                        </td>
                                    </tr>
                                @endif
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-1 border-b text-gray-400"> </td>
                                    <td class="px-2 py-1 border-b font-medium">{{ $row['work_unit'] ?? '' }}</td>
                                    <td class="px-2 py-1 border-b text-center">{{ (int) ($row['total_kpis'] ?? 0) }}</td>
                                    <td class="px-2 py-1 border-b text-center">{{ (int) ($row['total_with_targets'] ?? 0) }}</td>
                                    @foreach($bucketKeys as $bk)
                                        <td class="px-2 py-1 border-b text-center">{{ (int) ($row[$bk] ?? 0) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(!empty($scorecard_performance_matrix))
            <div>
                <h4 class="text-sm font-semibold text-gray-800 mb-3">Responsible work unit balance (on / off track)</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs md:text-sm border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700">
                                <th class="px-3 py-2 text-left border-b">Responsible work unit</th>
                                <th class="px-3 py-2 text-center border-b">Rows</th>
                                <th class="px-3 py-2 text-center border-b" title="Above target + met target + accomplishment (no annual target)">On</th>
                                <th class="px-3 py-2 text-center border-b" title="Share of rows counted as on-track">On %</th>
                                <th class="px-3 py-2 text-center border-b" title="Below target + no accomplishment (had target)">Off</th>
                                <th class="px-3 py-2 text-center border-b" title="Share of rows counted as off-track">Off %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scorecard_performance_matrix as $m)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 border-b font-medium">{{ $m['work_unit'] ?? '' }}</td>
                                    <td class="px-3 py-2 border-b text-center">{{ (int) ($m['total_kpis'] ?? 0) }}</td>
                                    <td class="px-3 py-2 border-b text-center">{{ (int) ($m['positive_count'] ?? 0) }}</td>
                                    <td class="px-3 py-2 border-b text-center font-semibold">{{ $m['on_track_pct'] ?? 0 }}%</td>
                                    <td class="px-3 py-2 border-b text-center">{{ (int) ($m['negative_count'] ?? 0) }}</td>
                                    <td class="px-3 py-2 border-b text-center font-semibold">{{ $m['off_track_pct'] ?? 0 }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endif
