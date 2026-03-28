<?php

namespace App\Filament\App\Resources\PersonResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\PersonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use App\Models\MediaObject;
use App\Models\Family;
use Illuminate\Support\Facades\Auth;

class EditPerson extends EditRecord
{
    protected static string $resource = PersonResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord()->load('childInFamily');
        $data['father_id'] = $record->childInFamily?->husband_id;
        $data['mother_id'] = $record->childInFamily?->wife_id;
        $data['confirm_parent_linking'] = false;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            Action::make('selectMedia')
                ->label('Select GEDCOM Media')
                ->icon('heroicon-o-photograph')
                ->modalHeading('Select GEDCOM Media to Use as Profile Photo')
                ->modalWidth('lg')
                ->form([
                    Select::make('media_id')
                        ->label('GEDCOM Media')
                        ->options(fn () => MediaObject::orderBy('id', 'desc')->pluck('titl', 'id')->toArray())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $mediaId = $data['media_id'] ?? null;
                    if (! $mediaId) {
                        $this->notify('danger', 'No media selected.');
                        return;
                    }

                    $media = MediaObject::with('files')->find($mediaId);
                    if (! $media) {
                        $this->notify('danger', 'Selected media not found.');
                        return;
                    }

                    // Try to find an associated file record; use its `medi` field as the path/URL.
                    $file = $media->files->first();
                    $filePath = $file?->medi ?? null;

                    if (! $filePath) {
                        $this->notify('danger', 'Selected media has no associated file path.');
                        return;
                    }

                    $record = $this->getRecord();
                    // Normalize the file path/URL before storing in photo_url
                    $url = $filePath;
                    try {
                        $startsWithHttp = str_starts_with(strtolower($filePath), 'http://') || str_starts_with(strtolower($filePath), 'https://');
                    } catch (\Throwable $e) {
                        $startsWithHttp = false;
                    }

                    if ($startsWithHttp || str_starts_with($filePath, '/')) {
                        $url = $filePath;
                    } else {
                        // If it exists on the public disk, build a public URL
                        try {
                            $disk = \Illuminate\Support\Facades\Storage::disk('public');
                            if ($disk->exists($filePath)) {
                                $url = $disk->url($filePath);
                            }
                        } catch (\Throwable $e) {
                            // leave original value if disk check fails
                        }
                    }

                    $record->photo_url = $url;
                    $record->save();

                    $this->notify('success', 'Person photo updated from GEDCOM media.');
                    // Refresh the page to show updated image in the form/table.
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
                }),
        ];
    }
}
