<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccomplishmentPlan;
use App\Models\StrategicGoal;
use App\Models\KeyPerformanceIndicator;
use App\Models\QuarterlyReport;
use App\Models\Department;
use App\Models\Campus;
use App\Models\User;
use App\Models\FormSubmission;
use App\Models\Submission;
use App\Models\Template;
use App\Models\Form;
use App\Models\KRA;
use App\Models\KPI;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $user = Auth::user();
        
        try {
            if ($user->isDeveloper()) {
                return redirect()->route('messaging.index');
            }

            $data = $this->dashboardService->getFor($user);
            
            // Role-based dashboard views
        if ($user->isSuperAdmin()) {
                // Redirect Super Admin to their specific dashboard route
                return redirect()->route('super-admin.dashboard');
        } elseif ($user->isAdmin()) {
                // Redirect QA Coordinator to their specific dashboard route
                return redirect()->route('campus-admin.dashboard');
        } elseif ($user->isViewOnly()) {
                // Redirect View-Only Users to their specific dashboard route
                return redirect()->route('view-only.dashboard');
        } else {
            // Redirect Planning Coordinators to their proper dashboard
                return redirect()->route('campus-user.dashboard');
            }
        } catch (\Exception $e) {
            \Log::error('Dashboard Error: ' . $e->getMessage());
            
            // Return with default/empty data on error
            $defaultData = $this->getDefaultDashboardData($user);
            $defaultData['error'] = 'Unable to load dashboard data. Please try again later.';
            
            if ($user->isDeveloper()) {
                return redirect()->route('messaging.index');
            }
            if ($user->isSuperAdmin()) {
                return redirect()->route('super-admin.dashboard');
            } elseif ($user->isAdmin()) {
                return redirect()->route('campus-admin.dashboard');
            } elseif ($user->isViewOnly()) {
                return redirect()->route('view-only.dashboard');
            }
            
            return redirect()->route('campus-user.dashboard');
        }
    }
    
    /**
     * Get default/empty dashboard data for error cases
     */
    protected function getDefaultDashboardData(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return [
                'totalCampuses' => 0,
                'totalKras' => 0,
                'totalKpis' => 0,
                'averageAccomplishmentRate' => 0,
                'campusPerformance' => collect([]),
                'kpiStats' => [
                    'outstanding' => 0,
                    'very_satisfactory' => 0,
                    'satisfactory' => 0,
                    'needs_improvement' => 0,
                ],
                'recentSubmissions' => collect([]),
            ];
        } elseif ($user->isAdmin()) {
            return [
                'totalTemplates' => 0,
                'totalForms' => 0,
                'pendingReviews' => 0,
                'approvedSubmissions' => 0,
                'campusAccomplishmentRate' => 0,
                'pendingSubmissions' => collect([]),
                'quarterlyPerformance' => [],
                'topTemplates' => collect([]),
            ];
        }
        
        return [];
    }
    
    public function getData()
    {
        try {
        $user = Auth::user();
            if ($user->isDeveloper()) {
                return response()->json(['redirect' => route('messaging.index')], 403);
            }
            $data = $this->dashboardService->getFor($user);
        
            // Transform data for JSON response
        if ($user->isSuperAdmin()) {
                return response()->json([
                    'total_campuses' => $data['totalCampuses'],
                    'total_kras' => $data['totalKras'],
                    'total_kpis' => $data['totalKpis'],
                    'average_rate' => $data['averageAccomplishmentRate'],
                ]);
        } elseif ($user->isAdmin()) {
                return response()->json([
                    'total_templates' => $data['totalTemplates'],
                    'total_forms' => $data['totalForms'],
                    'pending_reviews' => $data['pendingReviews'],
                    'approved_submissions' => $data['approvedSubmissions'],
                    'campus_rate' => $data['campusAccomplishmentRate'],
                ]);
        } else {
        return response()->json([
                    'total_submissions' => $data['totalSubmissions'],
                    'pending_submissions' => $data['pendingSubmissions'],
                    'approved_submissions' => $data['approvedSubmissions'],
                    'returned_submissions' => $data['returnedSubmissions'],
                    'draft_submissions' => $data['draftSubmissions'],
                    'average_rate' => $data['averageRate'],
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Get Dashboard Data Error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to load data'], 500);
        }
    }
}