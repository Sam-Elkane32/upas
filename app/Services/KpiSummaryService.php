<?php

namespace App\Services;

use App\Models\KPI;
use App\Models\KRA;
use Illuminate\Database\Eloquent\Collection;

class KpiSummaryService
{
    /**
     * Generate comprehensive summary of KPIs
     * 
     * @param Collection|null $kpis Optional collection of KPIs. If null, fetches all active KPIs.
     * @return array
     */
    public function generateSummary(?Collection $kpis = null): array
    {
        // If no KPIs provided, fetch all active KPIs with their KRA relationships
        if ($kpis === null) {
            $kpis = KPI::with('kra')
                ->where('is_active', true)
                ->get();
        }

        // Overall statistics
        $totalKpis = $kpis->count();
        $noTarget = $kpis->filter(function ($kpi) {
            return $kpi->target_total === null || $kpi->target_total == 0;
        })->count();
        
        $noAccomplishment = $kpis->filter(function ($kpi) {
            return $kpi->accomplishment_total === null || $kpi->accomplishment_total == 0;
        })->count();

        // Categorize KPIs by target achievement
        $belowTarget = $kpis->filter(function ($kpi) {
            if ($kpi->target_total === null || $kpi->target_total == 0) {
                return false; // Exclude no-target KPIs
            }
            if ($kpi->accomplishment_total === null || $kpi->accomplishment_total == 0) {
                return true; // No accomplishment means below target
            }
            return $kpi->accomplishment_total < $kpi->target_total;
        })->count();

        $metTarget = $kpis->filter(function ($kpi) {
            if ($kpi->target_total === null || $kpi->target_total == 0) {
                return false; // Exclude no-target KPIs
            }
            if ($kpi->accomplishment_total === null || $kpi->accomplishment_total == 0) {
                return false; // No accomplishment doesn't mean met target
            }
            // Met target: accomplishment equals target (within 0.01 tolerance) or rate >= 100%
            return abs($kpi->accomplishment_total - $kpi->target_total) < 0.01 
                || ($kpi->rate_of_accomplishment !== null && $kpi->rate_of_accomplishment >= 100);
        })->count();

        $aboveTarget = $kpis->filter(function ($kpi) {
            if ($kpi->target_total === null || $kpi->target_total == 0) {
                return false; // Exclude no-target KPIs
            }
            if ($kpi->accomplishment_total === null || $kpi->accomplishment_total == 0) {
                return false; // No accomplishment doesn't mean above target
            }
            return $kpi->accomplishment_total > $kpi->target_total;
        })->count();

        // Group by KRA
        $kraSummaries = $this->groupByKra($kpis);

        // Group by Responsible Work Units
        $workUnitSummaries = $this->groupByWorkUnit($kpis);

        return [
            'overall' => [
                'total_kpis' => $totalKpis,
                'no_target' => $noTarget,
                'no_accomplishment' => $noAccomplishment,
                'below_target' => $belowTarget,
                'met_target' => $metTarget,
                'above_target' => $aboveTarget,
            ],
            'by_kra' => $kraSummaries,
            'by_work_unit' => $workUnitSummaries,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Group KPIs by KRA and compute summary per KRA
     * 
     * @param Collection $kpis
     * @return array
     */
    protected function groupByKra(Collection $kpis): array
    {
        $grouped = $kpis->groupBy('kra_id');
        $summaries = [];

        foreach ($grouped as $kraId => $kraKpis) {
            $kra = $kraKpis->first()->kra;
            $kraCode = $kra ? $kra->code : "KRA-{$kraId}";
            $kraTitle = $kra ? $kra->title : "Unknown KRA";

            $total = $kraKpis->count();
            $noTarget = $kraKpis->filter(function ($kpi) {
                return $kpi->target_total === null || $kpi->target_total == 0;
            })->count();
            
            $noAccomplishment = $kraKpis->filter(function ($kpi) {
                return $kpi->accomplishment_total === null || $kpi->accomplishment_total == 0;
            })->count();

            $belowTarget = $kraKpis->filter(function ($kpi) {
                if ($kpi->target_total === null || $kpi->target_total == 0) return false;
                if ($kpi->accomplishment_total === null || $kpi->accomplishment_total == 0) return true;
                return $kpi->accomplishment_total < $kpi->target_total;
            })->count();

            $metTarget = $kraKpis->filter(function ($kpi) {
                if ($kpi->target_total === null || $kpi->target_total == 0) return false;
                if ($kpi->accomplishment_total === null || $kpi->accomplishment_total == 0) return false;
                return abs($kpi->accomplishment_total - $kpi->target_total) < 0.01 
                    || ($kpi->rate_of_accomplishment !== null && $kpi->rate_of_accomplishment >= 100);
            })->count();

            $aboveTarget = $kraKpis->filter(function ($kpi) {
                if ($kpi->target_total === null || $kpi->target_total == 0) return false;
                if ($kpi->accomplishment_total === null || $kpi->accomplishment_total == 0) return false;
                return $kpi->accomplishment_total > $kpi->target_total;
            })->count();

            $summaries[$kraCode] = [
                'kra_code' => $kraCode,
                'kra_title' => $kraTitle,
                'total_kpis' => $total,
                'no_target' => $noTarget,
                'no_accomplishment' => $noAccomplishment,
                'below_target' => $belowTarget,
                'met_target' => $metTarget,
                'above_target' => $aboveTarget,
            ];
        }

        // Sort by KRA code
        ksort($summaries);

        return $summaries;
    }

    /**
     * Group KPIs by Responsible Work Unit
     * 
     * @param Collection $kpis
     * @return array
     */
    protected function groupByWorkUnit(Collection $kpis): array
    {
        $grouped = $kpis->groupBy(function ($kpi) {
            return $kpi->responsible_unit ?: 'Unassigned';
        });

        $summaries = [];

        foreach ($grouped as $workUnit => $unitKpis) {
            $total = $unitKpis->count();
            $noTarget = $unitKpis->filter(function ($kpi) {
                return $kpi->target_total === null || $kpi->target_total == 0;
            })->count();
            
            $noAccomplishment = $unitKpis->filter(function ($kpi) {
                return $kpi->accomplishment_total === null || $kpi->accomplishment_total == 0;
            })->count();

            $belowTarget = $unitKpis->filter(function ($kpi) {
                if ($kpi->target_total === null || $kpi->target_total == 0) return false;
                if ($kpi->accomplishment_total === null || $kpi->accomplishment_total == 0) return true;
                return $kpi->accomplishment_total < $kpi->target_total;
            })->count();

            $metTarget = $unitKpis->filter(function ($kpi) {
                if ($kpi->target_total === null || $kpi->target_total == 0) return false;
                if ($kpi->accomplishment_total === null || $kpi->accomplishment_total == 0) return false;
                return abs($kpi->accomplishment_total - $kpi->target_total) < 0.01 
                    || ($kpi->rate_of_accomplishment !== null && $kpi->rate_of_accomplishment >= 100);
            })->count();

            $aboveTarget = $unitKpis->filter(function ($kpi) {
                if ($kpi->target_total === null || $kpi->target_total == 0) return false;
                if ($kpi->accomplishment_total === null || $kpi->accomplishment_total == 0) return false;
                return $kpi->accomplishment_total > $kpi->target_total;
            })->count();

            $summaries[$workUnit] = [
                'work_unit' => $workUnit,
                'total_kpis' => $total,
                'no_target' => $noTarget,
                'no_accomplishment' => $noAccomplishment,
                'below_target' => $belowTarget,
                'met_target' => $metTarget,
                'above_target' => $aboveTarget,
            ];
        }

        // Sort by work unit name
        ksort($summaries);

        return $summaries;
    }
}

