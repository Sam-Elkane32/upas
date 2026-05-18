<?php

namespace App\Support;

/**
 * Normalizes submission table_data for display/export so columns stay aligned
 * with stored JSON (e.g. QA "Evidence Verified" on data rows after a summary row).
 */
final class SubmissionTableData
{
    public static function asArray(mixed $tableData): array
    {
        if ($tableData === null) {
            return [];
        }
        if (is_string($tableData)) {
            $decoded = json_decode($tableData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            return [];
        }

        return is_array($tableData) ? $tableData : [];
    }

    public static function rowIsSummary(array $row): bool
    {
        $meta = $row['_meta'] ?? [];
        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?? [];
        }
        if (! is_array($meta)) {
            $meta = [];
        }
        if (($meta['row_type'] ?? 'data') === 'summary') {
            return true;
        }
        foreach ($row as $rk => $rv) {
            if ($rk === '_meta' || $rk === '_after_separator') {
                continue;
            }
            if (strtolower(trim((string) $rv)) === 'summary') {
                return true;
            }
        }

        return false;
    }

    /**
     * Column keys for all non-summary rows, in first-seen order (union).
     * If there are no data rows, falls back to the first row's keys.
     *
     * @param  array<int, mixed>  $tableData
     * @return list<string>
     */
    public static function dataColumnKeys(array $tableData): array
    {
        $keys = [];
        foreach ($tableData as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (self::rowIsSummary($row)) {
                continue;
            }
            foreach (array_keys($row) as $k) {
                if ($k === '_meta' || $k === '_after_separator') {
                    continue;
                }
                if (! in_array($k, $keys, true)) {
                    $keys[] = $k;
                }
            }
        }
        if ($keys !== []) {
            return $keys;
        }
        $first = $tableData[0] ?? null;
        if (! is_array($first)) {
            return [];
        }

        return array_values(array_diff(array_keys($first), ['_meta', '_after_separator']));
    }
}
