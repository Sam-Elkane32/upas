<?php

namespace App\Http\Controllers\ViewOnly;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\Template;
use App\Models\Form;
use App\Models\Campus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class ViewOnlyDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:view_only']);
    }

    /**
     * Display view-only dashboard with read-only statistics
     */
    public function index()
    {
        $user = Auth::user();

        $userCampusCode = (string) ($user->campus_code ?? '');
        $userCampusName = trim((string) (optional($user->campusInfo)->name ?? $user->campus ?? ''));
        $singleCampusScope = $user->restrictsViewOnlyToSingleCampus();

        // Get approved submissions only (view-only users can only see approved).
        $approvedSubmissionsQuery = Submission::query()
            ->where('status', 'Approved')
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            });

        // Campus view-only: own campus only. Division / unset: university-wide approved data.
        if ($singleCampusScope) {
            $this->applyCampusFilter($approvedSubmissionsQuery, $userCampusCode, $userCampusName);
        }

        // Statistics (read-only, approved only).
        $totalApprovedSubmissions = (clone $approvedSubmissionsQuery)->count();
        $publishedTemplatesQuery = Template::where('status', 'Published');
        $publishedFormsQuery = Form::query()->readableByViewOnly();

        if ($singleCampusScope) {
            $publishedTemplatesQuery->where(function ($q) use ($userCampusCode) {
                $q->whereNull('campus_code')->orWhere('campus_code', '')->orWhere('campus_code', $userCampusCode);
            });
            $publishedFormsQuery->where('campus_code', $userCampusCode);
        }

        $stats = [
            'total_approved_submissions' => $totalApprovedSubmissions,
            'total_templates' => $publishedTemplatesQuery->count(),
            'total_forms' => $publishedFormsQuery->count(),
            'total_campuses' => $singleCampusScope ? 1 : Campus::where('is_active', true)->count(),
        ];

        // Additional campus accomplishment details.
        $quarterBreakdown = ['Q1' => 0, 'Q2' => 0, 'Q3' => 0, 'Q4' => 0];
        $quarterCounts = (clone $approvedSubmissionsQuery)
            ->selectRaw('quarter, COUNT(*) as total')
            ->groupBy('quarter')
            ->pluck('total', 'quarter')
            ->toArray();
        foreach ($quarterCounts as $quarter => $count) {
            $normalizedQuarter = strtoupper(trim((string) $quarter));
            if (isset($quarterBreakdown[$normalizedQuarter])) {
                $quarterBreakdown[$normalizedQuarter] = (int) $count;
            }
        }

        $topStrategicGoals = (clone $approvedSubmissionsQuery)
            ->selectRaw('sg_code, COUNT(*) as total')
            ->groupBy('sg_code')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(function ($row) {
                return [
                    'sg_code' => $row->sg_code ?: 'N/A',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $topKraAreas = (clone $approvedSubmissionsQuery)
            ->selectRaw('kra_title, COUNT(*) as total')
            ->groupBy('kra_title')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(function ($row) {
                return [
                    'kra_title' => $row->kra_title ?: 'N/A',
                    'total' => (int) $row->total,
                ];
            })
            ->values();

        $latestSubmissionAt = (clone $approvedSubmissionsQuery)->max('submitted_at');
        $scopeLabel = $singleCampusScope
            ? ($userCampusName !== '' ? $userCampusName : 'Your campus')
            : 'All campuses';
        $campusDetails = [
            'campus_name' => $scopeLabel,
            'scope_is_university_wide' => !$singleCampusScope,
            'active_quarters' => collect($quarterBreakdown)->filter(fn ($count) => $count > 0)->count(),
            'total_strategic_goals' => (clone $approvedSubmissionsQuery)->distinct('sg_code')->count('sg_code'),
            'total_kra_areas' => (clone $approvedSubmissionsQuery)->distinct('kra_title')->count('kra_title'),
            'latest_submission_at' => $latestSubmissionAt,
            'quarter_breakdown' => $quarterBreakdown,
            'top_strategic_goals' => $topStrategicGoals,
            'top_kra_areas' => $topKraAreas,
        ];

        // Recent approved submissions (last 5).
        $recentSubmissions = (clone $approvedSubmissionsQuery)
            ->with(['template', 'submitter'])
            ->orderBy('submitted_at', 'desc')
            ->limit(5)
            ->get();

        // Campus performance: division / university-wide view-only sees every active campus.
        $campusPerformance = [];
        if (!$singleCampusScope) {
            $campuses = Campus::where('is_active', true)->get();
            foreach ($campuses as $campus) {
                $campusSubsQuery = Submission::query()
                    ->where('status', 'Approved')
                    ->where(function($q) {
                        $q->where('is_draft', false)->orWhereNull('is_draft');
                    });
                $this->applyCampusFilter($campusSubsQuery, (string) $campus->code, (string) $campus->name);

                $campusPerformance[] = [
                    'name' => $campus->name,
                    'submissions_count' => $campusSubsQuery->count(),
                ];
            }
        } else {
            $campusPerformance[] = [
                'name' => $userCampusName !== '' ? $userCampusName : 'Your campus',
                'submissions_count' => $totalApprovedSubmissions,
            ];
        }

        return view('view-only.dashboard', compact('stats', 'recentSubmissions', 'campusPerformance', 'campusDetails'));
    }

    protected function applyCampusFilter(Builder $query, string $campusCode, string $campusName): Builder
    {
        $campusCode = strtoupper(trim($campusCode));
        $campusName = trim($campusName);

        return $query->where(function ($q) use ($campusCode, $campusName) {
            $added = false;
            if ($campusCode !== '') {
                $q->where('campus_code', $campusCode);
                $added = true;
            }

            if ($campusName !== '') {
                if ($added) {
                    $q->orWhere('campus', $campusName);
                } else {
                    $q->where('campus', $campusName);
                }
            }
        });
    }
}
