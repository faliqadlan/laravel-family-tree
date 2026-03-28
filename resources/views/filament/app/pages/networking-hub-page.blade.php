<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                @foreach(['discover' => '🔍 Discover', 'requests' => '📬 Requests', 'connections' => '🤝 Connections', 'privacy' => '🔒 Privacy Settings'] as $tab => $label)
                    <button
                        wire:click="$set('activeTab', '{{ $tab }}')"
                        class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors
                            {{ $activeTab === $tab
                                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- DISCOVER TAB --}}
        @if($activeTab === 'discover')
            <x-filament::section>
                <x-slot name="heading">Discover People</x-slot>
                <x-slot name="description">Find and connect with other members of the community.</x-slot>

                <div class="mb-4">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="searchQuery"
                        placeholder="Search by name…"
                        class="w-full rounded-lg border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    />
                </div>

                @php $users = $this->getDiscoverableUsers(); @endphp

                @if($users->isEmpty())
                    <p class="text-sm text-gray-500 text-center py-8">No users found.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($users as $user)
                            <div class="flex items-center justify-between p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-full object-cover" />
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</span>
                                </div>
                                <button
                                    wire:click="sendConnectionRequest({{ $user->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors"
                                >
                                    <x-heroicon-o-user-plus class="w-3.5 h-3.5" /> Connect
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                @endif
            </x-filament::section>
        @endif

        {{-- REQUESTS TAB --}}
        @if($activeTab === 'requests')
            <x-filament::section>
                <x-slot name="heading">Pending Requests</x-slot>
                <x-slot name="description">Approve or reject incoming connection requests.</x-slot>

                @php $requests = $this->getPendingRequests(); @endphp

                @if($requests->isEmpty())
                    <p class="text-sm text-gray-500 text-center py-8">No pending requests.</p>
                @else
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($requests as $connection)
                            <li class="flex items-center justify-between py-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $connection->requester->profile_photo_url }}" alt="{{ $connection->requester->name }}" class="h-10 w-10 rounded-full object-cover" />
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $connection->requester->name }}</p>
                                        @if($connection->message)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 italic">"{{ $connection->message }}"</p>
                                        @endif
                                        <p class="text-xs text-gray-400">{{ $connection->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        wire:click="approveRequest({{ $connection->id }})"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition-colors"
                                    >
                                        ✓ Approve
                                    </button>
                                    <button
                                        wire:click="rejectRequest({{ $connection->id }})"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 transition-colors"
                                    >
                                        ✕ Reject
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-filament::section>
        @endif

        {{-- CONNECTIONS TAB --}}
        @if($activeTab === 'connections')
            <x-filament::section>
                <x-slot name="heading">Your Connections</x-slot>
                <x-slot name="description">People you are connected with.</x-slot>

                @php
                    $connections = $this->getConnections();
                    $currentId = auth()->id();
                @endphp

                @if($connections->isEmpty())
                    <p class="text-sm text-gray-500 text-center py-8">You have no connections yet. Discover and connect with people!</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($connections as $connection)
                            @php $other = $connection->requester_id === $currentId ? $connection->receiver : $connection->requester; @endphp
                            <div class="flex items-center gap-3 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                                <img src="{{ $other->profile_photo_url }}" alt="{{ $other->name }}" class="h-10 w-10 rounded-full object-cover" />
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $other->name }}</p>
                                    <p class="text-xs text-gray-400">Connected {{ $connection->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        @endif

        {{-- PRIVACY SETTINGS TAB --}}
        @if($activeTab === 'privacy')
            <x-filament::section>
                <x-slot name="heading">Privacy Settings</x-slot>
                <x-slot name="description">Control who can see your profile and which fields are visible.</x-slot>

                <form wire:submit.prevent="savePrivacySettings" class="space-y-6">

                    {{-- Profile Visibility --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Profile Visibility
                        </label>
                        <div class="flex flex-wrap gap-3">
                            @foreach(['public' => '🌍 Public', 'connections' => '🤝 Connections Only', 'private' => '🔒 Private'] as $value => $label)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        wire:model="privacyLevel"
                                        value="{{ $value }}"
                                        class="text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-gray-400">
                            Public: anyone can see your profile. Connections: only your connections. Private: hidden from everyone.
                        </p>
                    </div>

                    {{-- Field-Level Visibility --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Field Visibility
                        </label>
                        <p class="text-xs text-gray-400 mb-3">
                            Override visibility for individual profile fields.
                            <strong>masked</strong> shows partial data (e.g. "J*** S***", "198X").
                        </p>
                        <div class="space-y-3">
                            @foreach(['name' => 'Name', 'birthday' => 'Birthday', 'photo' => 'Profile Photo', 'email' => 'Email Address', 'phone' => 'Phone Number'] as $field => $fieldLabel)
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $fieldLabel }}</span>
                                    <select
                                        wire:model="fieldVisibility.{{ $field }}"
                                        class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    >
                                        <option value="">— Use profile default —</option>
                                        <option value="public">🌍 Public</option>
                                        <option value="connections">🤝 Connections Only</option>
                                        <option value="masked">👤 Masked</option>
                                        <option value="hidden">🚫 Hidden</option>
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="px-5 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors"
                        >
                            Save Settings
                        </button>
                    </div>

                </form>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
