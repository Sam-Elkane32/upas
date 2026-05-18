<?php

namespace App\Http\Controllers;

use App\Models\RepairTicket;
use App\Models\SupportReport;
use App\Models\User;
use App\Notifications\RepairTicketSubmittedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class SupportReportController extends Controller
{
    private function assertMessagingAccess(): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->isAdmin() && ! $user->isPlanningCoordinator() && ! $user->isDeveloper() && ! $user->isDivisionLevelViewOnly()) {
            abort(403, 'Messaging is not available for your account type.');
        }
    }

    /**
     * @return list<array{path: string, name: string, mime: string, size: int}>
     */
    private function storeUploadedAttachments(Request $request): array
    {
        $files = $request->file('attachments', []);
        if (! is_array($files)) {
            $files = array_filter([$files]);
        }
        $files = array_slice($files, 0, 5);

        $out = [];
        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $path = $file->store('support_reports', 'public');
            $out[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime' => $mime,
                'size' => (int) $file->getSize(),
            ];
        }

        return $out;
    }

    public function store(Request $request)
    {
        $this->assertMessagingAccess();

        if (Auth::user()->isDeveloper()) {
            $msg = 'Developer accounts cannot submit reports. Use a staff account (Planning, QA, CED, etc.) to file a repair ticket.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 403);
            }
            abort(403, $msg);
        }

        $request->validate([
            'report_type' => ['required', 'string', 'max:64', Rule::in(SupportReport::REPORT_TYPES)],
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'priority'    => ['required', 'string', Rule::in(RepairTicket::PRIORITIES)],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']),
            ],
        ]);

        $stored = $this->storeUploadedAttachments($request);

        $priority = $request->input('priority');

        $ticket = DB::transaction(function () use ($request, $stored, $priority) {
            $report = SupportReport::create([
                'user_id'     => Auth::id(),
                'report_type' => $request->input('report_type'),
                'title'       => $request->input('title'),
                'description' => $request->input('description'),
                'attachments' => $stored === [] ? null : $stored,
            ]);

            return RepairTicket::create([
                'report_id'            => $report->id,
                'status'               => RepairTicket::STATUS_OPEN,
                'priority'             => $priority,
                'internal_notes'       => null,
                'assigned_to_user_id'  => null,
            ]);
        });

        $ticket->load('report');

        foreach (
            User::query()
                ->where('role', User::ROLE_DEVELOPER)
                ->where('is_active', true)
                ->cursor() as $developerUser
        ) {
            $developerUser->notify(new RepairTicketSubmittedNotification($ticket));
        }

        $redirectUrl = route('messaging.repair-tickets.show', [
            'repairTicket' => $ticket,
            'audience'       => 'developers',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success'          => true,
                'repair_ticket_id' => $ticket->id,
                'redirect_url'     => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl);
    }

    /**
     * Edit own support report (submitter only; open / in_progress tickets only).
     */
    public function edit(Request $request, SupportReport $supportReport)
    {
        $this->assertMessagingAccess();

        if ($request->query('audience') !== 'developers') {
            return redirect()->route('messaging.support-reports.edit', [
                'supportReport' => $supportReport,
                'audience'      => 'developers',
            ]);
        }

        $this->authorizeSupportReportEdit(Auth::user(), $supportReport);

        $supportReport->load('repairTicket');

        return view('messaging.support-reports.edit', [
            'supportReport' => $supportReport,
            'repairTicket'  => $supportReport->repairTicket,
        ]);
    }

    /**
     * Update own support report and linked ticket priority.
     */
    public function update(Request $request, SupportReport $supportReport)
    {
        $this->assertMessagingAccess();
        $this->authorizeSupportReportEdit(Auth::user(), $supportReport);

        $supportReport->load('repairTicket');
        $repairTicket = $supportReport->repairTicket;
        if (! $repairTicket) {
            abort(404);
        }

        $request->validate([
            'report_type' => ['required', 'string', 'max:64', Rule::in(SupportReport::REPORT_TYPES)],
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'priority'    => ['required', 'string', Rule::in(RepairTicket::PRIORITIES)],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']),
            ],
        ]);

        $existing = $supportReport->attachments;
        if (! is_array($existing)) {
            $existing = [];
        }
        $newFiles = $this->storeUploadedAttachments($request);
        $merged = array_merge($existing, $newFiles);
        if (count($merged) > 5) {
            return back()->withInput()->withErrors([
                'attachments' => 'You can have at most 5 files total. Add fewer new files or remove existing files first.',
            ]);
        }

        DB::transaction(function () use ($request, $supportReport, $repairTicket, $merged) {
            $supportReport->update([
                'report_type' => $request->input('report_type'),
                'title'       => $request->input('title'),
                'description' => $request->input('description'),
                'attachments' => $merged === [] ? null : $merged,
            ]);

            $repairTicket->update([
                'priority' => $request->input('priority'),
            ]);
        });

        return redirect()
            ->route('messaging.repair-tickets.show', [
                'repairTicket' => $repairTicket,
                'audience'     => 'developers',
            ])
            ->with('success', 'Report updated.');
    }

    private function authorizeSupportReportEdit(User $user, SupportReport $supportReport): void
    {
        if ($user->isDeveloper()) {
            abort(403, 'Developer accounts use the ticket panel below to update status.');
        }
        if ($supportReport->user_id !== $user->id) {
            abort(403, 'You can only edit your own reports.');
        }
        $supportReport->loadMissing('repairTicket');
        $ticket = $supportReport->repairTicket;
        if (! $ticket) {
            abort(404);
        }
        if (! in_array($ticket->status, [RepairTicket::STATUS_OPEN, RepairTicket::STATUS_IN_PROGRESS], true)) {
            abort(403, 'This ticket can no longer be edited.');
        }
    }

    /**
     * Download or inline view for a support report attachment (owner or developer).
     */
    public function downloadAttachment(Request $request, SupportReport $supportReport, int $index)
    {
        $this->assertMessagingAccess();

        $user = Auth::user();
        if (! $user->isDeveloper() && $supportReport->user_id !== $user->id) {
            abort(403, 'You cannot access this attachment.');
        }

        $list = $supportReport->attachments;
        if (! is_array($list) || ! array_key_exists($index, $list)) {
            abort(404);
        }

        $att = $list[$index];
        if (! is_array($att)) {
            abort(404);
        }

        $relativePath = (string) ($att['path'] ?? '');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($relativePath)) {
            abort(404);
        }

        $absolutePath = $disk->path($relativePath);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        $mime = (string) ($att['mime'] ?? '');
        if ($mime === '') {
            $mime = @mime_content_type($absolutePath) ?: 'application/octet-stream';
        }

        $downloadName = (string) ($att['name'] ?? basename($relativePath));
        $downloadName = str_replace(["\0", "\r", "\n", '"'], '', $downloadName);

        $forceDownload = $request->boolean('download');

        if (str_starts_with($mime, 'image/') && ! $forceDownload) {
            return response()->file($absolutePath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            ]);
        }

        return response()->download($absolutePath, $downloadName, [
            'Content-Type' => $mime,
        ]);
    }
}
