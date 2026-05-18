<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>UPAS Report Export</title>
    <style>
        /* Page Setup */
        @page {
            margin: 1cm;
            size: legal landscape;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
            color: #000;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .no-break {
            page-break-inside: avoid;
        }
        
        /* Title & Headers */
        .report-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 10px;
            color: #000;
        }
        
        .report-subtitle {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .campus-header {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            padding: 8px;
            background-color: #e5e7eb;
            border: 1px solid #000;
            text-align: center;
        }
        
        .section-header {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
            padding: 5px;
            background-color: #f3f4f6;
            border-left: 3px solid #000;
            padding-left: 10px;
        }
        
        /* Metadata */
        .metadata {
            font-size: 9pt;
            margin-bottom: 15px;
            padding: 5px;
            border-bottom: 1px solid #ccc;
        }
        
        .metadata-row {
            margin: 3px 0;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
            font-size: 9pt;
        }
        
        table thead {
            background-color: #374151;
            color: #fff;
        }
        
        table th {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
        }
        
        table td {
            border: 1px solid #000;
            padding: 5px 4px;
            text-align: left;
            font-size: 8pt;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        /* Submission Info Table */
        .submission-info {
            margin-bottom: 15px;
        }
        
        .submission-info th {
            background-color: #4b5563;
            width: 20%;
            text-align: left;
            padding-left: 8px;
        }
        
        .submission-info td {
            background-color: #fff;
        }
        
        /* Data Table */
        .data-table {
            margin-top: 10px;
        }
        
        .data-table th {
            background-color: #1f2937;
            font-size: 8pt;
        }
        
        .data-table td {
            font-size: 8pt;
            word-wrap: break-word;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #000;
            font-size: 9pt;
            page-break-inside: avoid;
        }
        
        .signature-block {
            margin-top: 30px;
            width: 100%;
            page-break-inside: avoid;
        }
        
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .signature-column {
            width: 50%;
            vertical-align: top;
            padding: 10px 15px;
        }
        
        .signature-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: left;
        }
        
        .signature-subtitle {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 10px;
            margin-top: 15px;
            text-align: left;
        }
        
        .signature-note {
            background-color: #ffff00;
            padding: 5px 8px;
            font-size: 9pt;
            font-style: italic;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .signature-entry {
            margin-bottom: 25px;
            font-size: 9pt;
        }
        
        .signature-line {
            font-family: 'Courier New', monospace;
            font-size: 10pt;
            letter-spacing: 2px;
            margin-bottom: 5px;
            padding-top: 3px;
            color: #000;
        }
        
        .signature-name {
            font-size: 9pt;
            text-align: left;
        }
        
        /* Utilities */
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-bold {
            font-weight: bold;
        }
        
        .mb-10 {
            margin-bottom: 10px;
        }
        
        .mt-10 {
            margin-top: 10px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-style: italic;
        }
    </style>
</head>
<body>
    @if($userRole === 'super_admin' && isset($groupedCampuses) && !empty($groupedCampuses) && $submissions->count() > 0)
        {{-- SUPER ADMIN: Multi-Campus Consolidated Format --}}
        <div class="report-title">UNIVERSITY ACCOMPLISHMENT PERFORMANCE SYSTEM (UPAS)</div>
        <div class="report-subtitle">CONSOLIDATED REPORT</div>
        
        @foreach($groupedCampuses as $campusName => $campusSubmissions)
            <div class="campus-header {{ !$loop->first ? 'page-break' : '' }}">{{ $campusName }}</div>
            
            @foreach($campusSubmissions as $submission)
                @php
                    // Ensure submission is loaded with relationships
                    if (!$submission->relationLoaded('submitter')) {
                        $submission->load('submitter');
                    }
                    if (!$submission->relationLoaded('approval')) {
                        $submission->load('approval');
                    }
                @endphp
                <div class="template-info-text" style="margin: 15px 0; line-height: 1.8;">
                    <div style="font-weight: bold; font-size: 13px;">Template Code: {{ $submission->template_code ?? 'N/A' }}</div>
                    <div style="font-weight: bold; font-size: 13px;">Strategic Goal (SG): {{ $submission->sg_code ?? 'N/A' }}</div>
                    <div style="font-weight: bold; font-size: 13px;">Key Result Area (KRA): {{ $submission->kra_title ?? 'N/A' }}</div>
                    <div style="font-weight: bold; font-size: 13px;">Key Performance Indicator (KPI): {{ $submission->kpi_title ?? 'N/A' }}</div>
                </div>
                
                @php
                    $tableData = \App\Support\SubmissionTableData::asArray($submission->table_data);
                    $pdfHeaders = \App\Support\SubmissionTableData::dataColumnKeys($tableData);
                @endphp

                @if(count($pdfHeaders) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                @foreach($pdfHeaders as $header)
                                    <th>{{ ucwords(str_replace('_', ' ', $header)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tableData as $row)
                                <tr>
                                    @foreach($pdfHeaders as $header)
                                        @php
                                            $cell = $row[$header] ?? '';
                                            $cell = is_array($cell) ? json_encode($cell) : ($cell ?? '');
                                        @endphp
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">No data available for this submission</div>
                @endif
                
                @if(!$loop->last)
                    <div style="margin-bottom: 20px;"></div>
                @endif
            @endforeach
            
            @if(!$loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach
        
    @else
        {{-- CAMPUS USER / CAMPUS ADMIN: Single Campus Format --}}
        <div class="report-title">UNIVERSITY ACCOMPLISHMENT PERFORMANCE SYSTEM (UPAS)</div>
        <div class="report-subtitle">{{ strtoupper($campusName ?? 'CAMPUS REPORT') }}</div>
        
        @if($submissions->count() > 0)
            @foreach($submissions as $submission)
                <div class="template-info-text" style="margin: 15px 0; line-height: 1.8; {{ !$loop->first ? 'margin-top: 30px;' : '' }}">
                    <div style="font-weight: bold; font-size: 13px;">Template Code: {{ $submission->template_code ?? 'N/A' }}</div>
                    <div style="font-weight: bold; font-size: 13px;">Strategic Goal (SG): {{ $submission->sg_code ?? 'N/A' }}</div>
                    <div style="font-weight: bold; font-size: 13px;">Key Result Area (KRA): {{ $submission->kra_title ?? 'N/A' }}</div>
                    <div style="font-weight: bold; font-size: 13px;">Key Performance Indicator (KPI): {{ $submission->kpi_title ?? 'N/A' }}</div>
                </div>
                
                @php
                    $tableData = \App\Support\SubmissionTableData::asArray($submission->table_data);
                    $pdfHeaders = \App\Support\SubmissionTableData::dataColumnKeys($tableData);
                @endphp

                @if(count($pdfHeaders) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                @foreach($pdfHeaders as $header)
                                    <th>{{ ucwords(str_replace('_', ' ', $header)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tableData as $row)
                                <tr>
                                    @foreach($pdfHeaders as $header)
                                        @php
                                            $cell = $row[$header] ?? '';
                                            $cell = is_array($cell) ? json_encode($cell) : ($cell ?? '');
                                        @endphp
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">No data available for this submission</div>
                @endif
                
                @if(!$loop->last)
                    <div style="margin-bottom: 20px;"></div>
                @endif
            @endforeach
        @else
            <div class="empty-state">No submissions found for the selected criteria.</div>
        @endif
    @endif

    @if(!empty($compact_preview_footer))
    <div class="footer" style="margin-top: 24px; padding: 12px; border-top: 1px solid #999; font-size: 9pt; color: #4b5563;">
        <p class="text-center" style="margin: 0;"><em>Signature blocks are omitted in this screen preview. They appear in the downloaded PDF.</em></p>
    </div>
    @else
    <div class="footer">
        <table class="signature-table">
            <tr>
                <td class="signature-column">
                    <div class="signature-title">Prepared by:</div>
                    <div class="signature-note">(e-signature over printed name)</div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Planning Coordinator, ALAMINOS Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Planning Coordinator, ASINGAN Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Planning Coordinator, BAYAMBANG Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Planning Coordinator, BINMALEY Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Planning Coordinator, INFANTA Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Planning Coordinator, LINGAYEN Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Planning Coordinator, SAN CARLOS Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Planning Coordinator, STA. MARIA Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Planning Coordinator, URDANETA Campus</div>
                    </div>
                </td>
                
                <td class="signature-column">
                    <div class="signature-title">Certified Correct by:</div>
                    <div class="signature-note">(e-signature over printed name)</div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Campus Executive Director, ALAMINOS Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Campus Executive Director, ASINGAN Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Campus Executive Director, BAYAMBANG Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Campus Executive Director, BINMALEY Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Campus Executive Director, INFANTA Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Campus Executive Director, LINGAYEN Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Campus Executive Director, SAN CARLOS Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Campus Executive Director, STA. MARIA Campus</div>
                    </div>
                    
                    <div class="signature-entry">
                        <div class="signature-line">______________________________</div>
                        <div class="signature-name">Campus Executive Director, URDANETA Campus</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    @endif
</body>
</html>

