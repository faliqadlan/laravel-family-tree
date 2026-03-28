<?php

namespace App\Filament\App\Resources\GatheringResource\RelationManagers;

use App\Models\GatheringContribution;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class ContributionsRelationManager extends RelationManager
{
    protected static string $relationship = 'contributions';

    protected static ?string $title = 'Contributions';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('User')
                ->options(User::all()->pluck('name', 'id'))
                ->searchable()
                ->required(),
            TextInput::make('amount')
                ->numeric()
                ->required()
                ->minValue(0.01),
            Select::make('currency')
                ->options([
                    'USD' => 'USD',
                    'EUR' => 'EUR',
                    'GBP' => 'GBP',
                ])
                ->required()
                ->default('USD'),
            Select::make('status')
                ->options([
                    GatheringContribution::STATUS_PLEDGED => 'Pledged',
                    GatheringContribution::STATUS_PAID => 'Paid',
                    GatheringContribution::STATUS_CANCELLED => 'Cancelled',
                ])
                ->required()
                ->default(GatheringContribution::STATUS_PLEDGED),
            Textarea::make('notes')
                ->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->money(fn (GatheringContribution $record) => $record->currency)
                    ->sortable(),
                TextColumn::make('currency')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        GatheringContribution::STATUS_PAID => 'success',
                        GatheringContribution::STATUS_CANCELLED => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
