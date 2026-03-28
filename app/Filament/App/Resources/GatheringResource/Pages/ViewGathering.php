<?php

namespace App\Filament\App\Resources\GatheringResource\Pages;

use App\Filament\App\Resources\GatheringResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGathering extends ViewRecord
{
    protected static string $resource = GatheringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
