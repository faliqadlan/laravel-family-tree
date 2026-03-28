<?php

namespace App\Notifications;

use App\Models\UserConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConnectionApprovedNotification extends Notification implements ShouldQueue
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
        $receiverName = $this->connection->receiver->name;

        return (new MailMessage)
            ->subject("{$receiverName} accepted your connection request")
            ->greeting('Great news!')
            ->line("{$receiverName} has approved your connection request.")
            ->action('View Your Connections', url('/app/networking-hub'))
            ->line('You are now connected and can exchange messages.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'connection_approved',
            'connection_id' => $this->connection->id,
            'receiver_id' => $this->connection->receiver_id,
            'receiver_name' => $this->connection->receiver->name,
        ];
    }
}
