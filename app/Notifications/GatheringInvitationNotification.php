<?php

namespace App\Notifications;

use App\Models\Gathering;
use App\Models\GatheringInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GatheringInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Gathering $gathering,
        protected GatheringInvitation $invitation
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rsvpUrl = url('/gatherings/' . $this->invitation->token);

        return (new MailMessage)
            ->subject('You\'re Invited: ' . $this->gathering->title)
            ->greeting('You\'ve been invited to a family gathering!')
            ->line('**' . $this->gathering->title . '**')
            ->line('📅 Date: ' . $this->gathering->start_date->format('F j, Y g:i A'))
            ->when($this->gathering->location, fn ($mail) => $mail->line('📍 Location: ' . $this->gathering->location))
            ->when($this->gathering->description, fn ($mail) => $mail->line($this->gathering->description))
            ->action('RSVP Now', $rsvpUrl)
            ->line('Please let us know if you\'ll be attending by clicking the button above.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'gathering_invitation',
            'gathering_id' => $this->gathering->id,
            'gathering_title' => $this->gathering->title,
            'invitation_id' => $this->invitation->id,
        ];
    }
}
