<?php

namespace App\Exports\CampusAdmin;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class VpassExcelExport implements FromArray, WithStyles, WithColumnWidths, WithEvents, WithTitle
{
    protected $vpassData;
    protected $formTitle;

    public function __construct(array $vpassData, $formTitle)
    {
        $this->vpassData = $vpassData;
        $this->formTitle = $formTitle;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $rows = [];
        
        // Header - Form Title only
        $rows[] = [strtoupper($this->formTitle)];
        $rows[] = []; // Empty row
        
        // Table headers
        $rows[] = [
            'SG',
            'KRA',
            'KPI No.',
            'SDP KPI No.',
            'Key Performance Indicator',
            'Description',
            'Responsible Work Units',
            'Q1 Target',
            'Q2 Target',
            'Q3 Target',
            'Q4 Target',
            'Total Target',
            'Q1 Accomplishment',
            'Q2 Accomplishment',
            'Q3 Accomplishment',
            'Q4 Accomplishment',
            'Total Accomplishment',
            'Variance',
            'Rate of Accomplishment (%)',
            'Descriptive Rating'
        ];
        
        // Data rows
        foreach ($this->vpassData as $sg) {
            foreach ($sg['kras'] as $kra) {
                foreach ($kra['kpis'] as $kpi) {
                    $rows[] = [
                        $sg['sg_code'],
                        $kra['kra_title'],
                        $kpi['kpi_no'],
                        $kpi['sdp_kpi_no'],
                        $kpi['key_performance_indicator'],
                        $kpi['description'],
                        $kpi['responsible_work_units'],
                        $kpi['target_q1'],
                        $kpi['target_q2'],
                        $kpi['target_q3'],
                        $kpi['target_q4'],
                        $kpi['target_total'],
                        $kpi['accomplishment_q1'],
                        $kpi['accomplishment_q2'],
                        $kpi['accomplishment_q3'],
                        $kpi['accomplishment_q4'],
                        $kpi['accomplishment_total'],
                        $kpi['variance'],
                        $kpi['rate_of_accomplishment'],
                        $kpi['descriptive_rating']
                    ];
                }
            }
        }
        
        return $rows;
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // SG
            'B' => 15,  // KRA
            'C' => 10,  // KPI No.
            'D' => 12,  // SDP KPI No.
            'E' => 25,  // Key Performance Indicator
            'F' => 30,  // Description
            'G' => 20,  // Responsible Work Units
            'H' => 12,  // Q1 Target
            'I' => 12,  // Q2 Target
            'J' => 12,  // Q3 Target
            'K' => 12,  // Q4 Target
            'L' => 12,  // Total Target
            'M' => 15,  // Q1 Accomplishment
            'N' => 15,  // Q2 Accomplishment
            'O' => 15,  // Q3 Accomplishment
            'P' => 15,  // Q4 Accomplishment
            'Q' => 18,  // Total Accomplishment
            'R' => 12,  // Variance
            'S' => 22,  // Rate of Accomplishment
            'T' => 18,  // Descriptive Rating
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
            3 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']]],
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->formTitle;
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Style header row
                $sheet->getStyle('A4:T4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0E0E0']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);
                
                // Merge header cells
                $sheet->mergeCells('A1:T1');
                
                // Style data rows
                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 3) {
                    $sheet->getStyle('A4:T' . $highestRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000']
                            ]
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_TOP,
                            'wrapText' => true
                        ]
                    ]);
                    
                    // Number format for numeric columns
                    $numericColumns = ['H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'];
                    foreach ($numericColumns as $col) {
                        $sheet->getStyle($col . '4:' . $col . $highestRow)->getNumberFormat()->setFormatCode('#,##0.00');
                    }
                }
            },
        ];
    }
}

