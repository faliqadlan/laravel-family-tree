<?php

namespace App\Filament\App\Resources\GatheringResource\Pages;

use App\Filament\App\Resources\GatheringResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGatherings extends ListRecords
{
    protected static string $resource = GatheringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
