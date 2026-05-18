<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeadlineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string  $title,
        protected string  $message,
        protected string  $deadline,
        protected string  $priority = 'normal',
        protected ?int    $templateId = null,
        protected ?string $templateCode = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $priorityLabel = strtoupper($this->priority);

        $mail = (new MailMessage)
            ->subject("[{$priorityLabel}] Task Reminder: {$this->title}")
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->message)
            ->line('**Deadline:** ' . $this->deadline);

        if ($this->templateId) {
            $mail->action('View Template', url('/super-admin/templates/' . $this->templateId));
        }

        return $mail->line('Please complete the assigned task before the deadline.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'deadline_reminder',
            'title'         => $this->title,
            'message'       => $this->message,
            'deadline'      => $this->deadline,
            'priority'      => $this->priority,
            'template_id'   => $this->templateId,
            'template_code' => $this->templateCode,
        ];
    }
}
