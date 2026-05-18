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
    $bucketShort = [
        'above_target' => 'Abv',
        'met_target' => 'Met',
        'below_target' => 'Bel',
        'no_accomplishment_with_target' => 'NoA',
        'accomplishment_no_target' => 'Ac0',
        'no_target_q12' => 'NT1',
        'no_target_annual' => 'NTy',
    ];
    $officeRows = $office_summary_by_sg ?? [];
    $formTitles = $contributing_form_titles ?? [];
@endphp

@if(!empty($ext) && $extTotal > 0)
    <div class="stats-section">
        <table>
            @if(!empty($formTitles))
                <tr>
                    <td colspan="3" style="font-size:8px;padding:6px;line-height:1.35;font-weight:bold;">
                        Form title: {{ implode(' · ', $formTitles) }}
                    </td>
                </tr>
            @endif
            <tr>
                <th>Status</th>
                <th class="text-center">Count</th>
                <th class="text-center">%</th>
            </tr>
            @foreach($bucketKeys as $bk)
                <tr>
                    <td>{{ $bucketLabels[$bk] }}</td>
                    <td class="text-center font-semibold">{{ (int) ($ext[$bk] ?? 0) }}</td>
                    <td class="text-center">{{ $extPct((int) ($ext[$bk] ?? 0)) }}%</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" style="font-size:7px;color:#444;">With targets: {{ (int) ($ext['total_with_targets'] ?? 0) }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($officeRows))
        <table style="margin-top:10px;font-size:7px;">
            <tr>
                <th colspan="{{ 4 + count($bucketKeys) }}" style="text-align:left;padding:6px;font-size:8px;">By strategic goal and responsible work unit</th>
            </tr>
            <tr>
                <th>SG</th>
                <th>Resp. unit</th>
                <th class="text-center">Rows</th>
                <th class="text-center">W/tgt</th>
                @foreach($bucketKeys as $bk)
                    <th class="text-center" title="{{ $bucketLabels[$bk] }}">{{ $bucketShort[$bk] ?? $bk }}</th>
                @endforeach
            </tr>
            @php $prevSg = null; @endphp
            @foreach($officeRows as $row)
                @if($prevSg !== ($row['sg_code'] ?? ''))
                    @php $prevSg = $row['sg_code'] ?? ''; @endphp
                    <tr style="background:#e0e7ff;">
                        <td colspan="{{ 4 + count($bucketKeys) }}" style="font-weight:bold;padding:3px 4px;">{{ $row['sg_code'] ?? '' }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="color:#999;"> </td>
                    <td><strong>{{ $row['work_unit'] ?? '' }}</strong></td>
                    <td class="text-center">{{ (int) ($row['total_kpis'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($row['total_with_targets'] ?? 0) }}</td>
                    @foreach($bucketKeys as $bk)
                        <td class="text-center">{{ (int) ($row[$bk] ?? 0) }}</td>
                    @endforeach
                </tr>
            @endforeach
            <tr>
                <td colspan="{{ 4 + count($bucketKeys) }}" style="font-size:6px;color:#666;padding-top:4px;">
                    Abv/Met/Bel/NoA/Ac0/NT1/NTy — see web summary for full labels.
                </td>
            </tr>
        </table>
    @endif

    @if(!empty($scorecard_performance_matrix))
        <table style="margin-top:10px;font-size:7px;">
            <tr>
                <th colspan="6" style="text-align:left;padding:6px;font-size:8px;">Responsible work unit balance (on / off track)</th>
            </tr>
            <tr>
                <th>Resp. unit</th>
                <th class="text-center">Rows</th>
                <th class="text-center">On</th>
                <th class="text-center">On %</th>
                <th class="text-center">Off</th>
                <th class="text-center">Off %</th>
            </tr>
            @foreach($scorecard_performance_matrix as $m)
                <tr>
                    <td><strong>{{ $m['work_unit'] ?? '' }}</strong></td>
                    <td class="text-center">{{ (int) ($m['total_kpis'] ?? 0) }}</td>
                    <td class="text-center">{{ (int) ($m['positive_count'] ?? 0) }}</td>
                    <td class="text-center font-semibold">{{ $m['on_track_pct'] ?? 0 }}%</td>
                    <td class="text-center">{{ (int) ($m['negative_count'] ?? 0) }}</td>
                    <td class="text-center font-semibold">{{ $m['off_track_pct'] ?? 0 }}%</td>
                </tr>
            @endforeach
        </table>
    @endif
@endif
