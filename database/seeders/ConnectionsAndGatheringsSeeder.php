<?php

namespace Database\Seeders;

use App\Models\Gathering;
use App\Models\GatheringComment;
use App\Models\GatheringInvitation;
use App\Models\Team;
use App\Models\User;
use App\Models\UserConnection;
use App\Models\UserPrivacySetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ConnectionsAndGatheringsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::take(4)->get();

        if ($users->count() < 2) {
            $this->command->warn('Not enough users to seed connections and gatherings. Run UserSeeder first.');
            return;
        }

        $user1 = $users->get(0);
        $user2 = $users->get(1);
        $user3 = $users->count() > 2 ? $users->get(2) : $user1;
        $user4 = $users->count() > 3 ? $users->get(3) : $user2;
        $teamId = $user1->current_team_id ?: Team::query()->value('id');

        // Create 3 UserConnections
        UserConnection::firstOrCreate(
            ['requester_id' => $user1->id, 'receiver_id' => $user2->id],
            ['status' => UserConnection::STATUS_PENDING]
        );

        UserConnection::firstOrCreate(
            ['requester_id' => $user2->id, 'receiver_id' => $user3->id],
            ['status' => UserConnection::STATUS_APPROVED]
        );

        UserConnection::firstOrCreate(
            ['requester_id' => $user3->id, 'receiver_id' => $user4->id],
            ['status' => UserConnection::STATUS_BLOCKED]
        );

        // Create a UserPrivacySetting for user1
        UserPrivacySetting::firstOrCreate(
            ['user_id' => $user1->id],
            [
                'profile_visibility' => UserPrivacySetting::VISIBILITY_CONNECTIONS,
                'field_visibility' => [],
            ]
        );

        // Gathering 1: Annual Family Reunion
        $gathering1 = Gathering::create([
            'team_id' => $teamId,
            'organizer_id' => $user1->id,
            'title' => 'Annual Family Reunion 2026',
            'type' => Gathering::TYPE_REUNION,
            'description' => 'Our beloved annual family reunion! Join us for food, fun, and memories.',
            'location' => 'Central Park, New York',
            'start_date' => now()->addMonths(2),
            'end_date' => now()->addMonths(2)->addHours(6),
            'status' => Gathering::STATUS_PUBLISHED,
            'privacy' => Gathering::PRIVACY_CONNECTIONS,
            'funding_goal' => 500.00,
            'funding_currency' => 'USD',
            'funding_description' => 'Help cover catering and venue costs.',
        ]);

        $inv1 = GatheringInvitation::create([
            'gathering_id' => $gathering1->id,
            'user_id' => $user2->id,
            'email' => $user2->email,
            'status' => GatheringInvitation::STATUS_ATTENDING,
            'guests_count' => 2,
            'token' => Str::random(64),
            'sent_at' => now(),
            'responded_at' => now(),
        ]);

        GatheringComment::create([
            'gathering_id' => $gathering1->id,
            'user_id' => $user1->id,
            'content' => 'So excited for this year\'s reunion! It\'s going to be amazing.',
        ]);

        GatheringComment::create([
            'gathering_id' => $gathering1->id,
            'user_id' => $user2->id,
            'content' => 'Can\'t wait to see everyone! Should we do a potluck?',
        ]);

        // Gathering 2: Memorial Service
        $gathering2 = Gathering::create([
            'team_id' => $teamId,
            'organizer_id' => $user2->id,
            'title' => 'In Memory of Grandma Rose',
            'type' => Gathering::TYPE_MEMORIAL,
            'description' => 'A celebration of life and remembrance for our beloved Grandma Rose.',
            'location' => 'St. Mary\'s Church Hall',
            'start_date' => now()->addWeeks(3),
            'end_date' => now()->addWeeks(3)->addHours(3),
            'status' => Gathering::STATUS_PUBLISHED,
            'privacy' => Gathering::PRIVACY_PRIVATE,
            'is_virtual' => false,
        ]);

        GatheringInvitation::create([
            'gathering_id' => $gathering2->id,
            'user_id' => $user1->id,
            'email' => $user1->email,
            'status' => GatheringInvitation::STATUS_ATTENDING,
            'guests_count' => 0,
            'token' => Str::random(64),
            'sent_at' => now(),
            'responded_at' => now(),
        ]);

        GatheringComment::create([
            'gathering_id' => $gathering2->id,
            'user_id' => $user2->id,
            'content' => 'Please bring your favorite photo of Grandma Rose to share.',
        ]);

        $this->command->info('Connections and gatherings seeded successfully.');
    }
}
