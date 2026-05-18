<?php

namespace App\Services;

/**
 * Builds specific, human-readable change descriptions for table_data diffs
 * so Audit Trailing / Editing History shows entries like "Planning Coordinator
 * set Quarter to '1st' in row 1" instead of generic messages.
 */
class TableDataAuditHelper
{
    /** Max length for a single audit message (DB column limit). */
    public const MAX_MESSAGE_LENGTH = 500;

    /**
     * Describe changes between old and new table_data rows for audit log.
     *
     * @param array<int, array<string, mixed>> $oldRows
     * @param array<int, array<string, mixed>> $newRows
     * @param array<string, string> $columnLabels Map of field key => display label (e.g. 'quarter' => 'Quarter')
     * @return array<int, string> List of change description strings
     */
    public static function describeTableDataChanges(array $oldRows, array $newRows, array $columnLabels = []): array
    {
        $changes = [];
        $oldRows = static::normalizeRows($oldRows);
        $newRows = static::normalizeRows($newRows);
        $maxIndex = max(count($oldRows), count($newRows));

        for ($i = 0; $i < $maxIndex; $i++) {
            $rowNum = $i + 1;
            $oldRow = $oldRows[$i] ?? null;
            $newRow = $newRows[$i] ?? null;

            if ($oldRow === null && $newRow !== null) {
                $parts = [];
                foreach ($newRow as $key => $value) {
                    if ($key === '_after_separator' || $key === '_meta') {
                        continue;
                    }
                    $valueStr = static::stringValue($value);
                    if ($valueStr === '') {
                        continue;
                    }
                    $label = static::columnLabel($key, $columnLabels);
                    $parts[] = "{$label} to '{$valueStr}'";
                }
                if (count($parts) > 0) {
                    $changes[] = "Added row {$rowNum}: " . implode(', ', $parts);
                } else {
                    $changes[] = "Added row {$rowNum}";
                }
            } elseif ($oldRow !== null && $newRow === null) {
                $changes[] = "Removed row {$rowNum}";
            } elseif ($oldRow !== null && $newRow !== null) {
                $allKeys = static::allColumnKeys($oldRow, $newRow);
                foreach ($allKeys as $key) {
                    if ($key === '_after_separator' || $key === '_meta') {
                        continue;
                    }
                    $oldKey = static::findKey($oldRow, $key) ?? $key;
                    $newKey = static::findKey($newRow, $key) ?? $key;
                    $oldVal = static::stringValue($oldRow[$oldKey] ?? null);
                    $newVal = static::stringValue($newRow[$newKey] ?? null);
                    if ($oldVal === $newVal) {
                        continue;
                    }
                    $displayKey = $newKey ?? $oldKey ?? $key;
                    $label = static::columnLabel($displayKey, $columnLabels);
                    if ($newVal === '') {
                        $changes[] = "Row {$rowNum}: cleared {$label}";
                    } else {
                        $changes[] = "Row {$rowNum}: set {$label} to '{$newVal}'";
                    }
                }
            }
        }

        return $changes;
    }

    /** Get all column keys from both rows, normalized (use first occurrence for display). */
    private static function allColumnKeys(array $oldRow, array $newRow): array
    {
        $seen = [];
        $keys = [];
        foreach (array_merge(array_keys($newRow), array_keys($oldRow)) as $k) {
            $norm = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $k), '_'));
            if ($norm === '' || isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $keys[] = $k;
        }
        return $keys;
    }

    /** Find key in row that matches normalized key (case-insensitive, slug). */
    private static function findKey(array $row, string $key): ?string
    {
        $norm = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $key), '_'));
        foreach (array_keys($row) as $k) {
            $n = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $k), '_'));
            if ($n === $norm) {
                return $k;
            }
        }
        return null;
    }

    /**
     * Build a single audit message from change list, with optional prefix and length limit.
     */
    public static function buildAuditMessage(string $prefix, array $changes, int $maxLength = self::MAX_MESSAGE_LENGTH): string
    {
        if (count($changes) === 0) {
            return rtrim($prefix, ': ') . ': updated table data.';
        }

        $message = $prefix . implode('; ', $changes);
        if (strlen($message) <= $maxLength) {
            return $message;
        }

        $truncated = substr($message, 0, $maxLength - 15) . '… (more)';
        return $truncated;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, string>>
     */
    private static function normalizeRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (is_object($row)) {
                $row = (array) $row;
            }
            if (!is_array($row)) {
                continue;
            }
            $normalized = [];
            foreach ($row as $k => $v) {
                $normalized[(string) $k] = $v;
            }
            $out[] = $normalized;
        }
        return $out;
    }

    private static function stringValue($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        $s = (string) $value;
        return strlen($s) > 80 ? substr($s, 0, 77) . '...' : $s;
    }

    private static function columnLabel(string $key, array $columnLabels): string
    {
        if (isset($columnLabels[$key])) {
            return $columnLabels[$key];
        }
        // Humanize key: quarter -> Quarter, program_name -> Program name
        $label = str_replace('_', ' ', $key);
        $label = ucwords(strtolower($label));
        return $label;
    }

    /**
     * Get column key => label map from template fields_json.
     *
     * @param \App\Models\Template|null $template
     * @return array<string, string>
     */
    public static function getColumnLabelsFromTemplate($template): array
    {
        $labels = [];
        if (!$template || !is_array($template->fields_json ?? null)) {
            return $labels;
        }
        $fields = $template->fields_json['fields'] ?? [];
        if (!is_array($fields)) {
            return $labels;
        }
        foreach ($fields as $f) {
            if (!is_array($f)) {
                continue;
            }
            $key = $f['key'] ?? $f['name'] ?? null;
            if ($key === null) {
                $label = $f['label'] ?? '';
                $key = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($label)));
            }
            $labels[$key] = $f['label'] ?? $key;
        }
        return $labels;
    }
}
