<?php

namespace App\Notifications;

use App\Models\Gathering;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GatheringReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Gathering $gathering,
        protected int $daysUntil
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dayLabel = $this->daysUntil === 1 ? 'tomorrow' : "in {$this->daysUntil} days";

        return (new MailMessage)
            ->subject('Reminder: ' . $this->gathering->title . ' is ' . $dayLabel)
            ->greeting("Don't forget - your family gathering is coming up!")
            ->line('**' . $this->gathering->title . '**')
            ->line("This event is happening {$dayLabel}.")
            ->line('📅 Date: ' . $this->gathering->start_date->format('F j, Y g:i A'))
            ->when($this->gathering->location, fn ($mail) => $mail->line('📍 Location: ' . $this->gathering->location))
            ->when($this->gathering->is_virtual && $this->gathering->meeting_url, fn ($mail) => $mail->line('🔗 Join online: ' . $this->gathering->meeting_url))
            ->action('View Event Details', url('/gatherings'))
            ->line('We look forward to seeing you there!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'gathering_reminder',
            'gathering_id' => $this->gathering->id,
            'gathering_title' => $this->gathering->title,
            'days_until' => $this->daysUntil,
        ];
    }
}
