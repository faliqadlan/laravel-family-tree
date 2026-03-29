<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use BezhanSalleh\FilamentShield\Support\Utils;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teamId = null;

        if (Utils::isTenancyEnabled()) {
            $teamId = Team::firstOrFail()->id;
        }

        Role::query()
            ->whereIn('name', ['super_admin', 'editor', 'panel_user'])
            ->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->delete();

        $adminRole = Role::firstOrCreate(array_filter([
            'name' => 'admin',
            'guard_name' => 'web',
            'team_id' => $teamId,
        ], fn ($value) => $value !== null));

        Role::firstOrCreate(array_filter([
            'name' => 'user',
            'guard_name' => 'web',
            'team_id' => $teamId,
        ], fn ($value) => $value !== null));

        $permissions = Permission::where('guard_name', 'web')->pluck('id')->toArray();
        $adminRole->syncPermissions($permissions);
    }
}
