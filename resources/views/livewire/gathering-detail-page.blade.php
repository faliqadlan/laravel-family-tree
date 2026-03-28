<div class="min-h-screen bg-gray-50">
    {{-- Flash Messages --}}
    @if (session('rsvp_success'))
        <div class="fixed top-4 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
            {{ session('rsvp_success') }}
        </div>
    @endif
    @if (session('contribution_success'))
        <div class="fixed top-4 right-4 z-50 bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg">
            {{ session('contribution_success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="fixed top-4 right-4 z-50 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Hero Header --}}
    <div class="bg-gradient-to-br from-indigo-700 to-purple-700 text-white py-12 px-4">
        <div class="max-w-4xl mx-auto">
            <div class="flex flex-wrap items-center gap-3 mb-3">
                <span class="bg-white/20 text-white text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wide">
                    {{ ucfirst($gathering->type) }}
                </span>
                <span class="text-white/70 text-sm">
                    @if ($gathering->status === 'published') 🟢 Published
                    @elseif ($gathering->status === 'cancelled') 🔴 Cancelled
                    @elseif ($gathering->status === 'completed') ✅ Completed
                    @else ⏳ Draft
                    @endif
                </span>
            </div>

            <h1 class="text-3xl font-bold mb-2">{{ $gathering->title }}</h1>

            <div class="flex flex-wrap gap-6 text-white/90 text-sm mt-4">
                <div class="flex items-center gap-2">
                    <span>📅</span>
                    <span>{{ $gathering->start_date->format('F j, Y \a\t g:i A') }}</span>
                </div>
                @if ($gathering->end_date)
                    <div class="flex items-center gap-2">
                        <span>⏰</span>
                        <span>Ends {{ $gathering->end_date->format('g:i A') }}</span>
                    </div>
                @endif
                @if ($gathering->location)
                    <div class="flex items-center gap-2">
                        <span>📍</span>
                        @if ($gathering->location_url)
                            <a href="{{ $gathering->location_url }}" target="_blank" class="underline hover:text-white">{{ $gathering->location }}</a>
                        @else
                            <span>{{ $gathering->location }}</span>
                        @endif
                    </div>
                @endif
                @if ($gathering->is_virtual && $gathering->meeting_url)
                    <div class="flex items-center gap-2">
                        <span>🔗</span>
                        <a href="{{ $gathering->meeting_url }}" target="_blank" class="underline hover:text-white">Join Online</a>
                    </div>
                @endif
            </div>

            {{-- Download ICS Button --}}
            <div class="mt-5">
                <a href="{{ url('/gatherings/' . $token . '/ics') }}"
                   wire:click.prevent="downloadIcs"
                   class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    📥 Download Calendar (.ics)
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-8 space-y-8">

        {{-- Description --}}
        @if ($gathering->description)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">About This Event</h2>
                <p class="text-gray-600 whitespace-pre-line">{{ $gathering->description }}</p>
            </div>
        @endif

        {{-- RSVP Section --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Your RSVP</h2>

            <form wire:submit.prevent="submitRsvp" class="space-y-4">
                {{-- Status Buttons --}}
                <div class="flex flex-wrap gap-3">
                    <button type="button"
                        wire:click="$set('rsvpStatus', 'attending')"
                        class="px-5 py-2 rounded-lg font-medium text-sm transition border-2
                            {{ $rsvpStatus === 'attending' ? 'bg-green-500 border-green-500 text-white' : 'bg-white border-gray-300 text-gray-600 hover:border-green-400' }}">
                        ✅ Attending
                    </button>
                    <button type="button"
                        wire:click="$set('rsvpStatus', 'maybe')"
                        class="px-5 py-2 rounded-lg font-medium text-sm transition border-2
                            {{ $rsvpStatus === 'maybe' ? 'bg-yellow-400 border-yellow-400 text-white' : 'bg-white border-gray-300 text-gray-600 hover:border-yellow-400' }}">
                        🤔 Maybe
                    </button>
                    <button type="button"
                        wire:click="$set('rsvpStatus', 'declined')"
                        class="px-5 py-2 rounded-lg font-medium text-sm transition border-2
                            {{ $rsvpStatus === 'declined' ? 'bg-red-500 border-red-500 text-white' : 'bg-white border-gray-300 text-gray-600 hover:border-red-400' }}">
                        ❌ Declined
                    </button>
                </div>

                @if ($rsvpStatus === 'attending')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Additional Guests</label>
                        <input type="number" wire:model="guestsCount" min="0" max="20"
                            class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                    <textarea wire:model="rsvpNotes" rows="2"
                        placeholder="Any dietary requirements, questions, or notes..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none resize-none"></textarea>
                </div>

                @error('rsvpStatus') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror

                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-2 rounded-lg text-sm transition">
                    Save RSVP
                </button>
            </form>
        </div>

        {{-- Attendees --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                Attendees
                <span class="text-sm font-normal text-gray-500 ml-2">({{ $attendees->count() }} attending)</span>
            </h2>

            @if ($attendees->isEmpty())
                <p class="text-gray-500 text-sm">No confirmed attendees yet.</p>
            @else
                <div class="flex flex-wrap gap-3">
                    @foreach ($attendees as $attendee)
                        <div class="flex items-center gap-2 bg-gray-50 rounded-full px-4 py-2 text-sm">
                            <div class="w-7 h-7 rounded-full bg-indigo-200 flex items-center justify-center text-indigo-700 font-bold text-xs uppercase">
                                {{ substr($attendee->display_name, 0, 1) }}
                            </div>
                            @if ($gathering->privacy === 'public' || (auth()->check() && auth()->user()->isConnectedTo($attendee->user_id ?? 0)))
                                <span class="font-medium text-gray-700">{{ $attendee->display_name }}</span>
                            @else
                                <span class="text-gray-500">Family Member</span>
                            @endif
                            @if ($attendee->guests_count > 0)
                                <span class="text-gray-400 text-xs">+{{ $attendee->guests_count }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Funding / Contributions --}}
        @if ($gathering->funding_goal)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Fundraising</h2>
                @if ($gathering->funding_description)
                    <p class="text-gray-600 text-sm mb-4">{{ $gathering->funding_description }}</p>
                @endif

                {{-- Progress bar --}}
                @php
                    $progress = $gathering->getFundingProgress();
                    $total = $gathering->getTotalContributions();
                    $goal = (float) $gathering->funding_goal;
                @endphp

                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                        <span>{{ number_format($total, 2) }} {{ $gathering->funding_currency }} raised</span>
                        <span>Goal: {{ number_format($goal, 2) }} {{ $gathering->funding_currency }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-indigo-500 h-3 rounded-full transition-all" style="width: {{ min(100, $progress) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">{{ number_format($progress, 1) }}% funded</p>
                </div>

                {{-- Contributors --}}
                @if ($contributions->isNotEmpty())
                    <div class="mb-4 space-y-2">
                        @foreach ($contributions as $contrib)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700">{{ $contrib->user?->name ?? 'Anonymous' }}</span>
                                <span class="font-medium text-indigo-600">
                                    {{ number_format((float) $contrib->amount, 2) }} {{ $contrib->currency }}
                                    <span class="text-xs text-gray-400 ml-1">{{ ucfirst($contrib->status) }}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Pledge Form --}}
                @auth
                    <form wire:submit.prevent="submitContribution" class="border-t pt-4 space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700">Make a Pledge</h3>
                        <div class="flex gap-3 flex-wrap">
                            <div>
                                <label class="text-xs text-gray-500">Amount ({{ $gathering->funding_currency }})</label>
                                <input type="number" wire:model="newAmount" step="0.01" min="0.01"
                                    class="block w-32 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                                @error('newAmount') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex-1">
                                <label class="text-xs text-gray-500">Notes (optional)</label>
                                <input type="text" wire:model="newAmountNotes" placeholder="e.g. For venue deposit"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                            </div>
                        </div>
                        <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                            Pledge
                        </button>
                    </form>
                @endauth
            </div>
        @endif

        {{-- Discussion / Comments --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Discussion</h2>

            {{-- Comment Form --}}
            @auth
                <form wire:submit.prevent="submitComment" class="mb-6">
                    <textarea wire:model="commentContent" rows="3"
                        placeholder="Share something with the group..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none resize-none mb-2"></textarea>
                    @error('commentContent') <p class="text-red-500 text-xs mb-1">{{ $message }}</p> @enderror
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
                        Post Comment
                    </button>
                </form>
            @endauth

            {{-- Comments List --}}
            @if ($comments->isEmpty())
                <p class="text-gray-500 text-sm">No comments yet. Be the first to say something!</p>
            @else
                <div class="space-y-4">
                    @foreach ($comments as $comment)
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs uppercase flex-shrink-0">
                                {{ substr($comment->user?->name ?? '?', 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-baseline gap-2 mb-1">
                                    <span class="text-sm font-semibold text-gray-800">{{ $comment->user?->name ?? 'Unknown' }}</span>
                                    <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-sm text-gray-700">{{ $comment->content }}</p>

                                {{-- Replies --}}
                                @if ($comment->replies->isNotEmpty())
                                    <div class="mt-3 ml-4 space-y-3 border-l-2 border-gray-100 pl-4">
                                        @foreach ($comment->replies->where('is_deleted', false) as $reply)
                                            <div class="flex gap-2">
                                                <div class="w-6 h-6 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 font-bold text-xs uppercase flex-shrink-0">
                                                    {{ substr($reply->user?->name ?? '?', 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="flex items-baseline gap-2 mb-0.5">
                                                        <span class="text-xs font-semibold text-gray-700">{{ $reply->user?->name ?? 'Unknown' }}</span>
                                                        <span class="text-xs text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                                                    </div>
                                                    <p class="text-xs text-gray-600">{{ $reply->content }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>
