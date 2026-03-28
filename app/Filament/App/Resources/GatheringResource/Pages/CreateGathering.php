<?php

namespace App\Filament\App\Resources\GatheringResource\Pages;

use App\Filament\App\Resources\GatheringResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGathering extends CreateRecord
{
    protected static string $resource = GatheringResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organizer_id'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
