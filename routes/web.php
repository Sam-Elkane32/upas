<?php

use App\Http\Controllers\ProfileController;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccomplishmentPlanController;
use App\Http\Controllers\StrategicGoalController;
use App\Http\Controllers\QuarterlyReportController;
use App\Http\Controllers\KPIController;
use App\Http\Controllers\KRAController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/health/database', function () {
    $started = hrtime(true);

    try {
        \Illuminate\Support\Facades\DB::select('select 1');
        $driver = config('database.default');

        return response()->json([
            'database' => 'ok',
            'driver' => $driver,
            'host' => config("database.connections.{$driver}.host"),
            'ms' => round((hrtime(true) - $started) / 1e6, 1),
        ]);
    } catch (\Throwable $e) {
        $driver = config('database.default');

        return response()->json([
            'database' => 'error',
            'driver' => $driver,
            'host' => config("database.connections.{$driver}.host"),
            'message' => $e->getMessage(),
        ], 503);
    }
});

// Dashboard Routes
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard/data', [DashboardController::class, 'getData'])->middleware(['auth'])->name('dashboard.data');

// Session Keepalive (for CSRF token refresh)
Route::post('/session/keepalive', [App\Http\Controllers\SessionController::class, 'keepAlive'])->middleware(['auth'])->name('session.keepalive');
Route::post('/session/clear-welcome-popup', [App\Http\Controllers\SessionController::class, 'clearWelcomePopup'])->middleware(['auth'])->name('session.clear-welcome-popup');

