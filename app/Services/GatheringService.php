<?php

namespace App\Services;

use App\Models\Gathering;
use App\Models\GatheringComment;
use App\Models\GatheringContribution;
use App\Models\GatheringInvitation;
use App\Models\Person;
use App\Models\User;
use App\Notifications\GatheringInvitationNotification;
use App\Notifications\GatheringReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GatheringService
{
    public function createGathering(User $organizer, array $data): Gathering
    {
        return $organizer->organizedGatherings()->create($data);
    }

    public function inviteUser(Gathering $gathering, User $user, User $actor): GatheringInvitation
    {
        /** @var GatheringInvitation $invitation */
        $invitation = $gathering->invitations()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'email' => $user->email,
                'status' => GatheringInvitation::STATUS_PENDING,
                'sent_at' => now(),
            ]
        );

        if ($invitation->wasRecentlyCreated) {
            $user->notify(new GatheringInvitationNotification($gathering, $invitation));
        }

        return $invitation;
    }

    public function inviteDescendants(Gathering $gathering, Person $root, User $actor): array
    {
        $invitations = [];
        $visited = [];

        $this->traverseDescendants($root, $gathering, $actor, $invitations, $visited);

        return $invitations;
    }

    private function traverseDescendants(Person $person, Gathering $gathering, User $actor, array &$invitations, array &$visited): void
    {
        if (in_array($person->id, $visited)) {
            return;
        }
        $visited[] = $person->id;

        $children = $person->children();
        $childCollection = $children instanceof Collection ? $children : collect($children->get());

        foreach ($childCollection as $child) {
            if ($child->email) {
                $user = User::where('email', $child->email)->first();
                if ($user) {
                    $invitations[] = $this->inviteUser($gathering, $user, $actor);
                }
            }

            $this->traverseDescendants($child, $gathering, $actor, $invitations, $visited);
        }
    }

    public function suggestInvitees(Gathering $gathering): array
    {
        $suggestions = collect();

        // Suggest users from the same tree
        if ($gathering->tree_id) {
            $treeUsers = User::whereHas('trees', fn ($q) => $q->where('id', $gathering->tree_id))
                ->where('id', '!=', $gathering->organizer_id)
                ->get();
            $suggestions = $suggestions->merge($treeUsers);
        }

        // Suggest connections of the organizer
        $connectedUserIds = \App\Models\UserConnection::where(function ($q) use ($gathering) {
            $q->where('requester_id', $gathering->organizer_id)
              ->orWhere('receiver_id', $gathering->organizer_id);
        })->where('status', \App\Models\UserConnection::STATUS_APPROVED)
          ->get()
          ->map(fn ($c) => $c->requester_id === $gathering->organizer_id ? $c->receiver_id : $c->requester_id);

        $connectedUsers = User::whereIn('id', $connectedUserIds)->get();
        $suggestions = $suggestions->merge($connectedUsers);

        // Exclude already invited users
        $invitedUserIds = $gathering->invitations()->pluck('user_id')->filter()->all();

        return $suggestions
            ->unique('id')
            ->whereNotIn('id', $invitedUserIds)
            ->values()
            ->all();
    }

    public function respondToInvitation(GatheringInvitation $invitation, string $status, array $data = []): GatheringInvitation
    {
        $notes = $data['notes'] ?? null;
        $guests = (int) ($data['guests_count'] ?? 0);

        match ($status) {
            GatheringInvitation::STATUS_ATTENDING => $invitation->attend($notes, $guests),
            GatheringInvitation::STATUS_MAYBE => $invitation->maybe($notes),
            GatheringInvitation::STATUS_DECLINED => $invitation->decline($notes),
            default => null,
        };

        return $invitation->fresh();
    }

    public function addContribution(Gathering $gathering, User $user, array $data): GatheringContribution
    {
        return $gathering->contributions()->create(array_merge($data, [
            'user_id' => $user->id,
        ]));
    }

    public function addComment(Gathering $gathering, User $user, string $content, ?int $parentId = null): GatheringComment
    {
        return $gathering->comments()->create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'content' => $content,
        ]);
    }

    public function sendReminders(int $daysBeforeEvent = 3): int
    {
        $targetDate = now()->addDays($daysBeforeEvent)->toDateString();

        $gatherings = Gathering::published()
            ->whereDate('start_date', $targetDate)
            ->with('invitations.user')
            ->get();

        $count = 0;

        foreach ($gatherings as $gathering) {
            $attendingInvitations = $gathering->invitations()
                ->whereIn('status', [GatheringInvitation::STATUS_ATTENDING, GatheringInvitation::STATUS_MAYBE])
                ->with('user')
                ->get();

            foreach ($attendingInvitations as $invitation) {
                if ($invitation->user) {
                    $invitation->user->notify(new GatheringReminderNotification($gathering, $daysBeforeEvent));
                    $count++;
                }
            }
        }

        return $count;
    }
}
