<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserConnection;

class UserConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserConnection $connection): bool
    {
        return $user->id === $connection->requester_id
            || $user->id === $connection->receiver_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, UserConnection $connection): bool
    {
        return $user->id === $connection->requester_id
            || $user->id === $connection->receiver_id;
    }

    public function delete(User $user, UserConnection $connection): bool
    {
        return $user->id === $connection->requester_id
            || $user->id === $connection->receiver_id;
    }
}
