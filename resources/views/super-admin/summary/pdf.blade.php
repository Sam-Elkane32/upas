<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary of Accomplishments - PSU</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            margin: 15px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }
        .header h1 {
            font-size: 20px;
            font-weight: bold;
            margin: 3px 0;
        }
        .header h2 {
            font-size: 14px;
            margin: 2px 0;
        }
        .header p {
            font-size: 9px;
            color: #666;
            margin: 2px 0;
        }
        .stats-section {
            margin-bottom: 12px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .stats-row {
            display: table-row;
        }
        .stats-cell {
            display: table-cell;
            padding: 6px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #ddd;
        }
        .stats-value {
            font-size: 16px;
            font-weight: bold;
        }
        .stats-label {
            font-size: 8px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 8px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: center;
        }
        .bg-blue-50 { background-color: #eff6ff; }
        .bg-red-50 { background-color: #fef2f2; }
        .bg-yellow-50 { background-color: #fefce8; }
        .bg-green-50 { background-color: #f0fdf4; }
        .bg-emerald-50 { background-color: #ecfdf5; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-semibold { font-weight: 600; }
        .sg-header {
            background-color: #4f46e5;
            color: white;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 15px;
        }
        .kra-header {
            background-color: #dbeafe;
            padding: 5px 8px;
            font-size: 10px;
            font-weight: bold;
            border: 1px solid #ddd;
            border-bottom: none;
        }
        .kpi-table {
            font-size: 7px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>SUMMARY OF ACCOMPLISHMENTS</h1>
        <h2>Pangasinan State University</h2>
        <p>Generated: {{ $university_stats['date_generated'] }}</p>
    </div>

    <!-- University Statistics -->
    <div class="stats-section">
        <table>
            <tr>
                <th colspan="5" style="text-align: left; padding: 6px;">University-Wide Statistics</th>
            </tr>
            <tr>
                <td class="text-center stats-cell">
                    <div class="stats-value" style="color: #2563eb;">{{ $university_stats['total_campuses'] }}</div>
                    <div class="stats-label">Campuses</div>
                </td>
                <td class="text-center stats-cell">
                    <div class="stats-value" style="color: #16a34a;">{{ $university_stats['total_approved_submissions'] }}</div>
                    <div class="stats-label">Reports (submissions + VPASS)</div>
                </td>
                <td class="text-center stats-cell">
                    <div class="stats-value" style="color: #9333ea;">{{ $university_stats['total_sgs'] }}</div>
                    <div class="stats-label">Strategic Goals</div>
                </td>
                <td class="text-center stats-cell">
                    <div class="stats-value" style="color: #4f46e5;">{{ $university_stats['total_kras'] }}</div>
                    <div class="stats-label">Key Result Areas</div>
                </td>
                <td class="text-center stats-cell">
                    <div class="stats-value" style="color: #ea580c;">{{ $university_stats['total_kpis'] }}</div>
                    <div class="stats-label">Key Performance Indicators</div>
                </td>
            </tr>
        </table>
        
        <table style="margin-top: 8px;">
            <tr>
                <th style="text-align: left; padding: 6px;">KPI & Template Totals</th>
            </tr>
            <tr>
                <td style="padding: 6px;">
                    <strong>Total KPIs:</strong> {{ $overall_breakdown['total_kpis'] }}<br>
                    <strong>Total Accomplishment Templates:</strong> {{ $university_stats['total_approved_submissions'] }}
                </td>
            </tr>
        </table>
    </div>

    <!-- KPI Status Breakdown -->
    <table>
        <thead>
            <tr>
                <th colspan="3" style="text-align:left;padding:6px;">KPI status (university-wide)</th>
            </tr>
            <tr>
                <th>Category</th>
                <th class="text-center">Count</th>
                <th class="text-center">Percentage</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalKpis = $overall_breakdown['total_kpis'];
                $calculatePercentage = function($count, $total) {
                    if ($total == 0) return '0.00';
                    return number_format(($count / $total) * 100, 2);
                };
            @endphp
            <tr class="bg-blue-50">
                <td><strong>KPIs with No Target</strong></td>
                <td class="text-center">{{ $overall_breakdown['no_target'] }}</td>
                <td class="text-center font-semibold">{{ $calculatePercentage($overall_breakdown['no_target'], $totalKpis) }}%</td>
            </tr>
            <tr class="bg-red-50">
                <td><strong>KPIs with No Accomplishment</strong></td>
                <td class="text-center">{{ $overall_breakdown['no_accomplishment'] }}</td>
                <td class="text-center font-semibold">{{ $calculatePercentage($overall_breakdown['no_accomplishment'], $totalKpis) }}%</td>
            </tr>
            <tr class="bg-yellow-50">
                <td><strong>KPIs Below Target</strong></td>
                <td class="text-center">{{ $overall_breakdown['below_target'] }}</td>
                <td class="text-center font-semibold">{{ $calculatePercentage($overall_breakdown['below_target'], $totalKpis) }}%</td>
            </tr>
            <tr class="bg-green-50">
                <td><strong>KPIs Met Target</strong></td>
                <td class="text-center">{{ $overall_breakdown['met_target'] }}</td>
                <td class="text-center font-semibold">{{ $calculatePercentage($overall_breakdown['met_target'], $totalKpis) }}%</td>
            </tr>
            <tr class="bg-emerald-50">
                <td><strong>KPIs Above Target</strong></td>
                <td class="text-center">{{ $overall_breakdown['above_target'] }}</td>
                <td class="text-center font-semibold">{{ $calculatePercentage($overall_breakdown['above_target'], $totalKpis) }}%</td>
            </tr>
        </tbody>
    </table>

    <!-- Summary by KRA -->
    <table>
        <thead>
            <tr>
                <th colspan="13" style="text-align:left;padding:6px;">By KRA</th>
            </tr>
            <tr>
                <th>SG Code</th>
                <th>KRA Title</th>
                <th class="text-center">Total KPIs</th>
                <th class="text-center bg-blue-50">No Target</th>
                <th class="text-center bg-blue-50">%</th>
                <th class="text-center bg-red-50">No Accomplishment</th>
                <th class="text-center bg-red-50">%</th>
                <th class="text-center bg-yellow-50">Below Target</th>
                <th class="text-center bg-yellow-50">%</th>
                <th class="text-center bg-green-50">Met Target</th>
                <th class="text-center bg-green-50">%</th>
                <th class="text-center bg-emerald-50">Above Target</th>
                <th class="text-center bg-emerald-50">%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($kra_summary as $kraRow)
                @php
                    $kraTotal = $kraRow['total_kpis'];
                    $kraPercentage = function($count, $total) {
                        if ($total == 0) return '0.00';
                        return number_format(($count / $total) * 100, 2);
                    };
                @endphp
                <tr>
                    <td>{{ $kraRow['sg_code'] }}</td>
                    <td>{{ $kraRow['kra_title'] }}</td>
                    <td class="text-center font-semibold">{{ $kraRow['total_kpis'] }}</td>
                    <td class="text-center bg-blue-50">{{ $kraRow['no_target'] }}</td>
                    <td class="text-center bg-blue-50 font-semibold">{{ $kraPercentage($kraRow['no_target'], $kraTotal) }}%</td>
                    <td class="text-center bg-red-50">{{ $kraRow['no_accomplishment'] }}</td>
                    <td class="text-center bg-red-50 font-semibold">{{ $kraPercentage($kraRow['no_accomplishment'], $kraTotal) }}%</td>
                    <td class="text-center bg-yellow-50">{{ $kraRow['below_target'] }}</td>
                    <td class="text-center bg-yellow-50 font-semibold">{{ $kraPercentage($kraRow['below_target'], $kraTotal) }}%</td>
                    <td class="text-center bg-green-50 font-semibold">{{ $kraRow['met_target'] }}</td>
                    <td class="text-center bg-green-50 font-semibold">{{ $kraPercentage($kraRow['met_target'], $kraTotal) }}%</td>
                    <td class="text-center bg-emerald-50">{{ $kraRow['above_target'] }}</td>
                    <td class="text-center bg-emerald-50 font-semibold">{{ $kraPercentage($kraRow['above_target'], $kraTotal) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary by Responsible Work Unit -->
    <table>
        <thead>
            <tr>
                <th colspan="13" style="text-align:left;padding:6px;">By responsible unit (as stored on forms)</th>
            </tr>
            <tr>
                <th>Responsible unit (raw)</th>
                <th class="text-center">Total KPIs</th>
                <th class="text-center bg-blue-50">No Target</th>
                <th class="text-center bg-blue-50">%</th>
                <th class="text-center bg-red-50">No Accomplishment</th>
                <th class="text-center bg-red-50">%</th>
                <th class="text-center bg-yellow-50">Below Target</th>
                <th class="text-center bg-yellow-50">%</th>
                <th class="text-center bg-green-50">Met Target</th>
                <th class="text-center bg-green-50">%</th>
                <th class="text-center bg-emerald-50">Above Target</th>
                <th class="text-center bg-emerald-50">%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($work_unit_summary as $unitRow)
                @php
                    $unitTotal = $unitRow['total_kpis'];
                    $unitPercentage = function($count, $total) {
                        if ($total == 0) return '0.00';
                        return number_format(($count / $total) * 100, 2);
                    };
                @endphp
                <tr>
                    <td><strong>{{ $unitRow['work_unit'] }}</strong></td>
                    <td class="text-center font-semibold">{{ $unitRow['total_kpis'] }}</td>
                    <td class="text-center bg-blue-50">{{ $unitRow['no_target'] }}</td>
                    <td class="text-center bg-blue-50 font-semibold">{{ $unitPercentage($unitRow['no_target'], $unitTotal) }}%</td>
                    <td class="text-center bg-red-50">{{ $unitRow['no_accomplishment'] }}</td>
                    <td class="text-center bg-red-50 font-semibold">{{ $unitPercentage($unitRow['no_accomplishment'], $unitTotal) }}%</td>
                    <td class="text-center bg-yellow-50">{{ $unitRow['below_target'] }}</td>
                    <td class="text-center bg-yellow-50 font-semibold">{{ $unitPercentage($unitRow['below_target'], $unitTotal) }}%</td>
                    <td class="text-center bg-green-50 font-semibold">{{ $unitRow['met_target'] }}</td>
                    <td class="text-center bg-green-50 font-semibold">{{ $unitPercentage($unitRow['met_target'], $unitTotal) }}%</td>
                    <td class="text-center bg-emerald-50">{{ $unitRow['above_target'] }}</td>
                    <td class="text-center bg-emerald-50 font-semibold">{{ $unitPercentage($unitRow['above_target'], $unitTotal) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @include('super-admin.summary.partials.extended-scorecard-pdf', [
        'extended_overall' => $extended_overall ?? [],
        'office_summary_by_sg' => $office_summary_by_sg ?? [],
        'scorecard_performance_matrix' => $scorecard_performance_matrix ?? [],
        'contributing_form_titles' => $contributing_form_titles ?? [],
    ])

    <!-- Campus-Side Distribution -->
    @if(count($campus_stats) > 0)
        <table style="margin-top: 15px;">
            <thead>
                <tr>
                    <th>Campus</th>
                    <th class="text-center">Total Approved Templates</th>
                    <th class="text-center">Unique KPIs</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campus_stats as $campus)
                    <tr>
                        <td><strong>{{ $campus['campus_name'] }}</strong></td>
                        <td class="text-center">{{ $campus['total_submissions'] }}</td>
                        <td class="text-center">{{ $campus['unique_kpis'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Footer -->
    <div style="margin-top: 20px; text-align: center; font-size: 7px; color: #666; border-top: 1px solid #ddd; padding-top: 8px;">
        <p>This is an official document from Pangasinan State University - University Accomplishment Planning System (UPAS)</p>
        <p>Generated by: {{ $user->name }} ({{ $user->email }}) on {{ $university_stats['date_generated'] }}</p>
    </div>
</body>
</html>
