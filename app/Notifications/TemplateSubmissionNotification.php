<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemplateSubmissionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Submission $submission;

    public function __construct(Submission $submission)
    {
        $this->submission = $submission;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $submitterName = $this->submission->submitter?->name ?? 'A Planning Coordinator';
        $templateTitle = $this->submission->form_title ?? $this->submission->template_code ?? 'Template';
        $campus = $this->submission->campus ?? $this->submission->campus_code ?? 'your campus';

        return (new MailMessage)
            ->subject('New Template Submission for Review - ' . $campus)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new template has been submitted for your review.')
            ->line('**Submitter:** ' . $submitterName)
            ->line('**Template:** ' . $templateTitle)
            ->line('**Campus:** ' . $campus)
            ->line('**Quarter:** ' . ($this->submission->quarter ?? 'N/A'))
            ->action('Review Submission', route('campus-admin.approvals.review', $this->submission))
            ->line('Please log in to the system to review and approve this submission.');
    }

    public function toArray(object $notifiable): array
    {
        $submitterName = $this->submission->submitter?->name ?? 'Unknown';
        $templateTitle = $this->submission->form_title ?? $this->submission->template_code ?? 'Template';

        return [
            'type' => 'template_submission',
            'submission_id' => $this->submission->id,
            'template_code' => $this->submission->template_code,
            'form_title' => $templateTitle,
            'submitter_name' => $submitterName,
            'campus' => $this->submission->campus ?? $this->submission->campus_code,
            'quarter' => $this->submission->quarter,
            'message' => $submitterName . ' submitted template "' . $templateTitle . '" for ' . ($this->submission->campus ?? $this->submission->campus_code) . '. Please review.',
            'url' => route('campus-admin.approvals.review', $this->submission),
        ];
    }
}
