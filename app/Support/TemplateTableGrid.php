<?php

namespace App\Support;

/**
 * Expands template schema fields so multi-entry subheaders become separate table columns
 * under a shared parent header (colspan in row 1, sub-labels in row 2).
 */
class TemplateTableGrid
{
    public static function normalizeKeyFromLabel(string $label): string
    {
        $s = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($label)));

        return trim($s, '_') !== '' ? trim($s, '_') : 'col';
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields  Schema fields (e.g. after performance columns filtered out)
     * @param  callable(array): string  $getFieldKey
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>, 2: bool}
     */
    public static function expandFieldsWithSubheaderGroups(array $fields, callable $getFieldKey): array
    {
        $expanded = [];
        $headerPlan = [];

        foreach ($fields as $f) {
            if (! is_array($f)) {
                continue;
            }
            $subs = $f['subheaders'] ?? [];
            if (! is_array($subs)) {
                $subs = [];
            }
            $subs = array_values(array_filter(array_map(static function ($s) {
                return trim((string) $s);
            }, $subs), static fn ($s) => $s !== ''));

            if (count($subs) >= 2) {
                $parentKey = (string) $getFieldKey($f);
                $headerPlan[] = [
                    'kind' => 'group',
                    'parent_label' => (string) ($f['label'] ?? ''),
                    'subs' => $subs,
                ];
                $usedSuffix = [];
                foreach ($subs as $si => $subLabel) {
                    $base = $parentKey.'_'.self::normalizeKeyFromLabel($subLabel);
                    $key = $base;
                    $n = 2;
                    while (isset($usedSuffix[$key])) {
                        $key = $base.'_'.$n;
                        $n++;
                    }
                    $usedSuffix[$key] = true;
                    $child = $f;
                    unset($child['subheaders']);
                    $child['key'] = $key;
                    $child['label'] = $subLabel;
                    $child['_grid_parent_label'] = (string) ($f['label'] ?? '');
                    $child['_grid_parent_key'] = $parentKey;
                    $child['_grid_is_subcolumn'] = true;
                    $child['_grid_subcolumn_index'] = $si;
                    $expanded[] = $child;
                }
            } else {
                $headerPlan[] = ['kind' => 'single'];
                $expanded[] = $f;
            }
        }

        $useTwoRow = false;
        foreach ($headerPlan as $h) {
            if (($h['kind'] ?? '') === 'group') {
                $useTwoRow = true;
                break;
            }
        }

        return [$expanded, $headerPlan, $useTwoRow];
    }
}
