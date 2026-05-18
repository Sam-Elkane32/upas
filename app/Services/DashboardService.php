<?php

namespace App\Services;

use App\Models\User;
use App\Models\Submission;
use App\Models\Template;
use App\Models\Form;
use App\Models\Campus;
use App\Models\Approval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get dashboard data for a specific user based on their role
     * 
     * @param User $user
     * @return array
     */
    public function getFor(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return $this->getSuperAdminData();
        } elseif ($user->isAdmin()) {
            return $this->getCampusAdminData($user);
        } else {
            return $this->getCampusUserData($user);
        }
    }

    /**
     * Get Super Admin dashboard data
     * 
     * @return array
     */
    protected function getSuperAdminData(): array
    {
        $cacheKey = 'dashboard_super_admin_' . now()->format('Y-m-d-H-i');
        
        return Cache::remember($cacheKey, 300, function () {
            $totalCampuses = Campus::where('is_active', true)->count();
            $formsForKraKpi = Form::query()->get();
            $totalKras = $formsForKraKpi->sum(fn (Form $form) => $form->getKraCount());
            $totalKpis = $formsForKraKpi->sum(fn (Form $form) => $form->getKpiCount());
            
            // Calculate average accomplishment rate
            $averageAccomplishmentRate = $this->calculateAverageRate(
                Submission::where('status', 'Approved')
                    ->with('approval')
                    ->get()
            );
            
            // Campus performance data
            $campusPerformance = Campus::where('is_active', true)
                ->get()
                ->map(function ($campus) {
                    $submissions = Submission::where('campus', $campus->name)
                        ->where('status', 'Approved')
                        ->with('approval')
                        ->get();
                    
                    $accomplishmentRate = $this->calculateAverageRate($submissions);
                    
                    return (object) [
                        'name' => $campus->name,
                        'code' => $campus->code,
                        'accomplishment_rate' => round($accomplishmentRate, 2),
                        'total_submissions' => $submissions->count()
                    ];
                });
            
            // Recent approved submissions
            $recentSubmissions = Submission::with(['template', 'submitter', 'approval'])
                ->where('status', 'Approved')
                ->orderBy('submitted_at', 'desc')
                ->limit(5)
                ->get();
            
            // Calculate KPI stats from approvals
            $allApprovals = Approval::whereNotNull('rate')->get();
            $kpiStats = [
                'outstanding' => $allApprovals->where('rate', '>=', 100)->count(),
                'very_satisfactory' => $allApprovals->where('rate', '>=', 90)->where('rate', '<', 100)->count(),
                'satisfactory' => $allApprovals->where('rate', '>=', 80)->where('rate', '<', 90)->count(),
                'needs_improvement' => $allApprovals->where('rate', '<', 80)->count(),
            ];
            
            // Additional statistics
            $totalSubmissions = Submission::where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            })->count();
            $pendingReviews = Submission::where('status', 'Pending Review')
                ->where(function($q) {
                    $q->where('is_draft', false)->orWhereNull('is_draft');
                })->count();
            $totalUsers = User::where('is_active', true)->count();
            
            return [
                'totalCampuses' => $totalCampuses,
                'totalKras' => $totalKras,
                'totalKpis' => $totalKpis,
                'averageAccomplishmentRate' => round($averageAccomplishmentRate, 2),
                'campusPerformance' => $campusPerformance,
                'kpiStats' => $kpiStats,
                'recentSubmissions' => $recentSubmissions,
                'totalSubmissions' => $totalSubmissions,
                'pendingReviews' => $pendingReviews,
                'totalUsers' => $totalUsers,
            ];
        });
    }

    /**
     * Get Campus Admin dashboard data
     * 
     * @param User $user
     * @return array
     */
    protected function getCampusAdminData(User $user): array
    {
        $campusCode = $user->campus_code;
        $campusName = optional($user->campusInfo)->name ?? $user->campus ?? Campus::where('code', $campusCode)->value('name') ?: '';
        $cacheKey = "dashboard_campus_admin_{$campusCode}_" . now()->format('Y-m-d-H-i');
        
        return Cache::remember($cacheKey, 120, function () use ($campusCode, $campusName) {
            $totalTemplates = Template::where('campus_code', $campusCode)
                ->where('status', 'Published')
                ->count();
            
            $totalForms = Form::where('campus_code', $campusCode)
                ->where('status', 'Published')
                ->count();
            
            $pendingReviews = Submission::forCampusNameOrCode($campusName, $campusCode)
                ->where('status', 'Pending Review')
                ->count();
            
            $approvedSubmissions = Submission::forCampusNameOrCode($campusName, $campusCode)
                ->where('status', 'Approved')
                ->count();
            
            // Calculate campus accomplishment rate
            $campusAccomplishmentRate = $this->calculateAverageRate(
                Submission::forCampusNameOrCode($campusName, $campusCode)
                    ->where('status', 'Approved')
                    ->with('approval')
                    ->get()
            );
            
            // Pending submissions for review
            $pendingSubmissions = Submission::with(['template', 'submitter'])
                ->forCampusNameOrCode($campusName, $campusCode)
                ->where('status', 'Pending Review')
                ->orderBy('submitted_at', 'asc')
                ->limit(5)
                ->get();
            
            // Quarterly performance
            $quarterlyPerformance = [];
            $quarters = ['1st Q', '2nd Q', '3rd Q', '4th Q'];
            foreach ($quarters as $quarter) {
                $subs = Submission::forCampusNameOrCode($campusName, $campusCode)
                    ->where('quarter', $quarter)
                    ->where('status', 'Approved')
                    ->with('approval')
                    ->get();
                
                $rate = $this->calculateAverageRate($subs);
                
                $quarterlyPerformance[$quarter] = [
                    'rate' => round($rate, 2),
                    'count' => $subs->count()
                ];
            }
            
            // Top performing templates
            $topTemplates = Template::where('status', 'Published')
                ->forCampus($campusCode)
                ->get()
                ->map(function ($template) use ($campusName, $campusCode) {
                    $subs = Submission::where('template_code', $template->template_code)
                        ->forCampusNameOrCode($campusName, $campusCode)
                        ->where('status', 'Approved')
                        ->with('approval')
                        ->get();
                    
                    $averageRate = $this->calculateAverageRate($subs);
                    
                    return (object) [
                        'template_code' => $template->template_code,
                        'kpi_title' => $template->kpi_title,
                        'average_rate' => round($averageRate, 2),
                        'submission_count' => $subs->count()
                    ];
                })
                ->filter(function ($item) {
                    return $item->submission_count > 0;
                })
                ->sortByDesc('average_rate')
                ->take(5);
            
            return [
                'totalTemplates' => $totalTemplates,
                'totalForms' => $totalForms,
                'pendingReviews' => $pendingReviews,
                'approvedSubmissions' => $approvedSubmissions,
                'campusAccomplishmentRate' => round($campusAccomplishmentRate, 2),
                'pendingSubmissions' => $pendingSubmissions,
                'quarterlyPerformance' => $quarterlyPerformance,
                'topTemplates' => $topTemplates,
            ];
        });
    }

    /**
     * Get Campus User dashboard data
     * 
     * @param User $user
     * @return array
     */
    protected function getCampusUserData(User $user): array
    {
        $userId = $user->id;
        $cacheKey = "dashboard_campus_user_{$userId}_" . now()->format('Y-m-d-H-i');
        
        return Cache::remember($cacheKey, 120, function () use ($userId) {
            $submissions = Submission::where('submitted_by', $userId)->get();
            $approvedSubs = Submission::where('submitted_by', $userId)
                ->where('status', 'Approved')
                ->with('approval')
                ->get();
            
            return [
                'totalSubmissions' => $submissions->count(),
                'pendingSubmissions' => $submissions->where('status', 'Pending Review')->count(),
                'approvedSubmissions' => $approvedSubs->count(),
                'returnedSubmissions' => $submissions->where('status', 'Returned')->count(),
                'draftSubmissions' => $submissions->where('status', 'Unpublished')->count(),
                'averageRate' => round($this->calculateAverageRate($approvedSubs), 2),
                'recentSubmissions' => Submission::where('submitted_by', $userId)
                    ->with(['template'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(),
            ];
        });
    }

    /**
     * Calculate average accomplishment rate from submissions
     * 
     * @param \Illuminate\Database\Eloquent\Collection $submissions
     * @return float
     */
    protected function calculateAverageRate($submissions): float
    {
        if ($submissions->count() === 0) {
            return 0;
        }
        
        $totalRate = 0;
        $count = 0;
        
        foreach ($submissions as $submission) {
            if (!$submission->approval) {
                continue;
            }

            // Approval stores the overall rate in the `rate` column
            $rate = $submission->approval->rate;

            // Fallback for any legacy data that might still use the old column name
            if ($rate === null && isset($submission->approval->rate_of_accomplishment)) {
                $rate = $submission->approval->rate_of_accomplishment;
            }

            if ($rate !== null) {
                $totalRate += (float) $rate;
                $count++;
            }
        }
        
        return $count > 0 ? ($totalRate / $count) : 0;
    }

    /**
     * Invalidate dashboard cache for a user
     * 
     * @param User $user
     * @return void
     */
    public function invalidateCache(User $user): void
    {
        if ($user->isSuperAdmin()) {
            Cache::forget('dashboard_super_admin_' . now()->format('Y-m-d-H-i'));
        } elseif ($user->isAdmin()) {
            $campusCode = $user->campus_code;
            Cache::forget("dashboard_campus_admin_{$campusCode}_" . now()->format('Y-m-d-H-i'));
        } else {
            $userId = $user->id;
            Cache::forget("dashboard_campus_user_{$userId}_" . now()->format('Y-m-d-H-i'));
        }
    }
}

