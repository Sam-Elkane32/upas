<?php

namespace App\Exports\CampusUser;

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

class TsheetExport implements FromArray, WithStyles, WithColumnWidths, WithEvents, WithTitle
{
    protected $data;
    protected $campusName;
    protected $user;
    protected $rowPositions = [
        'title_start' => 1,
        'template_info_start' => 0,
        'main_table_start' => 0,
        'signature_start' => 0,
    ];
    protected $fieldKeys = []; // Store field keys for each group

    public function __construct(array $data, $campusName, $user)
    {
        $this->data = $data;
        $this->campusName = $campusName;
        $this->user = $user;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $rows = [];
        $currentRow = 1;

        // Process each group (Template Code, SG, KRA, KPI combination)
        foreach ($this->data as $groupKey => $group) {
            $title = $group['title'];
            $fieldMapping = $group['field_mapping'] ?? [];
            $allDataKeys = $group['all_data_keys'] ?? [];
            
            // Get field keys and labels - prioritize actual data keys (like PDF export)
            // Use first row's keys to preserve order (like PDF: array_keys($submission->table_data[0]))
            $fieldKeys = [];
            $fieldLabels = [];
            
            if (!empty($group['main_data_rows'])) {
                // EXACT PDF APPROACH: Use first row's keys for headers
                // PDF: @foreach(array_keys($submission->table_data[0]) as $header)
                $firstRowKeys = array_keys($group['main_data_rows'][0]);
                
                // Use ALL keys from first row in exact order (like PDF export)
                // Don't add normalized keys here - use original keys as they appear
                foreach ($firstRowKeys as $key) {
                    $fieldKeys[] = $key;
                    
                    // Get label from field_mapping if available, otherwise format the key (like PDF)
                    // PDF: {{ ucwords(str_replace('_', ' ', $header)) }}
                    $normalized = strtolower(trim(str_replace(['"', "'"], '', $key)));
                    $normalized = preg_replace('/\s+/', '_', $normalized);
                    $normalized = preg_replace('/[^a-z0-9_]/', '_', $normalized);
                    $normalized = preg_replace('/_+/', '_', $normalized);
                    $normalized = trim($normalized, '_');
                    
                    if (isset($fieldMapping[$normalized])) {
                        $fieldLabels[] = $fieldMapping[$normalized];
                    } elseif (isset($fieldMapping[$key])) {
                        $fieldLabels[] = $fieldMapping[$key];
                    } else {
                        // Format like PDF export: ucwords(str_replace('_', ' ', $header))
                        $label = ucwords(str_replace('_', ' ', $key));
                        $fieldLabels[] = $label;
                    }
                }
            } else {
                // Fallback: use field_mapping keys
                foreach ($fieldMapping as $key => $label) {
                    $fieldKeys[] = $key;
                    $fieldLabels[] = $label;
                }
            }
            
            // A) TITLE HEADER SECTION
            $titleRow = ['Template Code: ' . ($title['template_code'] ?? '')];
            $titleRow = array_pad($titleRow, count($fieldLabels), '');
            $rows[] = $titleRow;
            $currentRow++;
            
            $titleRow = ['Strategic Goal (SG): ' . ($title['sg_code'] ?? '')];
            $titleRow = array_pad($titleRow, count($fieldLabels), '');
            $rows[] = $titleRow;
            $currentRow++;
            
            $titleRow = ['Key Result Area (KRA): ' . ($title['kra_title'] ?? '')];
            $titleRow = array_pad($titleRow, count($fieldLabels), '');
            $rows[] = $titleRow;
            $currentRow++;
            
            $titleRow = ['Key Performance Indicator (KPI): ' . ($title['kpi_title'] ?? '')];
            $titleRow = array_pad($titleRow, count($fieldLabels), '');
            $rows[] = $titleRow;
            $currentRow++;
            
            $rows[] = array_fill(0, count($fieldLabels), ''); // Empty row
            $currentRow++;
            
            // Store title section row range
            if (!isset($this->rowPositions['title_start'])) {
                $this->rowPositions['title_start'] = 1;
            }
            
            // MAIN TSHEET TABLE - Dynamic headers
            $mainTableStart = $currentRow;
            $this->fieldKeys[$groupKey] = $fieldKeys; // Store field keys for this group
            $rows[] = $fieldLabels; // Dynamic header row with all field labels
            $currentRow++;
            
            // Data rows - include all fields dynamically (EXACTLY like PDF export)
            // PDF: @foreach($row as $value) - iterates through values in row order
            // We need to extract values in the same order as fieldKeys, but use the row's natural order
            foreach ($group['main_data_rows'] as $dataRow) {
                $rowData = [];
                
                // EXACT PDF APPROACH: Use fieldKeys from first row, extract values from current row
                // For each field key, get the value from the row
                foreach ($fieldKeys as $fieldKey) {
                    // Direct key access (like PDF: $row[$key])
                    $value = null;
                    
                    // Try exact key first (most common case)
                    if (isset($dataRow[$fieldKey])) {
                        $value = $dataRow[$fieldKey];
                    } else {
                        // If exact key not found, try to find it by iterating through row keys
                        // This handles cases where keys might differ slightly
                        foreach ($dataRow as $rowKey => $rowValue) {
                            // Exact match
                            if ($rowKey === $fieldKey) {
                                $value = $rowValue;
                                break;
                            }
                            
                            // Case-insensitive match
                            if (strtolower(trim($rowKey)) === strtolower(trim($fieldKey))) {
                                $value = $rowValue;
                                break;
                            }
                            
                            // Normalized match
                            $rowKeyNorm = strtolower(trim(str_replace(['"', "'"], '', $rowKey)));
                            $rowKeyNorm = preg_replace('/\s+/', '_', $rowKeyNorm);
                            $rowKeyNorm = preg_replace('/[^a-z0-9_]/', '_', $rowKeyNorm);
                            $rowKeyNorm = preg_replace('/_+/', '_', $rowKeyNorm);
                            $rowKeyNorm = trim($rowKeyNorm, '_');
                            
                            $fieldKeyNorm = strtolower(trim(str_replace(['"', "'"], '', $fieldKey)));
                            $fieldKeyNorm = preg_replace('/\s+/', '_', $fieldKeyNorm);
                            $fieldKeyNorm = preg_replace('/[^a-z0-9_]/', '_', $fieldKeyNorm);
                            $fieldKeyNorm = preg_replace('/_+/', '_', $fieldKeyNorm);
                            $fieldKeyNorm = trim($fieldKeyNorm, '_');
                            
                            if ($rowKeyNorm === $fieldKeyNorm && $rowKeyNorm !== '') {
                                $value = $rowValue;
                                break;
                            }
                        }
                    }
                    
                    // Format value EXACTLY like PDF: is_array($value) ? json_encode($value) : ($value ?? '')
                    if (is_array($value)) {
                        $value = json_encode($value);
                    } else {
                        $value = $value ?? '';
                    }
                    
                    $rowData[] = $value;
                }
                
                $rows[] = $rowData;
                $currentRow++;
            }
            
            $this->rowPositions['main_table_start'] = $mainTableStart;
            $rows[] = array_fill(0, count($fieldLabels), ''); // Empty row
            $currentRow++;
        }
        
        // D) FOOTER SIGNATURE SECTION
        $this->rowPositions['signature_start'] = $currentRow;
        $rows[] = ['Prepared by:', '', '', '', '', '', '', '', '', '', '', ''];
        $currentRow++;
        $rows[] = ['(e-signature over printed name)', '', '', '', '', '', '', '', '', '', '', ''];
        $currentRow++;
        
        $campuses = [
            'ALAMINOS', 'ASINGAN', 'BAYAMBANG', 'BINMALEY', 
            'INFANTA', 'LINGAYEN', 'SAN CARLOS', 'STA. MARIA', 'URDANETA'
        ];
        
        foreach ($campuses as $campus) {
            $rows[] = ['______________________________', '', '', '', '', '', '', '', '', '', '', ''];
            $currentRow++;
            $rows[] = ['Planning Coordinator, ' . $campus . ' Campus', '', '', '', '', '', '', '', '', '', '', ''];
            $currentRow++;
        }
        
        $rows[] = ['', '', '', '', '', '', '', '', '', '', '', '']; // Empty row
        $currentRow++;
        $rows[] = ['Certified Correct by:', '', '', '', '', '', '', '', '', '', '', ''];
        $currentRow++;
        $rows[] = ['(e-signature over printed name)', '', '', '', '', '', '', '', '', '', '', ''];
        $currentRow++;
        
        foreach ($campuses as $campus) {
            $rows[] = ['______________________________', '', '', '', '', '', '', '', '', '', '', ''];
            $currentRow++;
            $rows[] = ['Campus Executive Director, ' . $campus . ' Campus', '', '', '', '', '', '', '', '', '', '', ''];
            $currentRow++;
        }
        
        return $rows;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'TSHEET Report';
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        // Calculate dynamic column widths based on data
        $widths = [];
        $maxColumns = 0;
        
        // Find maximum number of columns across all groups
        foreach ($this->data as $group) {
            $fieldMapping = $group['field_mapping'] ?? [];
            $allKeys = [];
            foreach ($group['main_data_rows'] as $dataRow) {
                $allKeys = array_merge($allKeys, array_keys($dataRow));
            }
            $maxColumns = max($maxColumns, count(array_unique($allKeys)));
        }
        
        // Ensure minimum columns
        $maxColumns = max($maxColumns, 12);
        
        // Generate column widths dynamically
        $columnLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        
        for ($i = 0; $i < $maxColumns; $i++) {
            if ($i < count($columnLetters)) {
                $letter = $columnLetters[$i];
                // Default widths: first column wider, others medium
                if ($i === 0) {
                    $widths[$letter] = 35;
                } elseif ($i === 1) {
                    $widths[$letter] = 15;
                } else {
                    $widths[$letter] = 25;
                }
            }
        }
        
        return $widths;
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Find actual row positions by scanning
                $titleStartRow = 1;
                $mainTableStartRow = null;
                $signatureStartRow = null;
                
                for ($row = 1; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('A' . $row)->getValue();
                    if (is_string($cellValue)) {
                        // Check if this row looks like a header (has multiple non-empty cells after title rows)
                        // Title rows are single cell merged, header rows have multiple columns
                        if ($row > 5 && !$mainTableStartRow) { // After title section (title is usually rows 1-5)
                            $bCell = $sheet->getCell('B' . $row)->getValue();
                            $cCell = $sheet->getCell('C' . $row)->getValue();
                            // Check if this row has multiple filled cells (likely a header row)
                            if ($bCell !== null && $bCell !== '' && $cCell !== null && $cCell !== '') {
                                // Check a few more columns to confirm it's a header row
                                $dCell = $sheet->getCell('D' . $row)->getValue();
                                if ($dCell !== null && $dCell !== '') {
                                    // This looks like a header row (has at least 3 columns)
                                    $mainTableStartRow = $row;
                                }
                            }
                        }
                        
                        if (strpos($cellValue, 'Prepared by:') !== false) {
                            $signatureStartRow = $row;
                            break;
                        }
                    }
                }

                // A) Style Title Header Section
                for ($row = 1; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('A' . $row)->getValue();
                    if (is_string($cellValue)) {
                        if (strpos($cellValue, 'Template Code:') !== false || 
                            strpos($cellValue, 'Strategic Goal') !== false ||
                            strpos($cellValue, 'Key Result Area') !== false ||
                            strpos($cellValue, 'Key Performance Indicator') !== false) {
                            // Merge across all columns (find last column dynamically)
                            $lastCol = $sheet->getHighestColumn();
                            $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
                            $sheet->getStyle('A' . $row)->applyFromArray([
                                'font' => ['bold' => true, 'size' => 12],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                            ]);
                        }
                    }
                }

                // Style Main TSHEET Table
                if ($mainTableStartRow) {
                    // Find the actual number of columns by checking the header row
                    $headerRow = $sheet->getRowIterator($mainTableStartRow, $mainTableStartRow)->current();
                    $lastColumn = 'A';
                    $columnCount = 0;
                    foreach ($headerRow->getCellIterator() as $cell) {
                        $cellValue = $cell->getValue();
                        if ($cellValue !== null && $cellValue !== '') {
                            $lastColumn = $cell->getColumn();
                            $columnCount++;
                        }
                    }
                    
                    // If no columns found, use a default
                    if ($columnCount === 0) {
                        $lastColumn = 'L'; // Default to 12 columns
                    }
                    
                    // Header row - dynamic range
                    $sheet->getStyle('A' . $mainTableStartRow . ':' . $lastColumn . $mainTableStartRow)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E6E6E6'],
                        ],
                        'font' => ['bold' => true, 'size' => 11],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                    
                    // Data rows - find end of main table
                    $dataEndRow = $mainTableStartRow + 1;
                    for ($row = $mainTableStartRow + 1; $row <= $highestRow; $row++) {
                        $cellValue = $sheet->getCell('A' . $row)->getValue();
                        if (is_string($cellValue) && strpos($cellValue, 'Prepared by:') !== false) {
                            $dataEndRow = $row - 2;
                            break;
                        }
                        if ($row == $highestRow) {
                            $dataEndRow = $row;
                        }
                    }
                    
                    if ($mainTableStartRow + 1 <= $dataEndRow) {
                        // Use the same last column as header
                        $sheet->getStyle('A' . ($mainTableStartRow + 1) . ':' . $lastColumn . $dataEndRow)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '000000'],
                                ],
                            ],
                            'alignment' => [
                                'wrapText' => true,
                                'vertical' => Alignment::VERTICAL_TOP,
                            ],
                        ]);
                    }
                }

                // D) Style Signature Section
                if ($signatureStartRow) {
                    $sheet->getStyle('A' . $signatureStartRow)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                    ]);
                    $sheet->getStyle('A' . ($signatureStartRow + 1))->applyFromArray([
                        'font' => ['italic' => true, 'size' => 10],
                    ]);
                }

                // Apply borders to all cells
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            },
        ];
    }
}
