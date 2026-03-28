<?php

namespace App\Filament\App\Resources\PersonResource\Pages;

use App\Filament\App\Resources\PersonResource;
use App\Models\Family;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;

class CreatePerson extends CreateRecord
{
    protected static string $resource = PersonResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $fatherId = ! empty($data['father_id']) ? (int) $data['father_id'] : null;
        $motherId = ! empty($data['mother_id']) ? (int) $data['mother_id'] : null;
        $confirmed = (bool) ($data['confirm_parent_linking'] ?? false);

        if ($confirmed && ($fatherId || $motherId)) {
            $family = Family::query()
                ->where('husband_id', $fatherId)
                ->where('wife_id', $motherId)
                ->first();

            if (! $family) {
                $family = Family::query()->create([
                    'husband_id' => $fatherId,
                    'wife_id' => $motherId,
                    'team_id' => Auth::user()?->currentTeam?->id,
                ]);
            }

            $data['child_in_family_id'] = $family->id;
        }

        unset($data['father_id'], $data['mother_id'], $data['confirm_parent_linking']);

        return $data;
    }
}
