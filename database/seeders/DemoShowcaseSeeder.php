<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\Person;
use App\Models\Role;
use App\Models\Team;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoShowcaseSeeder extends Seeder
{
    /**
     * Seed demo-friendly users and a realistic family tree for screenshots.
     */
    public function run(): void
    {
        $demoPassword = env('DEMO_USER_PASSWORD', Str::random(16));

        $owner = User::updateOrCreate(
            ['email' => 'demo.owner@familytree365.test'],
            [
                'name' => 'Nadia Rahman',
                'password' => Hash::make($demoPassword),
                'email_verified_at' => now(),
            ]
        );

        $coResearcher = User::updateOrCreate(
            ['email' => 'demo.researcher@familytree365.test'],
            [
                'name' => 'Daniel Rahman',
                'password' => Hash::make($demoPassword),
                'email_verified_at' => now(),
            ]
        );

        $viewer = User::updateOrCreate(
            ['email' => 'demo.viewer@familytree365.test'],
            [
                'name' => 'Maya Rahman',
                'password' => Hash::make($demoPassword),
                'email_verified_at' => now(),
            ]
        );

        $team = Team::firstOrCreate(
            ['name' => 'Rahman Family Showcase'],
            [
                'personal_team' => false,
                'user_id' => $owner->id,
            ]
        );

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        if (! $owner->hasRole('admin')) {
            $owner->assignRole($adminRole);
        }

        foreach ([$coResearcher, $viewer] as $member) {
            if (! $member->hasRole('user')) {
                $member->assignRole($userRole);
            }
        }

        foreach ([$owner, $coResearcher, $viewer] as $user) {
            $user->teams()->syncWithoutDetaching([
                $team->id => ['role' => $user->id === $owner->id ? 'admin' : 'user'],
            ]);
            if ((int) $user->current_team_id !== (int) $team->id) {
                $user->current_team_id = $team->id;
                $user->save();
            }
        }

        $grandfather = $this->upsertPerson([
            'gid' => 'I1001',
            'givn' => 'Hassan',
            'surn' => 'Rahman',
            'name' => 'Hassan Rahman',
            'sex' => 'M',
            'birthday' => '1942-04-03',
            'deathday' => '2018-11-19',
            'birthday_plac' => 'Bandung, Indonesia',
            'deathday_plac' => 'Jakarta, Indonesia',
            'description' => 'Known for preserving old letters and family records.',
            'team_id' => $team->id,
            'email' => 'person.hassan.rahman@familytree365.test',
            'phone' => '+62-21-0001',
        ]);

        $grandmother = $this->upsertPerson([
            'gid' => 'I1002',
            'givn' => 'Siti',
            'surn' => 'Rahman',
            'name' => 'Siti Rahman',
            'sex' => 'F',
            'birthday' => '1947-09-12',
            'birthday_plac' => 'Yogyakarta, Indonesia',
            'description' => 'Collected oral stories from multiple generations.',
            'team_id' => $team->id,
            'email' => 'person.siti.rahman@familytree365.test',
            'phone' => '+62-21-0002',
        ]);

        $parentsFamily = Family::updateOrCreate(
            [
                'husband_id' => $grandfather->id,
                'wife_id' => $grandmother->id,
                'team_id' => $team->id,
            ],
            [
                'description' => 'Marriage of Hassan and Siti Rahman',
                'is_active' => 1,
                'nchi' => '2',
            ]
        );

        $father = $this->upsertPerson([
            'gid' => 'I1003',
            'givn' => 'Arif',
            'surn' => 'Rahman',
            'name' => 'Arif Rahman',
            'sex' => 'M',
            'birthday' => '1974-01-20',
            'birthday_plac' => 'Jakarta, Indonesia',
            'description' => 'Engineer and keeper of family photos.',
            'team_id' => $team->id,
            'email' => 'person.arif.rahman@familytree365.test',
            'phone' => '+62-21-0003',
            'child_in_family_id' => $parentsFamily->id,
        ]);

        $aunt = $this->upsertPerson([
            'gid' => 'I1004',
            'givn' => 'Lina',
            'surn' => 'Rahman',
            'name' => 'Lina Rahman',
            'sex' => 'F',
            'birthday' => '1978-06-28',
            'birthday_plac' => 'Jakarta, Indonesia',
            'description' => 'Family event organizer and recipe archivist.',
            'team_id' => $team->id,
            'email' => 'person.lina.rahman@familytree365.test',
            'phone' => '+62-21-0004',
            'child_in_family_id' => $parentsFamily->id,
        ]);

        $mother = $this->upsertPerson([
            'gid' => 'I1005',
            'givn' => 'Dewi',
            'surn' => 'Pratama',
            'name' => 'Dewi Pratama',
            'sex' => 'F',
            'birthday' => '1976-08-11',
            'birthday_plac' => 'Surabaya, Indonesia',
            'description' => 'Teacher with deep interest in local history.',
            'team_id' => $team->id,
            'email' => 'person.dewi.pratama@familytree365.test',
            'phone' => '+62-21-0005',
        ]);

        $childrenFamily = Family::updateOrCreate(
            [
                'husband_id' => $father->id,
                'wife_id' => $mother->id,
                'team_id' => $team->id,
            ],
            [
                'description' => 'Marriage of Arif Rahman and Dewi Pratama',
                'is_active' => 1,
                'nchi' => '3',
            ]
        );

        $ownerPerson = $this->upsertPerson([
            'gid' => 'I1006',
            'givn' => 'Nadia',
            'surn' => 'Rahman',
            'name' => 'Nadia Rahman',
            'sex' => 'F',
            'birthday' => '2001-03-09',
            'birthday_plac' => 'Jakarta, Indonesia',
            'description' => 'Primary researcher for this family showcase tree.',
            'team_id' => $team->id,
            'email' => 'person.nadia.rahman@familytree365.test',
            'phone' => '+62-21-0006',
            'child_in_family_id' => $childrenFamily->id,
        ]);

        $sibling = $this->upsertPerson([
            'gid' => 'I1007',
            'givn' => 'Daniel',
            'surn' => 'Rahman',
            'name' => 'Daniel Rahman',
            'sex' => 'M',
            'birthday' => '2004-10-15',
            'birthday_plac' => 'Jakarta, Indonesia',
            'description' => 'Co-researcher and media scanner.',
            'team_id' => $team->id,
            'email' => 'person.daniel.rahman@familytree365.test',
            'phone' => '+62-21-0007',
            'child_in_family_id' => $childrenFamily->id,
        ]);

        $cousin = $this->upsertPerson([
            'gid' => 'I1008',
            'givn' => 'Maya',
            'surn' => 'Rahman',
            'name' => 'Maya Rahman',
            'sex' => 'F',
            'birthday' => '2006-12-02',
            'birthday_plac' => 'Bandung, Indonesia',
            'description' => 'Younger cousin who helps verify stories.',
            'team_id' => $team->id,
            'email' => 'person.maya.rahman@familytree365.test',
            'phone' => '+62-21-0008',
            'child_in_family_id' => $childrenFamily->id,
        ]);

        Tree::updateOrCreate(
            [
                'user_id' => $owner->id,
                'name' => 'Rahman Family Showcase Tree',
            ],
            [
                'description' => 'Demo tree for recording walkthroughs and screenshots.',
                'root_person_id' => $ownerPerson->id,
            ]
        );

        Tree::updateOrCreate(
            [
                'user_id' => $coResearcher->id,
                'name' => 'Rahman Branch - Notes',
            ],
            [
                'description' => 'Secondary demo tree used for collaboration views.',
                'root_person_id' => $sibling->id,
            ]
        );

        $this->command->info('Demo showcase data ready.');
        $this->command->line('Demo login accounts (password: '.$demoPassword.'):');
        $this->command->line('- demo.owner@familytree365.test');
        $this->command->line('- demo.researcher@familytree365.test');
        $this->command->line('- demo.viewer@familytree365.test');
    }

    private function upsertPerson(array $attributes): Person
    {
        $identifier = ['email' => $attributes['email']];

        return Person::updateOrCreate($identifier, $attributes);
    }
}
