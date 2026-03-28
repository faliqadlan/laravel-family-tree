<?php

namespace App\Filament\App\Resources\GatheringResource\Pages;

use App\Filament\App\Resources\GatheringResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGathering extends EditRecord
{
    protected static string $resource = GatheringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