// Accomplishment Routes
Route::middleware('auth')->group(function () {
    // Developer (beta support): profile only under /developer — messaging at /messaging
    Route::middleware(['verified', 'role:developer'])->prefix('developer')->name('developer.')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    Route::resource('accomplishments', AccomplishmentPlanController::class);
    
    // QAR System Routes
    Route::resource('strategic-goals', StrategicGoalController::class);
    Route::resource('quarterly-reports', QuarterlyReportController::class);
    Route::resource('kpis', KPIController::class);
    Route::resource('kras', KRAController::class);
    
    // Profile Routes (redirect to role-specific routes)
    Route::get('/profile', function() {
        $user = Auth::user();
        if ($user->isDeveloper()) {
            return redirect()->route('developer.profile.edit');
        }
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.profile.edit');
        } elseif ($user->isAdmin()) {
            return redirect()->route('campus-admin.profile.edit');
        } elseif ($user->hasRole('creator_editor')) {
            return redirect()->route('campus-user.profile.edit');
        }
        return app(ProfileController::class)->edit(request());
    })->name('profile.edit');
    Route::patch('/profile', function (ProfileUpdateRequest $request) {
        $user = $request->user();
        if ($user->isDeveloper()) {
            return app(ProfileController::class)->update($request);
        }
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.profile.edit');
        } elseif ($user->isAdmin()) {
            return redirect()->route('campus-admin.profile.edit');
        } elseif ($user->hasRole('creator_editor')) {
            return redirect()->route('campus-user.profile.edit');
        }

        return app(ProfileController::class)->update($request);
    })->name('profile.update');
    Route::delete('/profile', function() {
        $user = Auth::user();
        if ($user->isDeveloper()) {
            return app(ProfileController::class)->destroy(request());
        }
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.profile.edit');
        } elseif ($user->isAdmin()) {
            return redirect()->route('campus-admin.profile.edit');
        } elseif ($user->hasRole('creator_editor')) {
            return redirect()->route('campus-user.profile.edit');
        }
        return app(ProfileController::class)->destroy(request());
    })->name('profile.destroy');
    
    // Forms Routes
    Route::resource('forms', FormController::class);
    
    // Approvals Routes
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/users/{user}/approve', [ApprovalController::class, 'approveUser'])->name('approvals.approve-user');
    Route::post('/approvals/users/{user}/reject', [ApprovalController::class, 'rejectUser'])->name('approvals.reject-user');
    Route::post('/approvals/forms/{formId}/approve', [ApprovalController::class, 'approveForm'])->name('approvals.approve-form');
    Route::post('/approvals/forms/{formId}/reject', [ApprovalController::class, 'rejectForm'])->name('approvals.reject-form');
    
    // Reports Routes (Super Admin only - uses /reports)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    Route::post('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    
    // Settings Routes (redirect Super Admin to super-admin.settings)
    Route::get('/settings', function() {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.settings.index');
        }
        abort(403, 'Only Super Admin can access settings.');
    })->name('settings.index');
    Route::post('/settings', function() {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.settings.index');
        }
        abort(403, 'Only Super Admin can access settings.');
    })->name('settings.update');
    Route::get('/settings/campus', function() {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.settings.campus');
        }
        abort(403, 'Only Super Admin can access settings.');
    })->name('settings.campus');
    Route::post('/settings/campus/{campus}', function() {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.settings.campus');
        }
        abort(403, 'Only Super Admin can access settings.');
    })->name('settings.update-campus');
    
    // Campus User Routes - Complete Module
    Route::prefix('campus-user')->name('campus-user.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\CampusUserController::class, 'dashboard'])->name('dashboard');
        Route::get('/create-submission', [App\Http\Controllers\CampusUserController::class, 'createSubmission'])->name('create-submission');
        Route::post('/create-submission', [App\Http\Controllers\CampusUserController::class, 'storeSubmission'])->name('store-submission');
        Route::get('/returned-templates', [App\Http\Controllers\CampusUserController::class, 'returnedTemplates'])->name('returned-templates');
        Route::post('/open-template', [App\Http\Controllers\CampusUserController::class, 'openTemplate'])->name('open-template');
        Route::get('/submissions/{submission}', [App\Http\Controllers\CampusUserController::class, 'showSubmission'])->name('show-submission');
        Route::get('/submissions/{submission}/edit', [App\Http\Controllers\CampusUserController::class, 'editSubmission'])->name('edit-submission');
        Route::put('/submissions/{submission}', [App\Http\Controllers\CampusUserController::class, 'updateSubmission'])->name('update-submission');
        Route::delete('/submissions/{submission}', [App\Http\Controllers\CampusUserController::class, 'destroySubmission'])->name('destroy-submission');
        Route::get('/get-template-details', [App\Http\Controllers\CampusUserController::class, 'getTemplateDetails'])->name('get-template-details');
        // Campus User Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        // Save draft route - moved inside campus-user group
        Route::post('/save-draft', [App\Http\Controllers\CampusUserController::class, 'saveDraft'])->name('save-draft');
        Route::get('/export/preview', [App\Http\Controllers\CampusUser\PreviewExportController::class, 'preview'])->name('export.preview');
        Route::get('/reports/export/preview', [App\Http\Controllers\CampusUser\CampusUserReportController::class, 'preview'])->name('reports.export.preview');
        Route::get('/reports/export/tsheet/preview', [App\Http\Controllers\CampusUser\TsheetExportController::class, 'preview'])->name('reports.tsheet.preview');
        Route::get('/reports/export/tsheet/download', [App\Http\Controllers\CampusUser\TsheetExportController::class, 'export'])->name('reports.tsheet.download');
        
        // Profile Routes
        Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

        // Notification Routes (mark as read)
        Route::post('/notifications/{id}/mark-read', function ($id) {
            $notification = Auth::user()->notifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();
            }
            return response()->json(['success' => true]);
        })->name('notifications.mark-read');

        Route::post('/notifications/mark-all-read', function () {
            Auth::user()->unreadNotifications->markAsRead();
            if (request()->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return back()->with('success', 'All notifications marked as read.');
        })->name('notifications.mark-all-read');

        Route::delete('/notifications/{id}', function ($id) {
            $notification = Auth::user()->notifications()->where('id', $id)->first();
            if (! $notification) {
                return response()->json(['success' => false], 404);
            }
            $wasUnread = is_null($notification->read_at);
            $notification->delete();

            return response()->json(['success' => true, 'was_unread' => $wasUnread]);
        })->name('notifications.destroy');
    });
    
    // Planning Coordinator Routes
    // Planning Coordinator routes - redirect to campus-user routes (merged)
    Route::middleware(['auth', 'role:planning_coordinator'])->prefix('planning-coordinator')->name('planning-coordinator.')->group(function () {
        Route::get('/assigned-templates', function() {
            return redirect()->route('campus-user.create-submission');
        })->name('assigned-templates');
        Route::get('/templates/{template}/create-submission', function() {
            return redirect()->route('campus-user.create-submission');
        })->name('create-submission');
        Route::get('/my-submissions', function() {
            return redirect()->route('campus-user.create-submission');
        })->name('my-submissions');
        Route::get('/submissions/{submission}', function($submission) {
            return redirect()->route('campus-user.show-submission', $submission);
        })->name('show-submission');
        Route::get('/submissions/{submission}/edit', function($submission) {
            return redirect()->route('campus-user.edit-submission', $submission);
        })->name('edit-submission');
        Route::get('/dashboard', function() {
            return redirect()->route('campus-user.dashboard');
        })->name('dashboard');
        
        // Profile Routes - redirect to campus-user profile
        Route::get('/profile', function() {
            return redirect()->route('campus-user.profile.edit');
        })->name('profile.edit');
        Route::patch('/profile', function() {
            return redirect()->route('campus-user.profile.update');
        })->name('profile.update');
        Route::delete('/profile', function() {
            return redirect()->route('campus-user.profile.destroy');
        })->name('profile.destroy');
    });
    
    // View-Only User Routes - Read-Only Access
    Route::middleware(['auth', 'role:view_only'])->prefix('view-only')->name('view-only.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\ViewOnly\ViewOnlyDashboardController::class, 'index'])->name('dashboard');
        Route::get('/submissions', [App\Http\Controllers\ViewOnly\ViewOnlySubmissionController::class, 'index'])->name('submissions.index');
        Route::get('/submissions/{id}', [App\Http\Controllers\ViewOnly\ViewOnlySubmissionController::class, 'show'])->name('submissions.show');
        Route::get('/templates', [App\Http\Controllers\ViewOnly\ViewOnlyTemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/{id}/field-data', [App\Http\Controllers\ViewOnly\ViewOnlyTemplateController::class, 'showFieldData'])->name('templates.field-data');
        Route::get('/templates/{id}', [App\Http\Controllers\ViewOnly\ViewOnlyTemplateController::class, 'show'])->name('templates.show');
        Route::get('/summary', [App\Http\Controllers\ViewOnly\ViewOnlySummaryController::class, 'index'])->name('summary.index');
        Route::get('/forms', [App\Http\Controllers\ViewOnly\ViewOnlyFormController::class, 'index'])->name('forms.index');
        Route::post('/export', [App\Http\Controllers\ViewOnly\ViewOnlyExportController::class, 'export'])->name('export');
        
        // Profile Routes (read-only profile access)
        Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    });
    
    // Legacy Campus Submission Routes (for backward compatibility)
    Route::resource('campus-submissions', App\Http\Controllers\CampusSubmissionController::class);
    Route::get('/campus-submissions/{campusSubmission}/download-file', [App\Http\Controllers\CampusSubmissionController::class, 'downloadFile'])->name('campus-submissions.download-file');
    Route::get('/campus-submissions/my-submissions', [App\Http\Controllers\CampusSubmissionController::class, 'mySubmissions'])->name('campus-submissions.my-submissions');
    
    // Campus Admin Routes - Complete Module
Route::middleware(['role:admin'])->prefix('campus-admin')->name('campus-admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\CampusAdminController::class, 'dashboard'])->name('dashboard');
    
    // Forms Management
    Route::get('/create-form', [App\Http\Controllers\CampusAdminController::class, 'createForm'])->name('create-form');
    Route::post('/create-form', [App\Http\Controllers\CampusAdminController::class, 'storeForm'])->name('store-form');
    Route::get('/forms', [App\Http\Controllers\CampusAdminController::class, 'indexForms'])->name('forms');
    Route::get('/forms/{form}', [App\Http\Controllers\CampusAdminController::class, 'showForm'])->name('show-form');
    Route::get('/forms/{form}/edit', [App\Http\Controllers\CampusAdminController::class, 'editForm'])->name('edit-form');
    Route::put('/forms/{form}', [App\Http\Controllers\CampusAdminController::class, 'updateForm'])->name('update-form');
    Route::delete('/forms/{form}', [App\Http\Controllers\CampusAdminController::class, 'destroyForm'])->name('destroy-form');
    Route::post('/forms/{form}/toggle-status', [App\Http\Controllers\CampusAdminController::class, 'toggleFormStatus'])->name('toggle-form-status');
    
    // Templates Management
    Route::get('/templates', [App\Http\Controllers\CampusAdminController::class, 'indexTemplates'])->name('templates');
    Route::get('/create-template', [App\Http\Controllers\CampusAdminController::class, 'createTemplate'])->name('create-template');
    Route::post('/create-template', [App\Http\Controllers\CampusAdminController::class, 'storeTemplate'])->name('store-template');
    Route::get('/templates/{template}', [App\Http\Controllers\CampusAdminController::class, 'showTemplate'])->name('show-template');
    Route::get('/templates/{template}/edit', [App\Http\Controllers\CampusAdminController::class, 'editTemplate'])->name('edit-template');
    Route::put('/templates/{template}', [App\Http\Controllers\CampusAdminController::class, 'updateTemplate'])->name('update-template');
    Route::delete('/templates/{template}', [App\Http\Controllers\CampusAdminController::class, 'destroyTemplate'])->name('destroy-template');
    Route::post('/templates/{template}/toggle-status', [App\Http\Controllers\CampusAdminController::class, 'toggleTemplateStatus'])->name('toggle-template-status');
    
    // Approval Workflow - New System
    Route::get('/approvals', [App\Http\Controllers\CampusAdminApprovalController::class, 'index'])->name('approvals.index');
    Route::get('/approvals/all', [App\Http\Controllers\CampusAdminApprovalController::class, 'allSubmissions'])->name('approvals.all');
    Route::get('/approvals/{submission}', [App\Http\Controllers\CampusAdminApprovalController::class, 'show'])->name('approvals.show');
    Route::get('/approvals/{submission}/review', [App\Http\Controllers\CampusAdminApprovalController::class, 'create'])->name('approvals.review');
    Route::post('/approvals/{submission}', [App\Http\Controllers\CampusAdminApprovalController::class, 'store'])->name('approvals.store');
    Route::get('/approvals/{submission}/edit', [App\Http\Controllers\CampusAdminApprovalController::class, 'edit'])->name('approvals.edit');
    Route::put('/approvals/{submission}', [App\Http\Controllers\CampusAdminApprovalController::class, 'update'])->name('approvals.update');
    Route::post('/approvals/calculate-metrics', [App\Http\Controllers\CampusAdminApprovalController::class, 'calculateMetrics'])->name('approvals.calculate-metrics');
    Route::get('/approvals/statistics', [App\Http\Controllers\CampusAdminApprovalController::class, 'statistics'])->name('approvals.statistics');
    
    // Legacy Approvals Management (for backward compatibility)
    Route::get('/legacy-approvals', [App\Http\Controllers\CampusAdminController::class, 'indexApprovals'])->name('legacy-approvals');
    Route::get('/legacy-approvals/{approval}', [App\Http\Controllers\CampusAdminController::class, 'showApproval'])->name('legacy-show-approval');
    Route::put('/legacy-approvals/{approval}', [App\Http\Controllers\CampusAdminController::class, 'updateApproval'])->name('legacy-update-approval');
    
    // Campus Admin Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::post('/reports/export/vpass', [App\Http\Controllers\CampusAdmin\VpassExportController::class, 'exportVpassFormat'])->name('reports.export.vpass');
    Route::post('/reports/export/vpass/excel', [App\Http\Controllers\CampusAdmin\VpassExportController::class, 'exportVpassExcel'])->name('reports.export.vpass.excel');
    Route::get('/reports/export/vpass/preview', [App\Http\Controllers\CampusAdmin\VpassExportController::class, 'preview'])->name('reports.export.vpass.preview');
    Route::get('/reports/export/pdf/preview', [App\Http\Controllers\CampusAdmin\CampusAdminExportController::class, 'previewPdf'])->name('reports.export.pdf.preview');
    Route::get('/reports/export/excel/preview', [App\Http\Controllers\CampusAdmin\CampusAdminExportController::class, 'previewExcel'])->name('reports.export.excel.preview');
    
    // Users Management
    Route::get('/users', [App\Http\Controllers\UserController::class, 'index'])->name('users');
    Route::get('/users/create', [App\Http\Controllers\UserController::class, 'create'])->name('users.create');
    Route::post('/users', [App\Http\Controllers\UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [App\Http\Controllers\UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [App\Http\Controllers\UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');
    
    // Profile Routes
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
});
    
    // Super Admin Routes
    Route::prefix('super-admin')->name('super-admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', function() {
            $user = Auth::user();
            if (!$user->isSuperAdmin()) {
                abort(403, 'Only Super Admin can access this route.');
            }
            $dashboardService = app(\App\Services\DashboardService::class);
            try {
                $data = $dashboardService->getFor($user);
                return view('dashboard.super-admin', $data);
            } catch (\Exception $e) {
                \Log::error('Dashboard Error: ' . $e->getMessage());
                $defaultData = [
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
                    'error' => 'Unable to load dashboard data. Please try again later.',
                ];
                return view('dashboard.super-admin', $defaultData);
            }
        })->name('dashboard');
        Route::get('/dashboard/data', [App\Http\Controllers\DashboardController::class, 'getData'])->name('dashboard.data');
        
        // Users Management
        Route::get('/users', [App\Http\Controllers\UserController::class, 'index'])->name('users');
        Route::get('/users/create', [App\Http\Controllers\UserController::class, 'create'])->name('users.create');
        Route::post('/users', [App\Http\Controllers\UserController::class, 'store'])->name('users.store');
        
        // Custom Positions Management (Super Admin only)
        Route::post('/positions', [App\Http\Controllers\UserController::class, 'storePosition'])->name('positions.store');
        Route::get('/users/{user}', [App\Http\Controllers\UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [App\Http\Controllers\UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');
        
        // Profile
        Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
        
        // Settings
        Route::get('/settings', [App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
        Route::get('/settings/campus', [App\Http\Controllers\SettingController::class, 'campusSettings'])->name('settings.campus');
        Route::post('/settings/campus/{campus}', [App\Http\Controllers\SettingController::class, 'updateCampus'])->name('settings.update-campus');
        
        // Reports & Analytics — separate pages
        Route::get('/reports/overview', [App\Http\Controllers\SuperAdminController::class, 'overviewDashboard'])->name('reports.overview');
        Route::get('/reports/qa-coordinator', [App\Http\Controllers\SuperAdminController::class, 'qaCoordinatorReports'])->name('reports.qa-coordinator');
        Route::get('/reports/planning-coordinator', [App\Http\Controllers\SuperAdminController::class, 'planningCoordinatorReports'])->name('reports.planning-coordinator');
        Route::get('/reports/summary', [App\Http\Controllers\SuperAdminController::class, 'summaryAccomplishments'])->name('reports.summary');
        // Legacy: redirect old consolidated-reports URL to Overview
        Route::get('/consolidated-reports', function () {
            return redirect()->route('super-admin.reports.overview', request()->query());
        })->name('consolidated-reports');
        Route::delete('/submissions/{submission}', [App\Http\Controllers\SuperAdminController::class, 'deleteSubmission'])->name('submissions.delete');
        Route::get('/export-excel', [App\Http\Controllers\SuperAdminController::class, 'exportExcel'])->name('export-excel');
        Route::get('/export-csv', [App\Http\Controllers\SuperAdminController::class, 'exportCsv'])->name('export-csv');
        Route::get('/export-pdf', [App\Http\Controllers\SuperAdminController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/export-summary-pdf', [App\Http\Controllers\SuperAdminController::class, 'exportSummaryPdf'])->name('export-summary-pdf');
        Route::get('/export/preview', [App\Http\Controllers\SuperAdmin\PreviewExportController::class, 'preview'])->name('export.preview');
        Route::get('/university-stats', [App\Http\Controllers\SuperAdminController::class, 'getUniversityStats'])->name('university-stats');
        Route::get('/campus-performance', [App\Http\Controllers\SuperAdminController::class, 'getCampusPerformance'])->name('campus-performance');
        Route::get('/strategic-goal-performance', [App\Http\Controllers\SuperAdminController::class, 'getStrategicGoalPerformance'])->name('strategic-goal-performance');
        
        // Unified Templates Management
        Route::get('/templates', [App\Http\Controllers\SuperAdminTemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [App\Http\Controllers\SuperAdminTemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [App\Http\Controllers\SuperAdminTemplateController::class, 'store'])->name('templates.store');
        Route::post('/templates/imitate', [App\Http\Controllers\SuperAdminTemplateController::class, 'imitate'])->name('templates.imitate');
        Route::post('/templates/store-form', [App\Http\Controllers\SuperAdminTemplateController::class, 'storeForm'])->name('templates.store-form');
        Route::get('/templates/{template}', [App\Http\Controllers\SuperAdminTemplateController::class, 'show'])->name('templates.show');
        Route::get('/templates/{template}/edit-history', [App\Http\Controllers\SuperAdminTemplateController::class, 'editHistory'])->name('templates.edit-history');
        Route::delete('/templates/{template}/clear-edit-history', [App\Http\Controllers\SuperAdminTemplateController::class, 'clearEditHistory'])->name('templates.clear-edit-history');
        Route::post('/templates/{template}/save-table-data', [App\Http\Controllers\SuperAdminTemplateController::class, 'saveTableData'])->name('templates.save-table-data');
        Route::post('/templates/{template}/summary-rules', [App\Http\Controllers\SuperAdminTemplateController::class, 'updateSummaryRules'])->name('templates.update-summary-rules');
        Route::get('/templates/{template}/edit', [App\Http\Controllers\SuperAdminTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('/templates/{template}', [App\Http\Controllers\SuperAdminTemplateController::class, 'update'])->name('templates.update');
        Route::delete('/templates/{template}', [App\Http\Controllers\SuperAdminTemplateController::class, 'destroy'])->name('templates.destroy');
        Route::post('/templates/{template}/toggle-status', [App\Http\Controllers\SuperAdminTemplateController::class, 'toggleStatus'])->name('templates.toggle-status');
        Route::post('/templates/{template}/clone', [App\Http\Controllers\SuperAdminTemplateController::class, 'clone'])->name('templates.clone');
        Route::post('/templates/{template}/assign', [App\Http\Controllers\SuperAdminTemplateController::class, 'assignTemplate'])->name('templates.assign');

        // Template Lock / Unlock
        Route::post('/templates/{template}/lock',   [App\Http\Controllers\SuperAdminTemplateController::class, 'lockTemplate'])->name('templates.lock');
        Route::post('/templates/{template}/unlock', [App\Http\Controllers\SuperAdminTemplateController::class, 'unlockTemplate'])->name('templates.unlock');

        // Deadline Notifications
        Route::get('/templates/{template}/notify',  [App\Http\Controllers\SuperAdminTemplateController::class, 'notifyForm'])->name('templates.notify-form');
        Route::post('/templates/{template}/notify', [App\Http\Controllers\SuperAdminTemplateController::class, 'sendNotification'])->name('templates.send-notification');
        
        // Form Management Routes
        Route::post('/forms/{form}/toggle-status', [App\Http\Controllers\FormController::class, 'toggleStatus'])->name('forms.toggle-status');
        Route::delete('/forms/{form}', [App\Http\Controllers\FormController::class, 'destroy'])->name('forms.destroy');

        // Form Lock / Unlock (locks/unlocks all templates in the form)
        Route::post('/forms/{form}/lock',   [App\Http\Controllers\FormController::class, 'lockForm'])->name('forms.lock');
        Route::post('/forms/{form}/unlock', [App\Http\Controllers\FormController::class, 'unlockForm'])->name('forms.unlock');
        
        // NEW: Summary of Accomplishments Routes (additive only)
        Route::get('/summary/preview', [App\Http\Controllers\SuperAdminSummaryController::class, 'preview'])->name('summary.preview');
        Route::get('/summary/export-pdf', [App\Http\Controllers\SuperAdminSummaryController::class, 'exportPdf'])->name('summary.export-pdf');
        Route::get('/summary/export-excel', [App\Http\Controllers\SuperAdminSummaryController::class, 'exportExcel'])->name('summary.export-excel');
        
        // Validated Templates Management (Super Admin only - can edit accomplishment values)
        Route::get('/validated-templates', [App\Http\Controllers\SuperAdminValidatedTemplatesController::class, 'index'])->name('validated-templates.index');
        Route::get('/validated-templates/{submission}', [App\Http\Controllers\SuperAdminValidatedTemplatesController::class, 'show'])->name('validated-templates.show');
        Route::get('/validated-templates/{submission}/edit', [App\Http\Controllers\SuperAdminValidatedTemplatesController::class, 'edit'])->name('validated-templates.edit');
        Route::put('/validated-templates/{submission}', [App\Http\Controllers\SuperAdminValidatedTemplatesController::class, 'update'])->name('validated-templates.update');
        
        // Campus Admin Reports - VPASS Format Export (Same as Campus Admin)
        Route::get('/campus-admin/vpass/preview', [App\Http\Controllers\SuperAdmin\SuperAdminVpassExportController::class, 'preview'])->name('campus-admin.vpass.preview');
        Route::post('/campus-admin/vpass/pdf', [App\Http\Controllers\SuperAdmin\SuperAdminVpassExportController::class, 'exportPdf'])->name('campus-admin.vpass.pdf');
        Route::post('/campus-admin/vpass/excel', [App\Http\Controllers\SuperAdmin\SuperAdminVpassExportController::class, 'exportExcel'])->name('campus-admin.vpass.excel');
        
        // Campus User Reports - Standard & TSHEET Format Export (Same as Campus User)
        Route::get('/campus-user/export/preview', [App\Http\Controllers\SuperAdmin\SuperAdminCampusUserExportController::class, 'preview'])->name('campus-user.export.preview');
        Route::post('/campus-user/export/pdf', [App\Http\Controllers\SuperAdmin\SuperAdminCampusUserExportController::class, 'exportPdf'])->name('campus-user.export.pdf');
        Route::get('/campus-user/tsheet/preview', [App\Http\Controllers\SuperAdmin\SuperAdminTsheetExportController::class, 'preview'])->name('campus-user.tsheet.preview');
        Route::post('/campus-user/tsheet/download', [App\Http\Controllers\SuperAdmin\SuperAdminTsheetExportController::class, 'export'])->name('campus-user.tsheet.download');
    });
});

// ─── Internal Messaging System ────────────────────────────────────────────────
// Accessible to: super_admin, admin (QA Coordinator), planning_coordinator, developer
// Role enforcement is handled inside MessagingController::assertMessagingAccess()
Route::middleware(['auth'])->prefix('messaging')->name('messaging.')->group(function () {
    // Main page
    Route::get('/',                                [\App\Http\Controllers\MessagingController::class, 'index'])           ->name('index');
    Route::get('/developer-tickets',               [\App\Http\Controllers\MessagingController::class, 'developerTickets'])->name('developer-tickets.index');
    Route::get('/developer-notifications',         [\App\Http\Controllers\MessagingController::class, 'developerNotifications'])->name('developer-notifications.index');
    Route::get('/developer-notifications/{id}/open', [\App\Http\Controllers\MessagingController::class, 'developerNotificationOpen'])->name('developer-notifications.open');

    // Conversations
    Route::post('/conversations',                  [\App\Http\Controllers\MessagingController::class, 'startConversation'])->name('conversations.start');
    Route::delete('/conversations/{id}',           [\App\Http\Controllers\MessagingController::class, 'deleteConversation'])->name('conversations.delete');
    Route::post('/conversations/{id}/messages',    [\App\Http\Controllers\MessagingController::class, 'sendMessage'])     ->name('conversations.messages.send');
    Route::get('/conversations/{id}/messages',     [\App\Http\Controllers\MessagingController::class, 'getMessages'])     ->name('conversations.messages.get');
    Route::post('/conversations/{id}/read',        [\App\Http\Controllers\MessagingController::class, 'markAsRead'])      ->name('conversations.read');

    // Secure attachment download / inline image (same host as the app — works on LAN IP; not direct /storage URL)
    Route::get('/messages/{message}/attachment/{index}', [\App\Http\Controllers\MessagingController::class, 'downloadAttachment'])
        ->whereNumber('index')
        ->name('messages.attachment');

    // Message actions
    Route::patch('/messages/{id}',                 [\App\Http\Controllers\MessagingController::class, 'editMessage'])     ->name('messages.edit');
    Route::delete('/messages/{id}',                [\App\Http\Controllers\MessagingController::class, 'deleteMessage'])   ->name('messages.delete');
    Route::post('/messages/{id}/forward',          [\App\Http\Controllers\MessagingController::class, 'forwardMessage'])  ->name('messages.forward');
    Route::post('/messages/{id}/pin',              [\App\Http\Controllers\MessagingController::class, 'pinMessage'])      ->name('messages.pin');
    Route::delete('/messages/{id}/pin',            [\App\Http\Controllers\MessagingController::class, 'unpinMessage'])    ->name('messages.unpin');

    // Utilities
    Route::get('/unread-count',                    [\App\Http\Controllers\MessagingController::class, 'unreadCount'])     ->name('unread-count');
    Route::get('/users',                           [\App\Http\Controllers\MessagingController::class, 'messageableUsers'])->name('users');

    // Developer support: concern reports → repair tickets (no message thread)
    Route::post('/support-reports',                [\App\Http\Controllers\SupportReportController::class, 'store'])->name('support-reports.store');
    Route::get('/support-reports/{supportReport}/edit', [\App\Http\Controllers\SupportReportController::class, 'edit'])->name('support-reports.edit');
    Route::patch('/support-reports/{supportReport}', [\App\Http\Controllers\SupportReportController::class, 'update'])->name('support-reports.update');
    Route::get('/support-reports/{supportReport}/attachments/{index}', [\App\Http\Controllers\SupportReportController::class, 'downloadAttachment'])
        ->whereNumber('index')
        ->name('support-reports.attachments.show');
    Route::get('/repair-tickets/{repairTicket}',  [\App\Http\Controllers\RepairTicketController::class, 'show'])->name('repair-tickets.show');
    Route::patch('/repair-tickets/{repairTicket}', [\App\Http\Controllers\RepairTicketController::class, 'update'])->name('repair-tickets.update');
});

require __DIR__.'/auth.php';
