<?php

namespace App\Policies;

use App\Models\Gathering;
use App\Models\GatheringInvitation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GatheringPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Gathering $gathering): bool
    {
        if ($gathering->privacy === Gathering::PRIVACY_PUBLIC) {
            return true;
        }

        if ($gathering->isOrganizer($user)) {
            return true;
        }

        return $gathering->invitations()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Gathering $gathering): bool
    {
        return $gathering->isOrganizer($user);
    }

    public function delete(User $user, Gathering $gathering): bool
    {
        return $gathering->isOrganizer($user);
    }

    public function invite(User $user, Gathering $gathering): bool
    {
        return $gathering->isOrganizer($user);
    }

    public function contribute(User $user, Gathering $gathering): bool
    {
        if ($gathering->isOrganizer($user)) {
            return true;
        }

        $invitation = $gathering->getUserInvitation($user);

        return $invitation !== null && in_array($invitation->status, [
            GatheringInvitation::STATUS_ATTENDING,
            GatheringInvitation::STATUS_MAYBE,
        ]);
    }

    public function comment(User $user, Gathering $gathering): bool
    {
        if ($gathering->isOrganizer($user)) {
            return true;
        }

        $invitation = $gathering->getUserInvitation($user);

        return $invitation !== null && in_array($invitation->status, [
            GatheringInvitation::STATUS_ATTENDING,
            GatheringInvitation::STATUS_MAYBE,
        ]);
    }
}
