<?php

namespace App\Notifications;

use App\Models\UserConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConnectionRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserConnection $connection;

    public function __construct(UserConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $requesterName = $this->connection->requester->name;

        return (new MailMessage)
            ->subject('New Connection Request from ' . $requesterName)
            ->greeting('Hello!')
            ->line("{$requesterName} has sent you a connection request.")
            ->when($this->connection->message, fn ($mail) => $mail->line('Message: ' . $this->connection->message))
            ->action('View Connection Request', url('/app/networking-hub'))
            ->line('Log in to approve or reject this request.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'connection_request',
            'connection_id' => $this->connection->id,
            'requester_id' => $this->connection->requester_id,
            'requester_name' => $this->connection->requester->name,
        ];
    }
}
