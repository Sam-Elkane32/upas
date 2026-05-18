<?php

namespace App\Http\Controllers;

use App\Models\RepairTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RepairTicketController extends Controller
{
    private function assertMessagingAccess(): void
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin() && ! $user->isAdmin() && ! $user->isPlanningCoordinator() && ! $user->isDeveloper() && ! $user->isDivisionLevelViewOnly()) {
            abort(403, 'Messaging is not available for your account type.');
        }
    }

    public function show(RepairTicket $repairTicket)
    {
        $this->assertMessagingAccess();

        $user = Auth::user();
        $repairTicket->load(['report.user', 'assignedTo']);

        $report = $repairTicket->report;
        if (! $report) {
            abort(404);
        }

        if (! $user->isDeveloper() && $report->user_id !== $user->id) {
            abort(403, 'You cannot view this repair ticket.');
        }

        return view('messaging.repair-tickets.show', [
            'repairTicket' => $repairTicket,
        ]);
    }

    public function update(Request $request, RepairTicket $repairTicket)
    {
        $this->assertMessagingAccess();

        if (! Auth::user()->isDeveloper()) {
            abort(403, 'Only developers can update repair tickets.');
        }

        $repairTicket->load('report');

        $validated = $request->validate([
            'status'         => ['required', 'string', Rule::in(RepairTicket::STATUSES)],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
        ]);

        // Priority is set by staff on the Report Form; assignment is implicit (active developer saving the ticket).
        $repairTicket->update([
            'status'                => $validated['status'],
            'internal_notes'        => $validated['internal_notes'] ?? null,
            'assigned_to_user_id'     => Auth::id(),
        ]);

        $repairTicket->load(['report.user', 'assignedTo']);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'ticket' => [
                'id'     => $repairTicket->id,
                'status' => $repairTicket->status,
            ]]);
        }

        return redirect()
            ->route('messaging.repair-tickets.show', [
                'repairTicket' => $repairTicket,
                'audience'       => 'developers',
            ])
            ->with('success', 'Repair ticket updated.');
    }
}
