<?php

namespace App\Filament\App\Pages;

use App\Models\User;
use App\Models\UserConnection;
use App\Models\UserPrivacySetting;
use App\Services\ConnectionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class NetworkingHubPage extends Page
{
    protected string $view = 'filament.app.pages.networking-hub-page';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Networking Hub';

    protected static string|\UnitEnum|null $navigationGroup = '👤 Account & Settings';

    public string $activeTab = 'discover';

    public string $searchQuery = '';

    public string $privacyLevel = 'connections';

    /** @var array<string, string> */
    public array $fieldVisibility = [];

    public string $connectMessage = '';

    public function mount(): void
    {
        $privacy = Auth::user()->getPrivacySetting();
        $this->privacyLevel = $privacy->profile_visibility;
        $this->fieldVisibility = $privacy->field_visibility ?? [];
    }

    public function getTitle(): string
    {
        return 'Networking Hub';
    }

    /**
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getDiscoverableUsers()
    {
        $currentUser = Auth::user();

        $connectedIds = UserConnection::where(function ($q) use ($currentUser) {
            $q->where('requester_id', $currentUser->id)
                ->orWhere('receiver_id', $currentUser->id);
        })->get()->flatMap(fn ($c) => [$c->requester_id, $c->receiver_id])
            ->unique()
            ->filter(fn ($id) => $id !== $currentUser->id)
            ->values();

        return User::where('id', '!=', $currentUser->id)
            ->whereNotIn('id', $connectedIds)
            ->when($this->searchQuery, fn ($q) => $q->where('name', 'like', '%' . $this->searchQuery . '%'))
            ->paginate(12);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingRequests()
    {
        return app(ConnectionService::class)->getPendingRequests(Auth::user());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getConnections()
    {
        $currentUser = Auth::user();

        return UserConnection::where(function ($q) use ($currentUser) {
            $q->where('requester_id', $currentUser->id)
                ->orWhere('receiver_id', $currentUser->id);
        })->where('status', UserConnection::STATUS_APPROVED)
            ->with(['requester', 'receiver'])
            ->get();
    }

    public function sendConnectionRequest(int $userId): void
    {
        try {
            $receiver = User::findOrFail($userId);
            app(ConnectionService::class)->sendRequest(Auth::user(), $receiver, $this->connectMessage);
            $this->connectMessage = '';

            Notification::make()
                ->title('Connection request sent!')
                ->success()
                ->send();
        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function approveRequest(int $connectionId): void
    {
        try {
            $connection = UserConnection::findOrFail($connectionId);
            app(ConnectionService::class)->approveRequest($connection, Auth::user());

            Notification::make()
                ->title('Connection approved!')
                ->success()
                ->send();
        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function rejectRequest(int $connectionId): void
    {
        try {
            $connection = UserConnection::findOrFail($connectionId);
            app(ConnectionService::class)->rejectRequest($connection, Auth::user());

            Notification::make()
                ->title('Connection request rejected.')
                ->warning()
                ->send();
        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function savePrivacySettings(): void
    {
        $user = Auth::user();

        UserPrivacySetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'profile_visibility' => $this->privacyLevel,
                'field_visibility' => $this->fieldVisibility,
            ]
        );

        Notification::make()
            ->title('Privacy settings saved.')
            ->success()
            ->send();
    }
}
