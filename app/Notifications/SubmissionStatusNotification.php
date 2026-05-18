<?php

namespace App\Notifications;

use App\Models\CampusSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $submission;
    protected $status;
    protected $adminRemarks;

    /**
     * Create a new notification instance.
     */
    public function __construct(CampusSubmission $submission, string $status, string $adminRemarks = null)
    {
        $this->submission = $submission;
        $this->status = $status;
        $this->adminRemarks = $adminRemarks;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = match($this->status) {
            'approved' => 'Submission Approved',
            'returned' => 'Submission Returned for Revision',
            default => 'Submission Status Update',
        };

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your submission has been ' . $this->status . '.')
            ->line('Strategic Goal: ' . $this->submission->strategic_goal)
            ->line('KRA: ' . $this->submission->kra)
            ->line('KPI: ' . $this->submission->kpi);

        if ($this->adminRemarks) {
            $message->line('Admin Remarks: ' . $this->adminRemarks);
        }

        if ($this->status === 'returned') {
            $message->action('Edit Submission', route('campus-submissions.edit', $this->submission));
        } else {
            $message->action('View Submission', route('campus-submissions.my-submissions'));
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'status' => $this->status,
            'strategic_goal' => $this->submission->strategic_goal,
            'kra' => $this->submission->kra,
            'kpi' => $this->submission->kpi,
            'admin_remarks' => $this->adminRemarks,
            'message' => match($this->status) {
                'approved' => 'Your submission has been approved.',
                'returned' => 'Your submission has been returned for revision.',
                default => 'Your submission status has been updated.',
            },
        ];
    }
}