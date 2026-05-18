<?php

namespace App\Notifications;

use App\Models\RepairTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RepairTicketSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected RepairTicket $repairTicket,
    ) {
        $this->repairTicket->loadMissing('report');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $report = $this->repairTicket->report;
        $title = $report?->title ?? 'Repair ticket';

        return [
            'repair_ticket_id' => $this->repairTicket->id,
            'support_report_id' => $report?->id,
            'title' => $title,
            'report_type' => $report?->report_type,
            'message' => 'A new repair ticket was submitted: '.$title,
            'url' => route('messaging.repair-tickets.show', [
                'repairTicket' => $this->repairTicket->id,
                'audience' => 'developers',
            ]),
        ];
    }
}
