<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * SuperAdminSummaryExport
 * 
 * Excel export for Summary of Accomplishments
 * Matches the preview format exactly
 */
class SuperAdminSummaryExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Summary of Accomplishments';
    }

    public function collection()
    {
        $rows = collect();

        // Header
        $rows->push(['SUMMARY OF ACCOMPLISHMENTS']);
        $rows->push(['Pangasinan State University']);
        $rows->push(['Generated: ' . $this->data['university_stats']['date_generated']]);
        $rows->push(['']);

        // University Statistics
        $rows->push(['University-Wide Statistics']);
        $rows->push([
            'Campuses',
            $this->data['university_stats']['total_campuses'],
            'Reporting activity (submissions + VPASS)',
            $this->data['university_stats']['total_approved_submissions'],
            'Strategic Goals',
            $this->data['university_stats']['total_sgs'],
            'Key Result Areas',
            $this->data['university_stats']['total_kras'],
            'Key Performance Indicators',
            $this->data['university_stats']['total_kpis']
        ]);
        $rows->push(['']);

        // KPI & Template Totals
        $rows->push(['KPI & Template Totals']);
        $rows->push(['Total KPIs', $this->data['overall_breakdown']['total_kpis']]);
        $rows->push(['Total Accomplishment Templates', $this->data['university_stats']['total_approved_submissions']]);
        $rows->push(['']);

        // KPI Status Breakdown
        $rows->push(['KPI Status Breakdown (University-Wide)']);
        $rows->push(['Category', 'Count', 'Percentage']);
        
        $totalKpis = $this->data['overall_breakdown']['total_kpis'];
        $calculatePercentage = function($count, $total) {
            if ($total == 0) return '0.00';
            return number_format(($count / $total) * 100, 2) . '%';
        };

        $rows->push(['KPIs with No Target', $this->data['overall_breakdown']['no_target'], $calculatePercentage($this->data['overall_breakdown']['no_target'], $totalKpis)]);
        $rows->push(['KPIs with No Accomplishment', $this->data['overall_breakdown']['no_accomplishment'], $calculatePercentage($this->data['overall_breakdown']['no_accomplishment'], $totalKpis)]);
        $rows->push(['KPIs Below Target', $this->data['overall_breakdown']['below_target'], $calculatePercentage($this->data['overall_breakdown']['below_target'], $totalKpis)]);
        $rows->push(['KPIs Met Target', $this->data['overall_breakdown']['met_target'], $calculatePercentage($this->data['overall_breakdown']['met_target'], $totalKpis)]);
        $rows->push(['KPIs Above Target', $this->data['overall_breakdown']['above_target'], $calculatePercentage($this->data['overall_breakdown']['above_target'], $totalKpis)]);
        $rows->push(['']);

        // Extended VPASS-style breakdown (merged legacy + VPASS)
        $ext = $this->data['extended_overall'] ?? [];
        $extTotal = (int) ($ext['total_kpis'] ?? 0);
        if ($extTotal > 0) {
            $extPct = function ($count, $total) {
                if ($total == 0) {
                    return '0.00%';
                }

                return number_format(($count / $total) * 100, 2).'%';
            };
            $rows->push(['KPI status breakdown (university-wide rows)']);
            $rows->push(['Total KPI rows', $extTotal, 'With targets', (int) ($ext['total_with_targets'] ?? 0)]);
            $rows->push(['Category', 'Count', '% of extended total']);
            $rows->push(['KPIs with Above Target', (int) ($ext['above_target'] ?? 0), $extPct((int) ($ext['above_target'] ?? 0), $extTotal)]);
            $rows->push(['KPIs with Met Target', (int) ($ext['met_target'] ?? 0), $extPct((int) ($ext['met_target'] ?? 0), $extTotal)]);
            $rows->push(['KPIs with Below Target', (int) ($ext['below_target'] ?? 0), $extPct((int) ($ext['below_target'] ?? 0), $extTotal)]);
            $rows->push(['KPIs with No Accomplishment (with target)', (int) ($ext['no_accomplishment_with_target'] ?? 0), $extPct((int) ($ext['no_accomplishment_with_target'] ?? 0), $extTotal)]);
            $rows->push(['KPIs with Accomplishment (no annual target)', (int) ($ext['accomplishment_no_target'] ?? 0), $extPct((int) ($ext['accomplishment_no_target'] ?? 0), $extTotal)]);
            $rows->push(['KPIs with No Target (H1 empty, targets in H2)', (int) ($ext['no_target_q12'] ?? 0), $extPct((int) ($ext['no_target_q12'] ?? 0), $extTotal)]);
            $rows->push(['KPIs with No Target (annual / no plan)', (int) ($ext['no_target_annual'] ?? 0), $extPct((int) ($ext['no_target_annual'] ?? 0), $extTotal)]);
            $rows->push(['']);

            $matrix = $this->data['scorecard_performance_matrix'] ?? [];
            if (count($matrix) > 0) {
                $rows->push(['Balance by responsible work unit (split from KPI fields)']);
                $rows->push(['Responsible work unit', 'Total KPI rows', 'On track (count)', 'On track %', 'Off track (count)', 'Off track %']);
                foreach ($matrix as $m) {
                    $rows->push([
                        $m['work_unit'] ?? '',
                        (int) ($m['total_kpis'] ?? 0),
                        (int) ($m['positive_count'] ?? 0),
                        ($m['on_track_pct'] ?? 0).'%',
                        (int) ($m['negative_count'] ?? 0),
                        ($m['off_track_pct'] ?? 0).'%',
                    ]);
                }
                $rows->push(['']);
            }
        }

        // Summary by KRA
        $rows->push(['Summary by Key Result Area (KRA)']);
        $rows->push([
            'SG Code',
            'KRA Title',
            'Total KPIs',
            'No Target',
            '%',
            'No Accomplishment',
            '%',
            'Below Target',
            '%',
            'Met Target',
            '%',
            'Above Target',
            '%'
        ]);

        foreach ($this->data['kra_summary'] as $kraRow) {
            $kraTotal = $kraRow['total_kpis'];
            $kraPercentage = function($count, $total) {
                if ($total == 0) return '0.00';
                return number_format(($count / $total) * 100, 2) . '%';
            };

            $rows->push([
                $kraRow['sg_code'],
                $kraRow['kra_title'],
                $kraRow['total_kpis'],
                $kraRow['no_target'],
                $kraPercentage($kraRow['no_target'], $kraTotal),
                $kraRow['no_accomplishment'],
                $kraPercentage($kraRow['no_accomplishment'], $kraTotal),
                $kraRow['below_target'],
                $kraPercentage($kraRow['below_target'], $kraTotal),
                $kraRow['met_target'],
                $kraPercentage($kraRow['met_target'], $kraTotal),
                $kraRow['above_target'],
                $kraPercentage($kraRow['above_target'], $kraTotal),
            ]);
        }
        $rows->push(['']);

        // Summary by Responsible Work Unit
        $rows->push(['Summary by Responsible Work Unit']);
        $rows->push([
            'Responsible Work Unit',
            'Total KPIs',
            'No Target',
            '%',
            'No Accomplishment',
            '%',
            'Below Target',
            '%',
            'Met Target',
            '%',
            'Above Target',
            '%'
        ]);

        foreach ($this->data['work_unit_summary'] as $unitRow) {
            $unitTotal = $unitRow['total_kpis'];
            $unitPercentage = function($count, $total) {
                if ($total == 0) return '0.00';
                return number_format(($count / $total) * 100, 2) . '%';
            };

            $rows->push([
                $unitRow['work_unit'],
                $unitRow['total_kpis'],
                $unitRow['no_target'],
                $unitPercentage($unitRow['no_target'], $unitTotal),
                $unitRow['no_accomplishment'],
                $unitPercentage($unitRow['no_accomplishment'], $unitTotal),
                $unitRow['below_target'],
                $unitPercentage($unitRow['below_target'], $unitTotal),
                $unitRow['met_target'],
                $unitPercentage($unitRow['met_target'], $unitTotal),
                $unitRow['above_target'],
                $unitPercentage($unitRow['above_target'], $unitTotal),
            ]);
        }
        $rows->push(['']);

        // Campus-Side Distribution
        if (count($this->data['campus_stats']) > 0) {
            $rows->push(['Campus-Side Distribution']);
            $rows->push(['Campus', 'Total Approved Templates', 'Unique KPIs']);

            foreach ($this->data['campus_stats'] as $campus) {
                $rows->push([
                    $campus['campus_name'],
                    $campus['total_submissions'],
                    $campus['unique_kpis']
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [];
        
        // Header row
        $styles[1] = [
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $styles[2] = [
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $styles[3] = [
            'font' => ['size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        // Section headers
        $row = 5;
        foreach ($sheet->getRowIterator() as $rowData) {
            $rowNum = $rowData->getRowIndex();
            $cellValue = $sheet->getCell('A' . $rowNum)->getValue();
            
            if (is_string($cellValue) && (
                strpos($cellValue, 'University-Wide Statistics') !== false ||
                strpos($cellValue, 'KPI & Template Totals') !== false ||
                strpos($cellValue, 'KPI Status Breakdown') !== false ||
                strpos($cellValue, 'Summary by Key Result Area') !== false ||
                strpos($cellValue, 'Summary by Responsible Work Unit') !== false ||
                strpos($cellValue, 'STRATEGIC GOAL:') !== false ||
                strpos($cellValue, 'KRA:') !== false ||
                strpos($cellValue, 'Campus-Side Distribution') !== false ||
                strpos($cellValue, 'KPI status breakdown (university-wide rows)') !== false ||
                strpos($cellValue, 'Balance by responsible work unit') !== false
            )) {
                $styles[$rowNum] = [
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E5E7EB'],
                    ],
                ];
            }

            // Table headers (rows with specific column headers)
            if (is_string($cellValue) && (
                $cellValue === 'Category' ||
                $cellValue === 'SG Code' ||
                $cellValue === 'Responsible Work Unit' ||
                $cellValue === 'KPI Title' ||
                $cellValue === 'Campus'
            )) {
                $styles[$rowNum] = [
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ];
            }
        }

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,  // Labels/Names
            'B' => 15,  // Values
            'C' => 30,  // KRA Title / KPI Title
            'D' => 12,  // Counts
            'E' => 8,   // Percentages
            'F' => 12,
            'G' => 8,
            'H' => 12,
            'I' => 8,
            'J' => 12,
            'K' => 8,
            'L' => 12,
            'M' => 8,
        ];
    }
}
