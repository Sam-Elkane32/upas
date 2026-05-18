<?php

namespace App\Http\Controllers\ViewOnly;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SuperAdminTemplateController;
use Illuminate\Http\Request;
use App\Models\Template;
use App\Models\Form;
use App\Models\Campus;
use Illuminate\Support\Facades\Auth;

class ViewOnlyTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:view_only']);
    }

    /**
     * Display all published templates (read-only)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Only show published templates
        $templatesQuery = Template::where('status', 'Published')
            ->with(['creator']);
        
        if ($user->restrictsViewOnlyToSingleCampus()) {
            $templatesQuery->where(function ($q) use ($user) {
                $q->whereNull('campus_code')
                    ->orWhere('campus_code', '')
                    ->orWhere('campus_code', $user->campus_code);
            });
        }
        
        // Apply filters
        if ($request->filled('campus_code') && $request->campus_code !== 'ALL') {
            $templatesQuery->where('campus_code', $request->campus_code);
        }
        
        if ($request->filled('sg_code')) {
            $templatesQuery->where('sg_code', $request->sg_code);
        }
        
        if ($request->filled('template_code')) {
            $templatesQuery->where('template_code', 'like', '%' . $request->template_code . '%');
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'recent');
        switch ($sortBy) {
            case 'oldest':
                $templatesQuery->orderBy('created_at', 'asc');
                break;
            case 'template_code':
                $templatesQuery->orderBy('template_code', 'asc')->orderBy('created_at', 'desc');
                break;
            case 'sg_code':
                $templatesQuery->orderBy('sg_code', 'asc')->orderBy('created_at', 'desc');
                break;
            case 'recent':
            default:
                $templatesQuery->orderBy('created_at', 'desc');
                break;
        }
        
        $templates = $templatesQuery->paginate(20)->withQueryString();
        
        // Get filter options
        $campuses = $user->restrictsViewOnlyToSingleCampus()
            ? Campus::where('code', $user->campus_code)->get()
            : Campus::where('is_active', true)->get();
        
        $sgCodes = Template::where('status', 'Published')
            ->distinct()
            ->pluck('sg_code')
            ->filter()
            ->sort()
            ->values();
        
        $templateCodes = Template::where('status', 'Published')
            ->distinct()
            ->pluck('template_code')
            ->filter()
            ->sort()
            ->values();
        
        return view('view-only.templates.index', compact('templates', 'campuses', 'sgCodes', 'templateCodes'));
    }

    /**
     * Display a single published template (read-only)
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $template = Template::where('status', 'Published')
            ->with(['creator'])
            ->findOrFail($id);
        
        if ($user->restrictsViewOnlyToSingleCampus()) {
            $tc = $template->campus_code;
            if ($tc !== null && trim((string) $tc) !== '' && (string) $tc !== (string) $user->campus_code) {
                abort(403, 'You do not have permission to view this template.');
            }
        }
        
        return view('view-only.templates.show', compact('template'));
    }

    /**
     * Full Super Admin template view (field structure + coordinator data), read-only — division-level view-only only.
     */
    public function showFieldData($id)
    {
        $user = Auth::user();

        if (! $user->isDivisionLevelViewOnly()) {
            abort(403, 'This page is only available for division-level view-only accounts.');
        }

        $template = Template::where('status', 'Published')
            ->with(['creator'])
            ->findOrFail($id);

        $backUrl = $template->form_id
            ? route('forms.show', $template->form_id)
            : route('view-only.templates.index');

        return app(SuperAdminTemplateController::class)->renderShowTemplate($template, [
            'readOnly' => true,
            'viewOnlyBackUrl' => $backUrl,
        ]);
    }
}
