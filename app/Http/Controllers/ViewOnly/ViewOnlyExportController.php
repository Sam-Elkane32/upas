<?php

namespace App\Http\Controllers\ViewOnly;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ViewOnlyExportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:view_only']);
    }

    public function export(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'format' => 'required|in:pdf,excel,csv',
        ]);

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'quarter' => $request->get('quarter'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
            'kpi_title' => $request->get('kpi_title'),
            'template_code' => $request->get('template_code'),
            'form_title' => $request->get('form_title'),
            'campus' => $request->get('campus'),
        ];

        $query = Submission::query()
            ->where('status', 'Approved')
            ->where(function ($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            });

        if ($user->restrictsViewOnlyToSingleCampus()) {
            $campusName = optional($user->campusInfo)->name ?? $user->campus ?? '';
            $query->where('campus', $campusName);
        } elseif ($request->filled('campus')) {
            $query->where('campus', $request->get('campus'));
        }

        $query = $this->applyFilters($query, $filters);

        $submissions = $query->with(['template', 'submitter', 'approval'])
            ->orderBy('campus', 'asc')
            ->orderBy('template_code', 'asc')
            ->orderBy('sg_code', 'asc')
            ->orderBy('kra_title', 'asc')
            ->orderBy('kpi_title', 'asc')
            ->orderBy('submitted_at', 'desc')
            ->get();

        $format = $request->get('format');
        $filename = 'uaps-approved-accomplishments-' . now()->format('Y-m-d-His') . '.' . $format;

        try {
            if ($format === 'csv') {
                return $this->exportCsv($submissions, $filename);
            }
            if ($format === 'excel') {
                return $this->exportExcel($submissions, $filename);
            }

            return $this->exportPdf($submissions, $filename, $user, $filters);
        } catch (\Exception $e) {
            Log::error('View-only export failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('view-only.summary.index')
                ->with('error', 'Export failed. Please try again or adjust filters.');
        }
    }

    private function exportCsv($submissions, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($submissions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Submission ID', 'Template Code', 'SG Code', 'KRA Title', 'KPI Title',
                'Campus', 'Quarter', 'Status', 'Submitted By', 'Submitted At',
                'Approval Rate', 'Remarks',
            ]);
            foreach ($submissions as $submission) {
                fputcsv($file, [
                    $submission->submission_id ?? $submission->id,
                    $submission->template_code ?? 'N/A',
                    $submission->sg_code ?? 'N/A',
                    $submission->kra_title ?? 'N/A',
                    $submission->kpi_title ?? 'N/A',
                    $submission->campus ?? 'N/A',
                    $submission->quarter ?? 'N/A',
                    $submission->status ?? 'N/A',
                    $submission->submitter->name ?? 'N/A',
                    $submission->submitted_at ? $submission->submitted_at->format('Y-m-d H:i:s') : 'N/A',
                    ($submission->approval && $submission->approval->rate) ? $submission->approval->rate : 'N/A',
                    ($submission->approval && $submission->approval->remarks) ? $submission->approval->remarks : 'N/A',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportExcel($submissions, string $filename)
    {
        return $this->exportCsv($submissions, str_replace('.excel', '.csv', $filename));
    }

    private function exportPdf($submissions, string $filename, $user, array $filters = [])
    {
        $singleCampus = $user->restrictsViewOnlyToSingleCampus();
        $userRole = $singleCampus ? 'campus_admin' : 'super_admin';

        $campusName = null;
        if ($singleCampus) {
            $campusName = optional($user->campusInfo)->name ?? $user->campus ?? '';
        }

        $groupedCampuses = null;
        if (!$singleCampus && $submissions->count() > 0) {
            $grouped = $submissions->groupBy('campus');
            $groupedCampuses = [];
            foreach ($grouped as $campus => $campusSubmissions) {
                $groupedCampuses[$campus] = $campusSubmissions;
            }
        }

        $data = [
            'submissions' => $submissions,
            'user' => $user,
            'userRole' => $userRole,
            'campusName' => $campusName,
            'groupedCampuses' => $groupedCampuses,
            'filters' => $filters,
        ];

        $pdf = Pdf::loadView('reports.pdf-export', $data)
            ->setPaper('legal', 'landscape')
            ->setOption('enable-local-file-access', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        return $pdf->download($filename);
    }

    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['quarter'])) {
            $query->where('quarter', $filters['quarter']);
        }
        if (!empty($filters['form_title'])) {
            $query->where('form_title', $filters['form_title']);
        }
        if (!empty($filters['sg_code'])) {
            $query->where('sg_code', $filters['sg_code']);
        }
        if (!empty($filters['kra_title'])) {
            $query->where('kra_title', $filters['kra_title']);
        }
        if (!empty($filters['kpi_title'])) {
            $query->where('kpi_title', 'like', '%' . $filters['kpi_title'] . '%');
        }
        if (!empty($filters['template_code'])) {
            $query->where('template_code', $filters['template_code']);
        }

        return $query;
    }
}
