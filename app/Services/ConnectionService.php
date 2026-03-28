<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserConnection;
use App\Models\UserPrivacySetting;
use App\Notifications\ConnectionApprovedNotification;
use App\Notifications\ConnectionRequestNotification;
use Illuminate\Database\Eloquent\Collection;

class ConnectionService
{
    /**
     * Send a connection request from $requester to $receiver.
     *
     * @throws \InvalidArgumentException
     */
    public function sendRequest(User $requester, User $receiver, string $message = ''): UserConnection
    {
        if ($requester->id === $receiver->id) {
            throw new \InvalidArgumentException('You cannot connect with yourself.');
        }

        $existing = UserConnection::getConnectionBetween($requester->id, $receiver->id);

        if ($existing) {
            if ($existing->status === UserConnection::STATUS_APPROVED) {
                throw new \InvalidArgumentException('You are already connected with this user.');
            }
            if ($existing->status === UserConnection::STATUS_PENDING) {
                throw new \InvalidArgumentException('A connection request is already pending.');
            }
            if ($existing->status === UserConnection::STATUS_BLOCKED) {
                throw new \InvalidArgumentException('Connection is not possible with this user.');
            }
        }

        $connection = UserConnection::create([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
            'status' => UserConnection::STATUS_PENDING,
            'message' => $message,
        ]);

        $receiver->notify(new ConnectionRequestNotification($connection));

        return $connection;
    }

    /**
     * Approve a pending connection request.
     *
     * @throws \InvalidArgumentException
     */
    public function approveRequest(UserConnection $connection, User $actor): UserConnection
    {
        if ($actor->id !== $connection->receiver_id) {
            throw new \InvalidArgumentException('Only the receiver can approve a connection request.');
        }

        $connection->approve();
        $connection->requester->notify(new ConnectionApprovedNotification($connection));

        return $connection;
    }

    /**
     * Reject a pending connection request.
     *
     * @throws \InvalidArgumentException
     */
    public function rejectRequest(UserConnection $connection, User $actor): UserConnection
    {
        if ($actor->id !== $connection->receiver_id) {
            throw new \InvalidArgumentException('Only the receiver can reject a connection request.');
        }

        $connection->reject();

        return $connection;
    }

    /**
     * Block a user, creating or updating a connection record to blocked status.
     */
    public function blockUser(User $blocker, User $blocked): UserConnection
    {
        $existing = UserConnection::getConnectionBetween($blocker->id, $blocked->id);

        if ($existing) {
            $existing->update([
                'requester_id' => $blocker->id,
                'receiver_id' => $blocked->id,
                'status' => UserConnection::STATUS_BLOCKED,
            ]);

            return $existing;
        }

        return UserConnection::create([
            'requester_id' => $blocker->id,
            'receiver_id' => $blocked->id,
            'status' => UserConnection::STATUS_BLOCKED,
        ]);
    }

    /**
     * Get the connection status string between two users, or null if none exists.
     */
    public function getConnectionStatus(User $userA, User $userB): ?string
    {
        $connection = UserConnection::getConnectionBetween($userA->id, $userB->id);

        return $connection?->status;
    }

    /**
     * Get all pending incoming connection requests for a user.
     */
    public function getPendingRequests(User $user): Collection
    {
        return UserConnection::where('receiver_id', $user->id)
            ->where('status', UserConnection::STATUS_PENDING)
            ->with('requester')
            ->get();
    }

    /**
     * Return an array of profile fields with masking applied based on privacy settings.
     *
     * @return array<string, mixed>
     */
    public function getMaskedProfile(User $subject, User $viewer): array
    {
        $privacy = $subject->getPrivacySetting();
        $isSelf = $viewer->id === $subject->id;
        $isConnected = $isSelf || $subject->isConnectedTo($viewer->id);

        // Owner always sees their own full profile data
        if ($isSelf) {
            return [
                'name' => $subject->name,
                'email' => $subject->email,
                'birthday' => $subject->birthday ?? null,
                'photo' => $subject->profile_photo_url ?? null,
                'phone' => $subject->phone ?? null,
            ];
        }

        // Respect top-level profile visibility for non-owners
        if ($privacy->profile_visibility === UserPrivacySetting::VISIBILITY_PRIVATE && !$isConnected) {
            return ['name' => null, 'email' => null, 'birthday' => null, 'photo' => null, 'phone' => null];
        }

        return [
            'name' => $privacy->maskValue('name', $subject->name, $isConnected),
            'email' => $privacy->maskValue('email', $subject->email, $isConnected),
            'birthday' => $privacy->maskValue('birthday', $subject->birthday ?? null, $isConnected),
            'photo' => $privacy->maskValue('photo', $subject->profile_photo_url ?? null, $isConnected),
            'phone' => $privacy->maskValue('phone', $subject->phone ?? null, $isConnected),
        ];
    }
}
