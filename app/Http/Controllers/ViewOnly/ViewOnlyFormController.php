<?php

namespace App\Http\Controllers\ViewOnly;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ViewOnlyFormController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:view_only']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Form::query()
            ->readableByViewOnly()
            ->with(['creator', 'campus']);

        if ($user->restrictsViewOnlyToSingleCampus()) {
            $query->where('campus_code', $user->campus_code);
        }

        if ($request->filled('campus_code') && $request->campus_code !== 'ALL') {
            $query->where('campus_code', $request->campus_code);
        }

        if ($request->filled('sg_code')) {
            $query->where('sg_code', $request->sg_code);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->get('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('form_title', 'like', '%' . $q . '%')
                    ->orWhere('template_code', 'like', '%' . $q . '%');
            });
        }

        $sortBy = $request->get('sort_by', 'recent');
        match ($sortBy) {
            'title' => $query->orderBy('form_title'),
            'campus' => $query->orderBy('campus_code')->orderByDesc('updated_at'),
            default => $query->orderByDesc('updated_at'),
        };

        $forms = $query->paginate(20)->withQueryString();

        $campuses = $user->restrictsViewOnlyToSingleCampus()
            ? Campus::where('code', $user->campus_code)->get()
            : Campus::where('is_active', true)->orderBy('name')->get();

        $sgCodes = Form::query()
            ->readableByViewOnly()
            ->when($user->restrictsViewOnlyToSingleCampus(), fn ($q) => $q->where('campus_code', $user->campus_code))
            ->distinct()
            ->pluck('sg_code')
            ->filter()
            ->sort()
            ->values();

        return view('view-only.forms.index', compact('forms', 'campuses', 'sgCodes'));
    }
}
