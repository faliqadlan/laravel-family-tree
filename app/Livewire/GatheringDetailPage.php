<?php

namespace App\Livewire;

use App\Models\Gathering;
use App\Models\GatheringInvitation;
use App\Services\GatheringService;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class GatheringDetailPage extends Component
{
    public string $token;

    public ?GatheringInvitation $invitation = null;

    public ?Gathering $gathering = null;

    public string $rsvpStatus = '';

    public int $guestsCount = 0;

    public string $rsvpNotes = '';

    public string $commentContent = '';

    public string $newAmount = '';

    public string $newAmountNotes = '';

    protected GatheringService $gatheringService;

    public function boot(GatheringService $gatheringService): void
    {
        $this->gatheringService = $gatheringService;
    }

    public function mount(string $token): void
    {
        $this->token = $token;

        $this->invitation = GatheringInvitation::where('token', $token)
            ->with(['gathering', 'user', 'person'])
            ->firstOrFail();

        $this->gathering = $this->invitation->gathering;
        $this->rsvpStatus = $this->invitation->status;
        $this->guestsCount = $this->invitation->guests_count;
        $this->rsvpNotes = $this->invitation->notes ?? '';
    }

    public function submitRsvp(): void
    {
        $this->validate([
            'rsvpStatus' => 'required|in:attending,maybe,declined',
            'guestsCount' => 'integer|min:0|max:20',
            'rsvpNotes' => 'nullable|string|max:500',
        ]);

        $this->gatheringService->respondToInvitation($this->invitation, $this->rsvpStatus, [
            'notes' => $this->rsvpNotes ?: null,
            'guests_count' => $this->guestsCount,
        ]);

        $this->invitation->refresh();
        session()->flash('rsvp_success', 'Your RSVP has been saved.');
    }

    public function submitContribution(): void
    {
        $this->validate([
            'newAmount' => 'required|numeric|min:0.01',
            'newAmountNotes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        if (!$user) {
            session()->flash('error', 'You must be logged in to contribute.');
            return;
        }

        $this->gatheringService->addContribution($this->gathering, $user, [
            'amount' => $this->newAmount,
            'currency' => $this->gathering->funding_currency,
            'notes' => $this->newAmountNotes ?: null,
        ]);

        $this->newAmount = '';
        $this->newAmountNotes = '';
        $this->gathering->refresh();

        session()->flash('contribution_success', 'Your contribution has been recorded. Thank you!');
    }

    public function submitComment(): void
    {
        $this->validate([
            'commentContent' => 'required|string|min:1|max:2000',
        ]);

        $user = auth()->user();

        if (!$user) {
            session()->flash('error', 'You must be logged in to comment.');
            return;
        }

        $this->gatheringService->addComment($this->gathering, $user, $this->commentContent);

        $this->commentContent = '';
        $this->gathering->refresh();
    }

    public function downloadIcs(): Response
    {
        $ics = $this->gathering->generateIcs();

        return response($ics, 200, [
            'Content-Type' => 'text/calendar',
            'Content-Disposition' => 'attachment; filename="gathering-' . $this->gathering->id . '.ics"',
        ]);
    }

    public function render(): \Illuminate\View\View
    {
        $attendees = $this->gathering->invitations()
            ->where('status', GatheringInvitation::STATUS_ATTENDING)
            ->with(['user', 'person'])
            ->get();

        $comments = $this->gathering->comments()
            ->with(['user', 'replies.user'])
            ->get();

        $contributions = $this->gathering->contributions()
            ->whereIn('status', ['pledged', 'paid'])
            ->with('user')
            ->get();

        return view('livewire.gathering-detail-page', [
            'attendees' => $attendees,
            'comments' => $comments,
            'contributions' => $contributions,
        ]);
    }
}
